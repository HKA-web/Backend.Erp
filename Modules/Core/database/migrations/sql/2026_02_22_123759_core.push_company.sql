CREATE OR REPLACE PROCEDURE core.push_company(
    p_session_id UUID,
    p_user_id INTEGER,
    p_payload JSONB
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_status TEXT;
    v_company_id TEXT;
BEGIN
    -- Extract info dari JSONB
    v_status := COALESCE(p_payload->>'status', 'DRAFT');
    v_company_id := p_payload->>'company_id';

    IF v_status = 'POSTED' THEN
        -- 1. MASUK KE MASTER
        INSERT INTO core.company (
            company_id,
            company_name,
            status,
            created_at,
            created_by
        )
        VALUES (
            v_company_id,
            p_payload->>'company_name',
            'POSTED',
            NOW(),
            p_user_id
        )
        ON CONFLICT (company_id)
        DO UPDATE SET
            company_name = EXCLUDED.company_name,
            status = EXCLUDED.status,
            updated_at = NOW();

        -- 2. BERSIHKAN DRAFT DI TEMPORARY
        DELETE FROM temporary.core_company
        WHERE company_id = v_company_id
          AND session_id = p_session_id;
    ELSE
        -- 3. JIKA DRAFT, MASUK KE TEMPORARY
        INSERT INTO temporary.core_company (
            company_id,
            company_name,
            status,
            session_id,
            created_by
        )
        VALUES (
            v_company_id,
            p_payload->>'company_name',
            'DRAFT',
            p_session_id,
            p_user_id
        )
        ON CONFLICT (company_id, session_id)
        DO UPDATE SET
            company_name = EXCLUDED.company_name,
            status = EXCLUDED.status;
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'UPSERT_FAILED: %', SQLERRM;
END;
$$;
