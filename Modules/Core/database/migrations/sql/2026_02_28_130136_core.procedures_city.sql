-- 1. PROCEDURE UPSERT DRAFT (Menggunakan temporary_id sebagai PK draf)
DROP PROCEDURE IF EXISTS core.procedure_upsert_city_draft;
CREATE OR REPLACE PROCEDURE core.procedure_upsert_city_draft(
    p_session_id VARCHAR,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_temp_id VARCHAR := (p_payload ->> 'temporary_id');
BEGIN
    -- Jika payload tidak bawa temporary_id, buat baru
    IF v_temp_id IS NULL THEN
        v_temp_id := gen_random_uuid()::TEXT;
END IF;

INSERT INTO temporary.core_city (
    temporary_id,
    session_id,
    master_id,
    parent_temporary_id, -- Penghubung ke draf Province
    temporary_option,
    city_id,
    city_name,
    province_id, -- Kolom asli master
    is_removed
) VALUES (
             v_temp_id,
             p_session_id,
             p_payload ->> 'master_id',
             (p_payload ->> 'parent_temporary_id'),
             COALESCE(p_payload ->> 'temporary_option', 'I'),
             p_payload ->> 'city_id',
             p_payload ->> 'city_name',
             p_payload ->> 'province_id',
             COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
         ) ON CONFLICT (temporary_id) DO UPDATE SET
    city_name = EXCLUDED.city_name,
    province_id = EXCLUDED.province_id,
    is_removed = EXCLUDED.is_removed,
    temporary_option = EXCLUDED.temporary_option,
    updated_at = NOW();
END;
$$;

-- 2. PROCEDURE REVISE (Check-out dari Master ke Temporary)
DROP PROCEDURE IF EXISTS core.procedure_revise_city;
CREATE OR REPLACE PROCEDURE core.procedure_revise_city(
    p_session_id VARCHAR,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
v_master_id TEXT := p_payload ->> 'city_id';
BEGIN
    -- Validasi Locking
    IF EXISTS (SELECT 1 FROM temporary.core_city
               WHERE master_id = v_master_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data City % is currently being edited by another session.', v_master_id;
END IF;

INSERT INTO temporary.core_city (
    temporary_id,
    session_id,
    master_id,
    temporary_option,
    city_id,
    city_name,
    province_id,
    is_removed
)
SELECT
    gen_random_uuid()::TEXT,
    p_session_id,
    city_id,
    COALESCE(p_payload ->> 'temporary_option', 'U'),
    city_id,
    city_name,
    province_id,
    COALESCE((p_payload ->> 'is_removed')::boolean, false)
FROM core.city WHERE city_id = v_master_id
    ON CONFLICT (master_id, session_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master berdasarkan temporary_option)
DROP PROCEDURE IF EXISTS core.procedure_commit_city;
CREATE OR REPLACE PROCEDURE core.procedure_commit_city(
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
    -- Ambil data draf City
SELECT * INTO v_rec FROM temporary.core_city
WHERE temporary_id = v_temp_id AND session_id = p_session_id;

IF NOT FOUND THEN
        RAISE EXCEPTION 'Draft City record not found.';
END IF;

    -- A. Snapshot Data Lama
    IF v_rec.master_id IS NOT NULL THEN
SELECT to_jsonb(t) INTO v_old_data FROM core.city t WHERE t.city_id = v_rec.master_id;
END IF;

    -- B. Eksekusi ke Master
    IF v_rec.temporary_option = 'D' THEN
DELETE FROM core.city WHERE city_id = v_rec.master_id;
    v_final_pk := v_rec.master_id;
    ELSE
        v_final_pk := v_rec.city_id;

        IF v_old_data IS NULL AND (v_final_pk IS NULL OR v_final_pk = '') THEN
            v_final_pk := core.get_next_sequence('CITY');
        END IF;


        INSERT INTO core.city (city_id, province_id, city_name, status, is_removed, created_at, updated_at)
        VALUES (v_final_pk, v_rec.province_id, v_rec.city_name, 'POSTED', v_rec.is_removed, NOW(), NOW())
        ON CONFLICT (city_id) DO UPDATE SET
    province_id = EXCLUDED.province_id,
                                     city_name = EXCLUDED.city_name,
                                     is_removed = EXCLUDED.is_removed,
                                     status = 'POSTED',
                                     updated_at = NOW();
END IF;

    -- C. Snapshot Baru & History
    IF v_rec.temporary_option <> 'D' THEN
SELECT to_jsonb(t) INTO v_new_data FROM core.city t WHERE t.city_id = v_final_pk;
END IF;

INSERT INTO history.core_city (history_id, executed_by, action, old_data, new_data, executed_at)
VALUES (
           gen_random_uuid()::TEXT,
           (p_payload ->> 'executed_by'),
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
DELETE FROM temporary.core_city WHERE temporary_id = v_temp_id;
END;
$$;
