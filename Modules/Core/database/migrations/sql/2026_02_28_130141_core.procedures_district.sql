-- 1. PROCEDURE UPSERT DRAFT (Untuk CRUD di Workspace/Sandbox)
DROP PROCEDURE IF EXISTS core.procedure_upsert_district_draft;
CREATE OR REPLACE PROCEDURE core.procedure_upsert_district_draft(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO temporary.core_district (
        district_id,
        district_name,
        status,
        session_id,
        is_removed
    ) VALUES (
        p_payload ->> 'district_id',
        p_payload ->> 'district_name',
        'DRAFT',
        p_session_id,
        COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
    ) ON CONFLICT (district_id) DO UPDATE SET
        district_name = EXCLUDED.district_name,
        is_removed = EXCLUDED.is_removed,
        status = 'DRAFT';
END;
$$;

-- 2. PROCEDURE REVISE (Tarik Master ke Temporary)
DROP PROCEDURE IF EXISTS core.procedure_revise_district;
CREATE OR REPLACE PROCEDURE core.procedure_revise_district(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_id TEXT := p_payload ->> 'district_id';
BEGIN
    -- Validasi Locking: Cek jika sudah ada draft milik session lain
    IF EXISTS (SELECT 1 FROM temporary.core_district WHERE district_id = v_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data is being edited by another user.';
    END IF;

    INSERT INTO temporary.core_district (district_id, district_name, status, session_id, is_removed)
    SELECT district_id, district_name, 'DRAFT', p_session_id, FALSE
    FROM core.district WHERE district_id = v_id
    ON CONFLICT (district_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master + Audit)
DROP PROCEDURE IF EXISTS core.procedure_commit_district;
CREATE OR REPLACE PROCEDURE core.procedure_commit_district(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_id TEXT := p_payload ->> 'district_id';
    v_old_data JSONB;
    v_new_data JSONB;
    v_user_id UUID := (p_payload ->> 'user_id')::UUID;
    v_is_removed BOOLEAN;
BEGIN
    -- Ambil flag is_removed dari temporary sebelum dihapus
    SELECT is_removed INTO v_is_removed FROM temporary.core_district WHERE district_id = v_id AND session_id = p_session_id;

    -- A. Snapshot Data Lama
    SELECT to_jsonb(t) INTO v_old_data FROM core.district t WHERE t.district_id = v_id;

    -- B. Move ke Master
    INSERT INTO core.district (district_id, district_name, status, is_removed, created_at, updated_at)
    SELECT district_id, district_name, 'POSTED', is_removed, NOW(), NOW()
    FROM temporary.core_district WHERE district_id = v_id AND session_id = p_session_id
    ON CONFLICT (district_id) DO UPDATE SET
        district_name = EXCLUDED.district_name,
        is_removed = EXCLUDED.is_removed,
        status = 'POSTED',
        updated_at = NOW();

    -- C. Snapshot Baru & History
    SELECT to_jsonb(t) INTO v_new_data FROM core.district t WHERE t.district_id = v_id;

    INSERT INTO history.district_history (history_id, executed_by, action, old_data, new_data, executed_at)
    VALUES (
        gen_random_uuid(),
        v_user_id,
        CASE WHEN v_is_removed THEN 'DELETE' WHEN v_old_data IS NULL THEN 'INSERT' ELSE 'UPDATE' END,
        v_old_data,
        v_new_data,
        NOW()
    );

    -- D. Cleanup Temporary
    DELETE FROM temporary.core_district WHERE district_id = v_id AND session_id = p_session_id;
END;
$$;