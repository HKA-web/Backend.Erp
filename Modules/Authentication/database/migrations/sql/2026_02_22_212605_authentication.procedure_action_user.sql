DROP PROCEDURE IF EXISTS authentication.procedure_action_user;
CREATE OR REPLACE PROCEDURE authentication.procedure_action_user(
    p_session_id UUID,
    p_payload JSONB
)
    LANGUAGE plpgsql
AS
$$
DECLARE
    v_status     TEXT;
    v_user_id TEXT;
    v_is_removed BOOLEAN;
BEGIN
    -- 1. Ambil data dasar dari payload
    v_status := COALESCE(p_payload ->> 'status', 'DRAFT');
    v_user_id := p_payload ->> 'user_id';

    -- Ambil flag is_removed, konversi Text ke Boolean secara eksplisit
    v_is_removed := COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE);

    -- 2. LOGIKA POSTED (COMMIT ACTION)
    IF v_status = 'POSTED' THEN
        -- Cek apakah data ada di temporary untuk session ini
        IF EXISTS (SELECT 1 FROM temporary.authentication_user WHERE user_id = v_user_id AND session_id = p_session_id) THEN

            INSERT INTO authentication.user (
                user_id,
                user_name,
                email,
                email_verified_at,
                password,
                remember_token,
                status,
                created_at,
                updated_at,
                is_removed
            ) SELECT
                  user_id,
                  user_name,
                  email,
                  email_verified_at, -- Di temp sudah bertipe timestamp, jadi aman
                  password,
                  remember_token,
                  'POSTED',
                  NOW(),
                  NOW(),
                  is_removed
            FROM temporary.authentication_user
            WHERE user_id = v_user_id AND session_id = p_session_id
            ON CONFLICT (user_id)
                DO UPDATE SET
                              user_name           = EXCLUDED.user_name,
                              email               = EXCLUDED.email,
                              email_verified_at   = EXCLUDED.email_verified_at,
                              password            = EXCLUDED.password,
                              remember_token      = EXCLUDED.remember_token,
                              status              = EXCLUDED.status,
                              is_removed          = EXCLUDED.is_removed,
                              updated_at          = NOW();

            -- Setelah berhasil dipindahkan atau dihapus, bersihkan tabel temporary
            DELETE FROM temporary.authentication_user
            WHERE user_id = v_user_id AND session_id = p_session_id;

        ELSE
            -- Opsional: Jika user kirim POSTED tapi di temporary tidak ada datanya
            RAISE EXCEPTION 'ERROR PROCEDURE: Data not found in temporary for ID % in this session.', v_user_id;
        END IF;

        -- 3. LOGIKA EDIT (Salin Master ke Temp as Draft)
    ELSIF v_status = 'EDIT' THEN
        INSERT INTO temporary.authentication_user (
            user_id,
            user_name,
            email,
            email_verified_at, -- Tambahkan kolom ini agar data master tidak hilang di temp
            password,
            remember_token,
            status,
            session_id,
            is_removed
        )
        SELECT user_id,
               user_name,
               email,
               email_verified_at,
               password,
               remember_token,
               'DRAFT',
               p_session_id,
               FALSE
        FROM authentication.user
        WHERE user_id = v_user_id
        ON CONFLICT (user_id)
            DO NOTHING;

        -- 4. LOGIKA DELETE (Salin Master ke Temp as Draft)
    ELSIF v_status = 'DELETE' THEN
        INSERT INTO temporary.authentication_user (
            user_id,
            user_name,
            email,
            status,
            session_id,
            is_removed
        )
        SELECT user_id,
               user_name,
               email,
               'DRAFT',
               p_session_id,
               TRUE
        FROM authentication.user
        WHERE user_id = v_user_id
        ON CONFLICT (user_id)
            DO UPDATE SET is_removed = TRUE;

        -- 5. LOGIKA DRAFT / DEFAULT
    ELSE
        INSERT INTO temporary.authentication_user (
            user_id,
            user_name,
            email,
            email_verified_at, -- Lakukan casting di sini
            password,
            remember_token,
            status,
            session_id,
            is_removed
        )
        VALUES (
                   v_user_id,
                   p_payload ->> 'user_name',
                   p_payload ->> 'email',
                   (p_payload ->> 'email_verified_at')::timestamp, -- CASTING DI SINI
                   p_payload ->> 'password',
                   p_payload ->> 'remember_token',
                   'DRAFT',
                   p_session_id,
                   v_is_removed
               )
        ON CONFLICT (user_id)
            DO UPDATE SET user_name         = EXCLUDED.user_name,
                          email             = EXCLUDED.email,
                          email_verified_at = EXCLUDED.email_verified_at,
                          password          = EXCLUDED.password,
                          remember_token    = EXCLUDED.remember_token,
                          status            = EXCLUDED.status,
                          is_removed        = EXCLUDED.is_removed;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        -- RAISE EXCEPTION akan membatalkan semua perubahan jika terjadi error
        RAISE EXCEPTION 'ERROR PROCEDURE: %', SQLERRM;
END;
$$;
