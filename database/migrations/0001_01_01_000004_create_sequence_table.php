<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequence', function (Blueprint $table) {
            $table->string('seq_key', 50)->primary();
            $table->string('prefix', 20)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->unsignedBigInteger('current_number')->default(0);
            $table->date('last_reset_at')->default(DB::raw('CURRENT_DATE'));
            $table->boolean('reset_daily')->default(true);
            $table->timestamp('updated_at')->useCurrent();
        });
        DB::statement("
            CREATE OR REPLACE FUNCTION pr_generate_number(
                p_seq_key VARCHAR,
                p_date DATE DEFAULT CURRENT_DATE
            )
            RETURNS VARCHAR
            LANGUAGE plpgsql
            AS $$
            DECLARE
                v_prefix VARCHAR;
                v_suffix VARCHAR;
                v_current BIGINT;
                v_last_reset DATE;
                v_reset_daily BOOLEAN;
                v_next BIGINT;
                v_result VARCHAR;
            BEGIN
                -- lock row biar aman dari race condition
                SELECT prefix, suffix, current_number, last_reset_at, reset_daily
                INTO v_prefix, v_suffix, v_current, v_last_reset, v_reset_daily
                FROM sequence
                WHERE seq_key = p_seq_key
                FOR UPDATE;

                IF NOT FOUND THEN
                    RAISE EXCEPTION 'Sequence % not found', p_seq_key;
                END IF;

                -- reset harian
                IF v_reset_daily AND v_last_reset <> p_date THEN
                    v_current := 0;
                    UPDATE sequence
                    SET current_number = 0,
                        last_reset_at = p_date;
                END IF;

                v_next := v_current + 1;

                UPDATE sequence
                SET current_number = v_next,
                    updated_at = now()
                WHERE seq_key = p_seq_key;

                v_result :=
                    COALESCE(v_prefix,'') || '-' ||
                    to_char(p_date, 'YYYYMMDD') || '-' ||
                    LPAD(v_next::text, 6, '0') ||
                    COALESCE('-' || v_suffix, '');

                RETURN v_result;
            END;
            $$;
        ");

        DB::statement("
            CREATE OR REPLACE FUNCTION trg_set_pk_from_sequence()
                RETURNS TRIGGER
                LANGUAGE plpgsql
            AS $$
            DECLARE
                v_seq_key TEXT := TG_ARGV[0];
                v_column  TEXT := TG_ARGV[1];
                v_value   TEXT;
                v_json    JSONB;
            BEGIN
                v_value := pr_generate_number(v_seq_key);

                v_json := to_jsonb(NEW);
                v_json := jsonb_set(v_json, ARRAY[v_column], to_jsonb(v_value), true);
                NEW := jsonb_populate_record(NEW, v_json);

                RETURN NEW;
            END;
            $$;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP FUNCTION IF EXISTS trg_set_pk_from_sequence();");

        DB::statement("DROP FUNCTION IF EXISTS pr_generate_number(VARCHAR, DATE);");

        Schema::dropIfExists('sequence');
    }
};
