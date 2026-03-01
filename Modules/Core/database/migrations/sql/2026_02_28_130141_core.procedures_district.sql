-- 1. PROCEDURE UPSERT DRAFT (Menggunakan temporary_id sebagai PK draf)
DROP PROCEDURE IF EXISTS core.procedure_upsert_district_draft;
CREATE OR REPLACE PROCEDURE core.procedure_upsert_district_draft(
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

INSERT INTO temporary.core_district (
    temporary_id,
    session_id,
    master_id,
    parent_temporary_id, -- Link ke temporary_id milik City
    temporary_option,
    district_id,
    district_name,
    city_id,
    is_removed
) VALUES (
             v_temp_id,
             p_session_id,
             p_payload ->> 'master_id',
             (p_payload ->> 'parent_temporary_id')::UUID,
             COALESCE(p_payload ->> 'temporary_option', 'I'),
             p_payload ->> 'district_id',
             p_payload ->> 'district_name',
             p_payload ->> 'city_id',
             COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
         ) ON CONFLICT (temporary_id) DO UPDATE SET
    district_name = EXCLUDED.district_name,
    city_id = EXCLUDED.city_id,
    is_removed = EXCLUDED.is_removed,
    temporary_option = EXCLUDED.temporary_option,
    updated_at = NOW();
END;
$$;

-- 2. PROCEDURE REVISE (Check-out dari Master ke Temporary)
DROP PROCEDURE IF EXISTS core.procedure_revise_district;
CREATE OR REPLACE PROCEDURE core.procedure_revise_district(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_master_id TEXT := p_payload ->> 'district_id';
BEGIN
    -- Validasi Locking
    IF EXISTS (SELECT 1 FROM temporary.core_district
               WHERE master_id = v_master_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data District % is currently being edited by another session.', v_master_id;
END IF;

INSERT INTO temporary.core_district (
    temporary_id,
    session_id,
    master_id,
    temporary_option,
    district_id,
    district_name,
    city_id,
    is_removed
)
SELECT
    gen_random_uuid(),
    p_session_id,
    district_id,
    'U', -- Default Update
    district_id,
    district_name,
    city_id,
    is_removed
FROM core.district WHERE district_id = v_master_id
    ON CONFLICT (master_id, session_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master berdasarkan temporary_option)
DROP PROCEDURE IF EXISTS core.procedure_commit_district;
CREATE OR REPLACE PROCEDURE core.procedure_commit_district(
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
SELECT * INTO v_rec FROM temporary.core_district
WHERE temporary_id = v_temp_id AND session_id = p_session_id;

IF NOT FOUND THEN
        RAISE EXCEPTION 'Draft District record not found.';
END IF;

    -- A. Snapshot Data Lama
    IF v_rec.master_id IS NOT NULL THEN
SELECT to_jsonb(t) INTO v_old_data FROM core.district t WHERE t.district_id = v_rec.master_id;
END IF;

    -- B. Eksekusi ke Master berdasarkan temporary_option
    IF v_rec.temporary_option = 'D' THEN
DELETE FROM core.district WHERE district_id = v_rec.master_id;
ELSE
        INSERT INTO core.district (district_id, city_id, district_name, status, is_removed, created_at, updated_at)
        VALUES (v_rec.district_id, v_rec.city_id, v_rec.district_name, 'POSTED', v_rec.is_removed, NOW(), NOW())
        ON CONFLICT (district_id) DO UPDATE SET
    city_id = EXCLUDED.city_id,
                                         district_name = EXCLUDED.district_name,
                                         is_removed = EXCLUDED.is_removed,
                                         status = 'POSTED',
                                         updated_at = NOW();
END IF;

    -- C. Snapshot Baru & History
    IF v_rec.temporary_option <> 'D' THEN
SELECT to_jsonb(t) INTO v_new_data FROM core.district t WHERE t.district_id = v_rec.district_id;
END IF;

INSERT INTO history.core_district (history_id, executed_by, action, old_data, new_data, executed_at)
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
DELETE FROM temporary.core_district WHERE temporary_id = v_temp_id;
END;
$$;
