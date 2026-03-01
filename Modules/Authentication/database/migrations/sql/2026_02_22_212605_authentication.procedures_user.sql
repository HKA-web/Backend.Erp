-- 1. PROCEDURE UPSERT DRAFT (Menggunakan temporary_id sebagai PK draf)
DROP PROCEDURE IF EXISTS authentication.procedure_upsert_user_draft;
CREATE OR REPLACE PROCEDURE authentication.procedure_upsert_user_draft(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_temp_id UUID := (p_payload ->> 'temporary_id')::UUID;
BEGIN
    -- Jika payload tidak bawa temporary_id, buat baru (Insert draf baru)
    IF v_temp_id IS NULL THEN
        v_temp_id := gen_random_uuid();
END IF;

INSERT INTO temporary.authentication_user (
    temporary_id,
    session_id,
    master_id,
    temporary_option,
    user_id,
    user_name,
    is_removed
) VALUES (
             v_temp_id,
             p_session_id,
             p_payload ->> 'master_id',
             COALESCE(p_payload ->> 'temporary_option', 'I'),
             p_payload ->> 'user_id',
             p_payload ->> 'user_name',
             COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
         ) ON CONFLICT (temporary_id) DO UPDATE SET
    user_name = EXCLUDED.user_name,
    is_removed = EXCLUDED.is_removed,
    temporary_option = EXCLUDED.temporary_option,
    updated_at = NOW();
END;
$$;

-- 2. PROCEDURE REVISE (Check-out dari Master ke Temporary)
DROP PROCEDURE IF EXISTS authentication.procedure_revise_user;
CREATE OR REPLACE PROCEDURE authentication.procedure_revise_user(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_master_id TEXT := p_payload ->> 'user_id';
BEGIN
    -- Validasi Locking (Logical): Jangan biarkan user lain edit data yang sama di temporary
    IF EXISTS (SELECT 1 FROM temporary.authentication_user WHERE master_id = v_master_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data ID % is currently being edited by another session.', v_master_id;
END IF;

INSERT INTO temporary.authentication_user (
    temporary_id,
    session_id,
    master_id,
    temporary_option,
    user_id,
    user_name,
    is_removed
)
SELECT
    gen_random_uuid(),
    p_session_id,
    user_id,
    'U', -- Default 'U' (Update) karena narik dari master
    user_id,
    user_name,
    is_removed
FROM authentication.user WHERE user_id = v_master_id
    ON CONFLICT (master_id, session_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master berdasarkan temporary_option)
DROP PROCEDURE IF EXISTS authentication.procedure_commit_user;
CREATE OR REPLACE PROCEDURE authentication.procedure_commit_user(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_temp_id UUID := (p_payload ->> 'temporary_id')::UUID;
    v_rec RECORD;
    v_old_data JSONB;
    v_new_data JSONB;
BEGIN
    -- Ambil data dari temporary
SELECT * INTO v_rec FROM temporary.authentication_user
WHERE temporary_id = v_temp_id AND session_id = p_session_id;

IF NOT FOUND THEN
        RAISE EXCEPTION 'Draft record not found.';
END IF;

    -- A. Snapshot Data Lama (Jika Update/Delete)
    IF v_rec.master_id IS NOT NULL THEN
SELECT to_jsonb(t) INTO v_old_data FROM authentication.user t WHERE t.user_id = v_rec.master_id;
END IF;

    -- B. Eksekusi ke Master berdasarkan temporary_option
    IF v_rec.temporary_option = 'D' THEN
DELETE FROM authentication.user WHERE user_id = v_rec.master_id;
ELSE
        -- INSERT atau UPDATE
        INSERT INTO authentication.user (user_id, user_name, status, is_removed, created_at, updated_at)
        VALUES (v_rec.user_id, v_rec.user_name, 'POSTED', v_rec.is_removed, NOW(), NOW())
        ON CONFLICT (user_id) DO UPDATE SET
    user_name = EXCLUDED.user_name,
                                     is_removed = EXCLUDED.is_removed,
                                     status = 'POSTED',
                                     updated_at = NOW();
END IF;

    -- C. History Logging
SELECT to_jsonb(t) INTO v_new_data FROM authentication.user t WHERE t.user_id = v_rec.user_id;

INSERT INTO history.authentication_user (history_id, executed_by, action, old_data, new_data, executed_at)
VALUES (
           gen_random_uuid(),
           (p_payload ->> 'executed_by')::UUID,
           CASE
               WHEN v_rec.temporary_option = 'D' THEN 'DELETE'
               WHEN v_old_data IS NULL THEN 'INSERT'
               ELSE 'UPDATE'
               END,
           v_old_data,
           v_new_data,
           NOW()
       );

-- D. Cleanup Temporary
DELETE FROM temporary.authentication_user WHERE temporary_id = v_temp_id;
END;
$$;
