-- 1. PROCEDURE UPSERT DRAFT (Menggunakan temporary_id sebagai PK draf)
DROP PROCEDURE IF EXISTS core.procedure_upsert_sequence_draft;
CREATE OR REPLACE PROCEDURE core.procedure_upsert_sequence_draft(
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

    INSERT INTO temporary.core_sequence (
        temporary_id,
        session_id,
        master_id,
        temporary_option,
        sequence_id,
        sequence_name,
        prefix,
        suffix,
        padding,
        current_number,
        reset_type,
        last_reset_date,
        is_removed
    ) VALUES (
        v_temp_id,
        p_session_id,
        p_payload ->> 'master_id',
        COALESCE(p_payload ->> 'temporary_option', 'I'),
        p_payload ->> 'sequence_id',
        p_payload ->> 'sequence_name',
        p_payload ->> 'prefix',
        p_payload ->> 'suffix',
        COALESCE((p_payload ->> 'padding')::INTEGER, 4),
        COALESCE((p_payload ->> 'current_number')::INTEGER, 0),
        COALESCE(p_payload ->> 'reset_type', 'NONE'),
        (p_payload ->> 'last_reset_date')::DATE,
        COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
    ) ON CONFLICT (temporary_id) DO UPDATE SET
        sequence_name = EXCLUDED.sequence_name,
        prefix = EXCLUDED.prefix,
        suffix = EXCLUDED.suffix,
        padding = EXCLUDED.padding,
        current_number = EXCLUDED.current_number,
        reset_type = EXCLUDED.reset_type,
        last_reset_date = EXCLUDED.last_reset_date,
        is_removed = EXCLUDED.is_removed,
        temporary_option = EXCLUDED.temporary_option,
        updated_at = NOW();
END;
$$;

-- 2. PROCEDURE REVISE (Check-out dari Master ke Temporary)
DROP PROCEDURE IF EXISTS core.procedure_revise_sequence;
CREATE OR REPLACE PROCEDURE core.procedure_revise_sequence(
    p_session_id VARCHAR,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_master_id TEXT := p_payload ->> 'sequence_id';
BEGIN
    -- Validasi Locking (Logical): Jangan biarkan user lain edit data yang sama di temporary
    IF EXISTS (SELECT 1 FROM temporary.core_sequence WHERE master_id = v_master_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data ID % is currently being edited by another session.', v_master_id;
    END IF;

    INSERT INTO temporary.core_sequence (
        temporary_id,
        session_id,
        master_id,
        temporary_option,
        sequence_id,
        sequence_name,
        prefix,
        suffix,
        padding,
        current_number,
        reset_type,
        last_reset_date,
        is_removed
    )
    SELECT
        gen_random_uuid(),
        p_session_id,
        sequence_id,
        COALESCE(p_payload ->> 'temporary_option', 'U'),
        sequence_id,
        sequence_name,
        prefix,
        suffix,
        padding,
        current_number,
        reset_type,
        last_reset_date,
        COALESCE((p_payload ->> 'is_removed')::boolean, false)
    FROM core.sequence WHERE sequence_id = v_master_id
    ON CONFLICT (master_id, session_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master berdasarkan temporary_option)
DROP PROCEDURE IF EXISTS core.procedure_commit_sequence;
CREATE OR REPLACE PROCEDURE core.procedure_commit_sequence(
    p_session_id VARCHAR,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_temp_id VARCHAR := (p_payload ->> 'temporary_id');
    v_rec RECORD;
    v_old_data JSONB;
    v_new_data JSONB;
BEGIN
    -- Ambil data dari temporary
    SELECT * INTO v_rec FROM temporary.core_sequence
    WHERE temporary_id = v_temp_id AND session_id = p_session_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Draft record not found.';
    END IF;

    -- A. Snapshot Data Lama (Jika Update/Delete)
    IF v_rec.master_id IS NOT NULL THEN
        SELECT to_jsonb(t) INTO v_old_data FROM core.sequence t WHERE t.sequence_id = v_rec.master_id;
    END IF;

    -- B. Eksekusi ke Master berdasarkan temporary_option
    IF v_rec.temporary_option = 'D' THEN
        DELETE FROM core.sequence WHERE sequence_id = v_rec.master_id;
    ELSE
        -- INSERT atau UPDATE
        INSERT INTO core.sequence (
            sequence_id, sequence_name, prefix, suffix, padding, current_number, reset_type, last_reset_date, status, is_removed, created_at, updated_at
        )
        VALUES (
            v_rec.sequence_id, v_rec.sequence_name, v_rec.prefix, v_rec.suffix, v_rec.padding, v_rec.current_number, v_rec.reset_type, v_rec.last_reset_date, 'POSTED', v_rec.is_removed, NOW(), NOW()
        )
        ON CONFLICT (sequence_id) DO UPDATE SET
            sequence_name = EXCLUDED.sequence_name,
            prefix = EXCLUDED.prefix,
            suffix = EXCLUDED.suffix,
            padding = EXCLUDED.padding,
            current_number = EXCLUDED.current_number,
            reset_type = EXCLUDED.reset_type,
            last_reset_date = EXCLUDED.last_reset_date,
            is_removed = EXCLUDED.is_removed,
            status = 'POSTED',
            updated_at = NOW();
    END IF;

    -- C. History Logging
    SELECT to_jsonb(t) INTO v_new_data FROM core.sequence t WHERE t.sequence_id = v_rec.sequence_id;

    INSERT INTO history.core_sequence (history_id, executed_by, action, old_data, new_data, executed_at)
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
    DELETE FROM temporary.core_sequence WHERE temporary_id = v_temp_id;
END;
$$;

-- 4. FUNCTION GET NEXT SEQUENCE (Dipanggil saat butuh nomor urut)
DROP FUNCTION IF EXISTS core.get_next_sequence;
CREATE OR REPLACE FUNCTION core.get_next_sequence(
    p_sequence_name VARCHAR,
    p_date DATE DEFAULT CURRENT_DATE
) RETURNS VARCHAR LANGUAGE plpgsql AS $$
DECLARE
    v_prefix VARCHAR;
    v_suffix VARCHAR;
    v_padding INT;
    v_current_number INT;
    v_reset_type VARCHAR;
    v_last_reset_date DATE;
    v_result VARCHAR;
    v_new_number INT;
BEGIN
    -- Select dan lock baris data
    SELECT prefix, suffix, padding, current_number, reset_type, last_reset_date
    INTO v_prefix, v_suffix, v_padding, v_current_number, v_reset_type, v_last_reset_date
    FROM core.sequence
    WHERE sequence_name = p_sequence_name AND is_removed = FALSE
    FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Sequence % not found or removed', p_sequence_name;
    END IF;

    -- Periksa reset_type
    IF v_reset_type = 'YEARLY' AND (v_last_reset_date IS NULL OR EXTRACT(YEAR FROM v_last_reset_date) <> EXTRACT(YEAR FROM p_date)) THEN
        v_current_number := 0;
    ELSIF v_reset_type = 'MONTHLY' AND (v_last_reset_date IS NULL OR TO_CHAR(v_last_reset_date, 'YYYY-MM') <> TO_CHAR(p_date, 'YYYY-MM')) THEN
        v_current_number := 0;
    ELSIF v_reset_type = 'DAILY' AND (v_last_reset_date IS NULL OR v_last_reset_date <> p_date) THEN
        v_current_number := 0;
    END IF;

    -- Tambah 1
    v_new_number := v_current_number + 1;

    -- Update sequence
    UPDATE core.sequence
    SET current_number = v_new_number,
        last_reset_date = p_date
    WHERE sequence_name = p_sequence_name;

    -- Ganti variabel pada prefix dan suffix
    v_prefix := REPLACE(v_prefix, '{YYYY}', TO_CHAR(p_date, 'YYYY'));
    v_prefix := REPLACE(v_prefix, '{YY}', TO_CHAR(p_date, 'YY'));
    v_prefix := REPLACE(v_prefix, '{MM}', TO_CHAR(p_date, 'MM'));
    v_prefix := REPLACE(v_prefix, '{DD}', TO_CHAR(p_date, 'DD'));

    v_suffix := REPLACE(v_suffix, '{YYYY}', TO_CHAR(p_date, 'YYYY'));
    v_suffix := REPLACE(v_suffix, '{YY}', TO_CHAR(p_date, 'YY'));
    v_suffix := REPLACE(v_suffix, '{MM}', TO_CHAR(p_date, 'MM'));
    v_suffix := REPLACE(v_suffix, '{DD}', TO_CHAR(p_date, 'DD'));

    -- Rangkai nomor (Prefix + Padded Number + Suffix)
    v_result := COALESCE(v_prefix, '') || LPAD(v_new_number::VARCHAR, v_padding, '0') || COALESCE(v_suffix, '');

    RETURN v_result;
END;
$$;
