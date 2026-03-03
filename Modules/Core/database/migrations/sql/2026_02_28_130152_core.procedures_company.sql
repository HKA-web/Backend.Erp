-- 1. PROCEDURE UPSERT DRAFT (Menggunakan temporary_id sebagai PK draf)
DROP PROCEDURE IF EXISTS core.procedure_upsert_company_draft;
CREATE OR REPLACE PROCEDURE core.procedure_upsert_company_draft(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_temp_id UUID := (p_payload ->> 'temporary_id')::UUID;
BEGIN
    -- Jika payload tidak bawa temporary_id, buat baru
    IF v_temp_id IS NULL THEN
        v_temp_id := gen_random_uuid();
END IF;

INSERT INTO temporary.core_company (
    temporary_id,
    session_id,
    master_id,
    temporary_option,
    company_id,
    company_name,
    is_removed
) VALUES (
             v_temp_id,
             p_session_id,
             p_payload ->> 'master_id',
             COALESCE(p_payload ->> 'temporary_option', 'I'),
             p_payload ->> 'company_id',
             p_payload ->> 'company_name',
             COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
         ) ON CONFLICT (temporary_id) DO UPDATE SET
    company_name = EXCLUDED.company_name,
    is_removed = EXCLUDED.is_removed,
    temporary_option = EXCLUDED.temporary_option,
    updated_at = NOW();
END;
$$;

-- 2. PROCEDURE REVISE (Check-out dari Master ke Temporary)
DROP PROCEDURE IF EXISTS core.procedure_revise_company;
CREATE OR REPLACE PROCEDURE core.procedure_revise_company(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_master_id TEXT := p_payload ->> 'company_id';
BEGIN
    -- Validasi Locking
    IF EXISTS (SELECT 1 FROM temporary.core_company
               WHERE master_id = v_master_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data Company % is currently being edited by another session.', v_master_id;
END IF;

INSERT INTO temporary.core_company (
    temporary_id,
    session_id,
    master_id,
    temporary_option,
    company_id,
    company_name,
    is_removed
)
SELECT
    gen_random_uuid(),
    p_session_id,
    company_id,
    COALESCE(p_payload ->> 'temporary_option', 'U'),
    company_id,
    company_name,
    COALESCE((p_payload ->> 'is_removed')::boolean, false)
FROM core.company WHERE company_id = v_master_id
    ON CONFLICT (master_id, session_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master berdasarkan temporary_option)
DROP PROCEDURE IF EXISTS core.procedure_commit_company;
CREATE OR REPLACE PROCEDURE core.procedure_commit_company(
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
SELECT * INTO v_rec FROM temporary.core_company
WHERE temporary_id = v_temp_id AND session_id = p_session_id;

IF NOT FOUND THEN
        RAISE EXCEPTION 'Draft Company record not found.';
END IF;

    -- A. Snapshot Data Lama
    IF v_rec.master_id IS NOT NULL THEN
SELECT to_jsonb(t) INTO v_old_data FROM core.company t WHERE t.company_id = v_rec.master_id;
END IF;

    -- B. Eksekusi ke Master berdasarkan temporary_option
    IF v_rec.temporary_option = 'D' THEN
DELETE FROM core.company WHERE company_id = v_rec.master_id;
ELSE
        INSERT INTO core.company (company_id, company_name, status, is_removed, created_at, updated_at)
        VALUES (v_rec.company_id, v_rec.company_name, 'POSTED', v_rec.is_removed, NOW(), NOW())
        ON CONFLICT (company_id) DO UPDATE SET
    company_name = EXCLUDED.company_name,
                                        is_removed = EXCLUDED.is_removed,
                                        status = 'POSTED',
                                        updated_at = NOW();
END IF;

    -- C. Snapshot Baru & History
    IF v_rec.temporary_option <> 'D' THEN
SELECT to_jsonb(t) INTO v_new_data FROM core.company t WHERE t.company_id = v_rec.company_id;
END IF;

INSERT INTO history.core_company (history_id, executed_by, action, old_data, new_data, executed_at)
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
DELETE FROM temporary.core_company WHERE temporary_id = v_temp_id;
END;
$$;
