CREATE OR REPLACE PROCEDURE authentication.procedure_quick_update_user(
    IN p_session_id UUID,
    IN p_payload JSONB
)
    LANGUAGE plpgsql
AS $$
DECLARE
    v_user_id_text TEXT := p_payload ->> 'user_id';
    v_raw_menus JSONB := p_payload -> 'menus';
    v_ordered_menus JSONB; -- Variabel untuk menampung menus yang sudah direorder
    v_executed_by UUID := (p_payload ->> 'executed_by')::UUID;
    v_old_data JSONB;
    v_new_data JSONB;
BEGIN
    -- 1. Validasi Locking
    IF EXISTS (SELECT 1 FROM temporary.authentication_user WHERE master_id = v_user_id_text AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'User ID % is currently being edited in a revision draft by another session.', v_user_id_text;
    END IF;

    -- 2. LOGIC REORDER: Membongkar array, memberi row_number baru, dan membungkusnya kembali
    -- Kita memaksa sort_order mengikuti index array (1, 2, 3...)
    SELECT jsonb_agg(
                   elem || jsonb_build_object('sort_order', row_num::text)
           ) INTO v_ordered_menus
    FROM (
             SELECT
                 elem,
                 row_number() OVER () as row_num
             FROM jsonb_array_elements(v_raw_menus) AS elem
         ) AS reordered;

    -- 3. Snapshot Data Lama
    SELECT to_jsonb(t) INTO v_old_data FROM authentication.user t WHERE t.user_id::TEXT = v_user_id_text;

    IF v_old_data IS NULL THEN
        RAISE EXCEPTION 'User with ID % not found.', v_user_id_text;
    END IF;

    -- 4. Update Master dengan v_ordered_menus (yang sudah punya sort_order baru)
    UPDATE authentication.user
    SET
        properties = jsonb_set(COALESCE(properties, '{}'::jsonb), '{menus}', v_ordered_menus),
        updated_at = NOW()
    WHERE user_id::TEXT = v_user_id_text;

    -- 5. Snapshot Data Baru & History Logging
    SELECT to_jsonb(t) INTO v_new_data FROM authentication.user t WHERE t.user_id::TEXT = v_user_id_text;

    INSERT INTO history.authentication_user (
        history_id,
        executed_by,
        action,
        old_data,
        new_data,
        executed_at
    )
    VALUES (
               gen_random_uuid(),
               v_executed_by,
               'UPDATE',
               v_old_data,
               v_new_data,
               NOW()
           );

END;
$$;

ALTER PROCEDURE authentication.procedure_quick_update_user(UUID, JSONB) OWNER TO postgres;