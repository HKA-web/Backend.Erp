CREATE OR REPLACE PROCEDURE authentication.procedure_reorder_menu_user(
    IN p_session_id UUID,
    IN p_payload JSONB
)
    LANGUAGE plpgsql
AS $$
DECLARE
    v_user_id_text TEXT := p_payload ->> 'user_id';
    v_input_menus JSONB := p_payload -> 'menus';
    v_final_menus JSONB;
    v_executed_by UUID := (p_payload ->> 'executed_by')::UUID;
    v_old_data JSONB;
    v_new_data JSONB;
BEGIN
    -- 1. Validasi Locking: Mencegah tabrakan edit antar session
    IF EXISTS (SELECT 1 FROM temporary.authentication_user WHERE master_id = v_user_id_text AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'User ID % is currently being edited in a revision draft by another session.', v_user_id_text;
    END IF;

    -- 2. LOGIC SYNC, REORDER & PERMISSION JOIN
    -- Menghasilkan array JSON baru yang berisi data Master Menu + Permission Object
    SELECT jsonb_agg(src.updated_menu) INTO v_final_menus
    FROM (
             SELECT
                 (
                     to_jsonb(m) ||
                     jsonb_build_object(
                             'sort_order', t.pos::text,
                             'permission', (
                                 SELECT to_jsonb(p)
                                 FROM auth_permissions p
                                 -- Cast ke text untuk menghindari mismatch UUID vs BigInt/String
                                 WHERE p.id::text = m.permission_id::text
                             )
                     )
                     ) as updated_menu
             FROM (
                      -- Ambil ID Menu dan urutan (pos) dari input JSON user
                      SELECT
                          (value ->> 'menu_id') as m_id,
                          row_number() OVER () as pos
                      FROM jsonb_array_elements(v_input_menus)
                  ) t
                      -- Join ke Master Menu dengan casting eksplisit ke text untuk performa & keamanan tipe data
                      JOIN core.menu m ON m.menu_id::text = t.m_id::text
             ORDER BY t.pos ASC
         ) src;

    -- 3. Snapshot Data Lama (Audit Trail)
    SELECT to_jsonb(t) INTO v_old_data FROM authentication.user t WHERE t.user_id::TEXT = v_user_id_text;

    IF v_old_data IS NULL THEN
        RAISE EXCEPTION 'User with ID % not found.', v_user_id_text;
    END IF;

    -- 4. Update Kolom Properties pada Tabel User
    UPDATE authentication.user
    SET
        properties = jsonb_set(COALESCE(properties, '{}'::jsonb), '{menus}', v_final_menus),
        updated_at = NOW()
    WHERE user_id::TEXT = v_user_id_text;

    -- 5. Snapshot Data Baru & Logging ke History
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

-- Pastikan owner sesuai dengan environment kamu
ALTER PROCEDURE authentication.procedure_reorder_menu_user(UUID, JSONB) OWNER TO postgres;