CREATE OR REPLACE PROCEDURE core.push_village(
    p_session_id UUID,
    p_user_id INTEGER,
    p_payload JSONB
)
LANGUAGE plpgsql
AS $$
DECLARE
v_status TEXT;
    v_village_id
TEXT;
BEGIN
    -- Extract info penting dari JSONB
    v_status
:= COALESCE(p_payload->>'status', 'DRAFT');
    v_village_id
:= p_payload->>'village_id';

    -- LOGIKA IF BRANCHING
    IF
v_status = 'POSTED' THEN

        -- 1. MASUK KE MASTER
        INSERT INTO core.village (
            village_id,
            village_name,
            status,
            created_at,
            created_by
        )
        VALUES (
            v_village_id,
            p_payload->>'village_name',
            'POSTED',
            NOW(),
            p_user_id
        )
        ON CONFLICT (village_id)
        DO
UPDATE SET
    village_name = EXCLUDED.village_name,
    status = EXCLUDED.status,
    updated_at = NOW();

-- 2. BERSIHKAN DRAFT DI TEMPORARY (Jika ada)
DELETE
FROM temporary.core_village
WHERE village_id = v_village_id
  AND session_id = p_session_id;

ELSE
        -- 3. JIKA DRAFT, MASUK/UPDATE KE TEMPORARY SAJA
        INSERT INTO temporary.core_village (
            village_id,
            village_name,
            status,
            session_id,
            created_by,
            temp_created_at
        )
        VALUES (
            v_village_id,
            p_payload->>'village_name',
            'DRAFT',
            p_session_id,
            p_user_id,
            NOW()
        )
        ON CONFLICT (village_id, session_id)
        DO
UPDATE SET
    village_name = EXCLUDED.village_name,
    status = EXCLUDED.status,
    temp_created_at = NOW();

END IF;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'UPSERT_FAILED: %', SQLERRM;
END;
$$;
