-- 1. PROCEDURE UPSERT DRAFT (Menggunakan temporary_id sebagai PK draf)
DROP PROCEDURE IF EXISTS core.procedure_upsert_menu_draft;
CREATE OR REPLACE PROCEDURE core.procedure_upsert_menu_draft(
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

    INSERT INTO temporary.core_menu (
        temporary_id,
        session_id,
        master_id,
        temporary_option,
        menu_id,
        menu_name,
        is_removed
    ) VALUES (
        v_temp_id,
        p_session_id,
        p_payload ->> 'master_id',
        COALESCE(p_payload ->> 'temporary_option', 'I'),
        p_payload ->> 'menu_id',
        p_payload ->> 'menu_name',
        COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
    ) ON CONFLICT (temporary_id) DO UPDATE SET
        menu_name = EXCLUDED.menu_name,
        is_removed = EXCLUDED.is_removed,
        temporary_option = EXCLUDED.temporary_option,
        updated_at = NOW();
END;
$$;

-- 2. PROCEDURE REVISE (Check-out dari Master ke Temporary)
DROP PROCEDURE IF EXISTS core.procedure_revise_menu;
CREATE OR REPLACE PROCEDURE core.procedure_revise_menu(
    p_session_id VARCHAR,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_master_id TEXT := p_payload ->> 'menu_id';
BEGIN
    -- Validasi Locking (Logical): Jangan biarkan user lain edit data yang sama di temporary
    IF EXISTS (SELECT 1 FROM temporary.core_menu WHERE master_id = v_master_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data ID % is currently being edited by another session.', v_master_id;
    END IF;

    INSERT INTO temporary.core_menu (
        temporary_id,
        session_id,
        master_id,
        temporary_option,
        menu_id,
        menu_name,
        is_removed
    )
    SELECT
        gen_random_uuid()::TEXT,
        p_session_id,
        menu_id,
        COALESCE(p_payload ->> 'temporary_option', 'U'),
        menu_id,
        menu_name,
        COALESCE((p_payload ->> 'is_removed')::boolean, false)
    FROM core.menu WHERE menu_id = v_master_id
    ON CONFLICT (master_id, session_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master berdasarkan temporary_option)
DROP PROCEDURE IF EXISTS core.procedure_commit_menu;
CREATE OR REPLACE PROCEDURE core.procedure_commit_menu(
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
    SELECT * INTO v_rec FROM temporary.core_menu
    WHERE temporary_id = v_temp_id AND session_id = p_session_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Draft record not found.';
    END IF;

    -- A. Snapshot Data Lama (Jika Update/Delete)
    IF v_rec.master_id IS NOT NULL THEN
        SELECT to_jsonb(t) INTO v_old_data FROM core.menu t WHERE t.menu_id = v_rec.master_id;
    END IF;

    -- B. Eksekusi ke Master berdasarkan temporary_option
    IF v_rec.temporary_option = 'D' THEN
        DELETE FROM core.menu WHERE menu_id = v_rec.master_id;
        v_final_pk := v_rec.master_id;
    ELSE
        v_final_pk := v_rec.menu_id;

        IF v_old_data IS NULL AND (v_final_pk IS NULL OR v_final_pk = '') THEN
            v_final_pk := core.get_next_sequence('MENU');
        END IF;


        -- INSERT atau UPDATE
        INSERT INTO core.menu (menu_id, menu_name, status, is_removed, created_at, updated_at)
        VALUES (v_final_pk, v_rec.menu_name, 'POSTED', v_rec.is_removed, NOW(), NOW())
        ON CONFLICT (menu_id) DO UPDATE SET
            menu_name = EXCLUDED.menu_name,
            is_removed = EXCLUDED.is_removed,
            status = 'POSTED',
            updated_at = NOW();
    END IF;

    -- C. History Logging
    SELECT to_jsonb(t) INTO v_new_data FROM core.menu t WHERE t.menu_id = v_final_pk;

    INSERT INTO history.core_menu (history_id, executed_by, action, old_data, new_data, executed_at)
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
    DELETE FROM temporary.core_menu WHERE temporary_id = v_temp_id;
END;
$$;