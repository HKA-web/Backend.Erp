DROP PROCEDURE IF EXISTS core.procedure_action_dictionary;
CREATE OR REPLACE PROCEDURE core.procedure_action_dictionary(
    p_session_id UUID,
    p_payload JSONB
)
    LANGUAGE plpgsql
AS
$$
DECLARE
    v_existing_session_id TEXT;
    v_status        TEXT;
    v_dictionary_id TEXT;
    v_is_removed    BOOLEAN;
    v_old_data      JSONB;
    v_new_data      JSONB;
    v_user_id       UUID;
BEGIN
    -- Cek apakah data sudah ada di tabel temporary dengan session berbeda
    SELECT session_id INTO v_existing_session_id
    FROM temporary.core_dictionary
    WHERE dictionary_id = v_dictionary_id
    LIMIT 1;

    -- Ambil data dasar dari payload
    v_status := COALESCE(p_payload ->> 'status', 'DRAFT');
    v_dictionary_id := p_payload ->> 'dictionary_id';
    v_user_id := (p_payload ->> 'user_id')::UUID; -- Asumsi user_id dikirim di payload

    -- Ambil flag is_removed, konversi Text ke Boolean secara eksplisit
    v_is_removed := COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE);

    -- LOGIKA POSTED (COMMIT ACTION)
    IF v_status = 'POSTED' THEN
        -- Cek apakah data ada di temporary untuk session ini
        IF EXISTS (SELECT 1 FROM temporary.core_dictionary WHERE dictionary_id = v_dictionary_id AND session_id = p_session_id) THEN

            -- A. Ambil snapshot data lama dari Master (jika ada)
            SELECT to_jsonb(t) INTO v_old_data FROM core.dictionary t WHERE t.dictionary_id = v_dictionary_id;

            -- Jalankan UPSERT ke Master
            INSERT INTO core.dictionary (
                dictionary_id,
                company_id,
                dictionary_name,
                key,
                is_removed,
                created_at,
                updated_at,
                status
            )
            SELECT
                dictionary_id,
                company_id,
                dictionary_name,
                key,
                is_removed,
                NOW(),
                NOW(),
                'POSTED'
            FROM temporary.core_dictionary
            WHERE dictionary_id = v_dictionary_id AND session_id = p_session_id
            ON CONFLICT (dictionary_id)
                DO UPDATE SET
                              dictionary_name = EXCLUDED.dictionary_name,
                              status = EXCLUDED.status,
                              is_removed = EXCLUDED.is_removed,
                              updated_at = NOW();

            -- Ambil snapshot data baru setelah UPSERT
            SELECT to_jsonb(t) INTO v_new_data FROM core.dictionary t WHERE t.dictionary_id = v_dictionary_id;

            -- INSERT KE HISTORY
            INSERT INTO history.core_dictionary (
                history_id,
                executed_by,
                action,
                old_data,
                new_data,
                executed_at
            ) VALUES (
                         gen_random_uuid(),
                         v_user_id,
                         CASE
                             WHEN v_is_removed = TRUE THEN 'DELETE'
                             WHEN v_old_data IS NULL THEN 'INSERT'
                             ELSE 'UPDATE'
                             END,
                         v_old_data,
                         v_new_data,
                         NOW()
                     );

            -- Bersihkan tabel temporary
            DELETE FROM temporary.core_dictionary
            WHERE dictionary_id = v_dictionary_id AND session_id = p_session_id;

        ELSE
            RAISE EXCEPTION 'ERROR PROCEDURE: Data not found in temporary for ID % in this session.', v_dictionary_id;
        END IF;

        -- LOGIKA EDIT
    ELSIF v_status = 'EDIT' THEN
        IF v_existing_session_id IS NOT NULL AND v_existing_session_id <> p_session_id THEN
            -- Jika ada dan session-nya beda, kasih peringatan
            RAISE EXCEPTION 'Data already processed user: (Session: %)', v_existing_session_id;
        ELSE
            WITH source_data AS (
                SELECT *, p_session_id as session_id
                FROM core.dictionary
                WHERE dictionary_id = v_dictionary_id
            )
            INSERT INTO temporary.core_dictionary
            SELECT * FROM source_data
            ON CONFLICT (dictionary_id) DO NOTHING;

            -- Baru diupdate setelah masuk
            UPDATE temporary.core_dictionary
            SET is_removed = FALSE,
                status = 'DRAFT'
            WHERE dictionary_id = v_dictionary_id
              AND session_id = p_session_id;
        END IF;

        -- LOGIKA DELETE
    ELSIF v_status = 'DELETE' THEN
        IF v_existing_session_id IS NOT NULL AND v_existing_session_id <> p_session_id THEN
            -- Jika ada dan session-nya beda, kasih peringatan
            RAISE EXCEPTION 'Data already processed user: (Session: %)', v_existing_session_id;
        ELSE
            WITH source_data AS (
                SELECT *, p_session_id as session_id
                FROM core.dictionary
                WHERE dictionary_id = v_dictionary_id
            )
            INSERT INTO temporary.core_dictionary
            SELECT * FROM source_data
            ON CONFLICT (dictionary_id) DO NOTHING;

            -- Baru diupdate setelah masuk
            UPDATE temporary.core_dictionary
            SET is_removed = TRUE,
                status = 'DRAFT'
            WHERE dictionary_id = v_dictionary_id
              AND session_id = p_session_id;
        END IF;

        -- LOGIKA DRAFT / DEFAULT
    ELSE
        INSERT INTO temporary.core_dictionary (dictionary_id, dictionary_name, status, session_id, is_removed)
        VALUES (v_dictionary_id, p_payload ->> 'dictionary_name', 'DRAFT', p_session_id, v_is_removed)
        ON CONFLICT (dictionary_id)
            DO UPDATE SET dictionary_name = EXCLUDED.dictionary_name,
                          status       = EXCLUDED.status,
                          is_removed   = EXCLUDED.is_removed;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION '%', SQLERRM;
END;
$$;
