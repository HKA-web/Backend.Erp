-- 1. PROCEDURE UPSERT DRAFT (Menggunakan temporary_id sebagai PK draf)
DROP PROCEDURE IF EXISTS core.procedure_upsert_province_draft;
CREATE OR REPLACE PROCEDURE core.procedure_upsert_province_draft(
    p_session_id VARCHAR,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_temp_id VARCHAR := (p_payload ->> 'temporary_id');
BEGIN
    -- Jika payload tidak bawa temporary_id, buat baru (Insert draf baru)
    IF v_temp_id IS NULL THEN
        v_temp_id := gen_random_uuid()::TEXT;
END IF;

INSERT INTO temporary.core_province (
    temporary_id,
    session_id,
    master_id,
    temporary_option,
    province_id,
    province_name,
    is_removed
) VALUES (
             v_temp_id,
             p_session_id,
             p_payload ->> 'master_id', -- ID asli dari core.province jika ada
             COALESCE(p_payload ->> 'temporary_option', 'I'),
             p_payload ->> 'province_id',
             p_payload ->> 'province_name',
             COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
         ) ON CONFLICT (temporary_id) DO UPDATE SET
    province_name = EXCLUDED.province_name,
    is_removed = EXCLUDED.is_removed,
    temporary_option = EXCLUDED.temporary_option,
    updated_at = NOW();
END;
$$;

-- 2. PROCEDURE REVISE (Check-out dari Master ke Temporary)
DROP PROCEDURE IF EXISTS core.procedure_revise_province;
CREATE OR REPLACE PROCEDURE core.procedure_revise_province(
    p_session_id VARCHAR,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_master_id TEXT := p_payload ->> 'province_id';
BEGIN
    -- Validasi Locking: Cek jika sudah ada draf milik session lain untuk record ini
    IF EXISTS (SELECT 1 FROM temporary.core_province
               WHERE master_id = v_master_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data Province % is currently being edited by another session.', v_master_id;
END IF;

INSERT INTO temporary.core_province (
    temporary_id,
    session_id,
    master_id,
    temporary_option,
    province_id,
    province_name,
    is_removed
)
SELECT
    gen_random_uuid()::TEXT,
    p_session_id,
    province_id, -- masuk ke master_id
    COALESCE(p_payload ->> 'temporary_option', 'U'),
    province_id,
    province_name,
    COALESCE((p_payload ->> 'is_removed')::boolean, false)
FROM core.province WHERE province_id = v_master_id
    ON CONFLICT (master_id, session_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master berdasarkan temporary_option)
DROP PROCEDURE IF EXISTS core.procedure_commit_province;
CREATE OR REPLACE PROCEDURE core.procedure_commit_province(
    p_session_id VARCHAR,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_temp_id VARCHAR := (p_payload ->> 'temporary_id');
    v_rec RECORD;
    v_old_data JSONB;
    v_new_data JSONB;
    v_final_pk VARCHAR;
BEGIN
    -- Ambil data dari temporary
SELECT * INTO v_rec FROM temporary.core_province
WHERE temporary_id = v_temp_id AND session_id = p_session_id;

IF NOT FOUND THEN
        RAISE EXCEPTION 'Draft Province record not found.';
END IF;

    -- A. Snapshot Data Lama (Jika Update/Delete berdasarkan master_id)
    IF v_rec.master_id IS NOT NULL THEN
SELECT to_jsonb(t) INTO v_old_data FROM core.province t WHERE t.province_id = v_rec.master_id;
END IF;

    -- B. Eksekusi ke Master berdasarkan temporary_option
    IF v_rec.temporary_option = 'D' THEN
DELETE FROM core.province WHERE province_id = v_rec.master_id;
    v_final_pk := v_rec.master_id;
    ELSE
        v_final_pk := v_rec.province_id;

        IF v_old_data IS NULL AND (v_final_pk IS NULL OR v_final_pk = '') THEN
            v_final_pk := core.get_next_sequence('PROVINCE');
        END IF;


        -- INSERT atau UPDATE menggunakan UPSERT ke core.province
        INSERT INTO core.province (province_id, province_name, status, is_removed, created_at, updated_at)
        VALUES (v_final_pk, v_rec.province_name, 'POSTED', v_rec.is_removed, NOW(), NOW())
        ON CONFLICT (province_id) DO UPDATE SET
    province_name = EXCLUDED.province_name,
                                         is_removed = EXCLUDED.is_removed,
                                         status = 'POSTED',
                                         updated_at = NOW();
END IF;

    -- C. Snapshot Baru & History
    -- Jika operasi adalah Delete, new_data akan null
    IF v_rec.temporary_option <> 'D' THEN
SELECT to_jsonb(t) INTO v_new_data FROM core.province t WHERE t.province_id = v_final_pk;
END IF;

INSERT INTO history.core_province (history_id, executed_by, action, old_data, new_data, executed_at)
VALUES (
           gen_random_uuid()::TEXT,
           (p_payload ->> 'executed_by'), -- ID User yang melakukan commit
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
DELETE FROM temporary.core_province WHERE temporary_id = v_temp_id;
END;
$$;
