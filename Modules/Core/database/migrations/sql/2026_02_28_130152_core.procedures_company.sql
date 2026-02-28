-- 1. PROCEDURE UPSERT DRAFT (Untuk CRUD di Workspace/Sandbox)
DROP PROCEDURE IF EXISTS core.procedure_upsert_company_draft;
CREATE OR REPLACE PROCEDURE core.procedure_upsert_company_draft(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO temporary.core_company (
        company_id,
        company_name,
        status,
        session_id,
        is_removed
    ) VALUES (
        p_payload ->> 'company_id',
        p_payload ->> 'company_name',
        'DRAFT',
        p_session_id,
        COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
    ) ON CONFLICT (company_id) DO UPDATE SET
        company_name = EXCLUDED.company_name,
        is_removed = EXCLUDED.is_removed,
        status = 'DRAFT';
END;
$$;

-- 2. PROCEDURE REVISE (Tarik Master ke Temporary)
DROP PROCEDURE IF EXISTS core.procedure_revise_company;
CREATE OR REPLACE PROCEDURE core.procedure_revise_company(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_id TEXT := p_payload ->> 'company_id';
BEGIN
    -- Validasi Locking: Cek jika sudah ada draft milik session lain
    IF EXISTS (SELECT 1 FROM temporary.core_company WHERE company_id = v_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data is being edited by another user.';
    END IF;

    INSERT INTO temporary.core_company (company_id, company_name, status, session_id, is_removed)
    SELECT company_id, company_name, 'DRAFT', p_session_id, FALSE
    FROM core.company WHERE company_id = v_id
    ON CONFLICT (company_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master + Audit)
DROP PROCEDURE IF EXISTS core.procedure_commit_company;
CREATE OR REPLACE PROCEDURE core.procedure_commit_company(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_id TEXT := p_payload ->> 'company_id';
    v_old_data JSONB;
    v_new_data JSONB;
    v_user_id UUID := (p_payload ->> 'user_id')::UUID;
    v_is_removed BOOLEAN;
BEGIN
    -- Ambil flag is_removed dari temporary sebelum dihapus
    SELECT is_removed INTO v_is_removed FROM temporary.core_company WHERE company_id = v_id AND session_id = p_session_id;

    -- A. Snapshot Data Lama
    SELECT to_jsonb(t) INTO v_old_data FROM core.company t WHERE t.company_id = v_id;

    -- B. Move ke Master
    INSERT INTO core.company (company_id, company_name, status, is_removed, created_at, updated_at)
    SELECT company_id, company_name, 'POSTED', is_removed, NOW(), NOW()
    FROM temporary.core_company WHERE company_id = v_id AND session_id = p_session_id
    ON CONFLICT (company_id) DO UPDATE SET
        company_name = EXCLUDED.company_name,
        is_removed = EXCLUDED.is_removed,
        status = 'POSTED',
        updated_at = NOW();

    -- C. Snapshot Baru & History
    SELECT to_jsonb(t) INTO v_new_data FROM core.company t WHERE t.company_id = v_id;

    INSERT INTO history.company_history (history_id, executed_by, action, old_data, new_data, executed_at)
    VALUES (
        gen_random_uuid(),
        v_user_id,
        CASE WHEN v_is_removed THEN 'DELETE' WHEN v_old_data IS NULL THEN 'INSERT' ELSE 'UPDATE' END,
        v_old_data,
        v_new_data,
        NOW()
    );

    -- D. Cleanup Temporary
    DELETE FROM temporary.core_company WHERE company_id = v_id AND session_id = p_session_id;
END;
$$;