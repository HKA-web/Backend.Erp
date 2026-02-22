DROP PROCEDURE IF EXISTS core.procedure_action_company;
CREATE OR REPLACE PROCEDURE core.procedure_action_company(
    p_session_id UUID,
    p_payload JSONB
)
    LANGUAGE plpgsql
AS
$$
DECLARE
    v_status     TEXT;
    v_company_id TEXT;
    v_is_removed BOOLEAN;
BEGIN
    -- 1. Ambil data dasar dari payload
    v_status := COALESCE(p_payload ->> 'status', 'DRAFT');
    v_company_id := p_payload ->> 'company_id';

    -- Ambil flag is_removed, konversi Text ke Boolean secara eksplisit
    v_is_removed := COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE);

    -- 2. LOGIKA POSTED (COMMIT ACTION)
    IF v_status = 'POSTED' THEN
        -- Cek apakah data ada di temporary untuk session ini
        IF EXISTS (SELECT 1 FROM temporary.core_company WHERE company_id = v_company_id AND session_id = p_session_id) THEN

            INSERT INTO core.company (
                    company_id,
                    company_name,
                    status,
                    created_at,
                    updated_at,
                    is_removed
                )
                SELECT
                    company_id,
                    company_name,
                    'POSTED',
                    NOW(),
                    NOW(),
                    is_removed
                FROM temporary.core_company
                WHERE company_id = v_company_id AND session_id = p_session_id
                ON CONFLICT (company_id)
                    DO UPDATE SET
                                  company_name = EXCLUDED.company_name,
                                  status = EXCLUDED.status,
                                  is_removed = EXCLUDED.is_removed,
                                  updated_at = NOW();

            -- Setelah berhasil dipindahkan atau dihapus, bersihkan tabel temporary
            DELETE FROM temporary.core_company
            WHERE company_id = v_company_id AND session_id = p_session_id;

        ELSE
            -- Opsional: Jika user kirim POSTED tapi di temporary tidak ada datanya
            RAISE EXCEPTION 'ERROR PROCEDURE: Data not found in temporary for ID % in this session.', v_company_id;
        END IF;

    -- 3. LOGIKA EDIT (Salin Master ke Temp as Draft)
    ELSIF v_status = 'EDIT' THEN
        INSERT INTO temporary.core_company (company_id,
                                            company_name,
                                            status,
                                            session_id,
                                            is_removed)
        SELECT company_id,
               company_name,
               'DRAFT',
               p_session_id,
               FALSE -- Default False saat inisialisasi edit
        FROM core.company
        WHERE company_id = v_company_id
        ON CONFLICT (company_id) -- Gunakan composite key jika ada
            DO NOTHING;

    -- 4. LOGIKA DELETE (Salin Master ke Temp as Draft)
    ELSIF v_status = 'DELETE' THEN
        INSERT INTO temporary.core_company (company_id,
                                            company_name,
                                            status,
                                            session_id,
                                            is_removed)
        SELECT company_id,
               company_name,
               'DRAFT',
               p_session_id,
               TRUE -- Tandai akan dihapus
        FROM core.company
        WHERE company_id = v_company_id
        ON CONFLICT (company_id)
            DO UPDATE SET status = 'DELETED', is_removed = TRUE;

    -- 5. LOGIKA DRAFT / DEFAULT
    ELSE
        INSERT INTO temporary.core_company (company_id,
                                            company_name,
                                            status,
                                            session_id,
                                            is_removed)
        VALUES (v_company_id,
                p_payload ->> 'company_name',
                'DRAFT',
                p_session_id,
                v_is_removed) -- Menggunakan variabel yang sudah di-cast
        ON CONFLICT (company_id)
            DO UPDATE SET company_name = EXCLUDED.company_name,
                          status       = EXCLUDED.status,
                          is_removed   = EXCLUDED.is_removed;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        -- RAISE EXCEPTION akan membatalkan semua perubahan jika terjadi error
        RAISE EXCEPTION 'ERROR PROCEDURE: %', SQLERRM;
END;
$$;