<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Garantir no máximo 1 sessão ativa (ended_at IS NULL) por usuário
        DB::statement('
            CREATE OR REPLACE FUNCTION check_concurrent_sessions()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.ended_at IS NULL THEN
                    IF EXISTS (
                        SELECT 1 FROM public.study_sessions
                        WHERE user_id = NEW.user_id
                        AND id != COALESCE(NEW.id, \'00000000-0000-0000-0000-000000000000\'::uuid)
                        AND ended_at IS NULL
                    ) THEN
                        RAISE EXCEPTION \'O usuário já possui uma sessão ativa. Finalize-a antes de iniciar outra.\';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        ');

        DB::statement('
            DROP TRIGGER IF EXISTS trigger_concurrent_sessions ON public.study_sessions
        ');
        DB::statement('
            CREATE TRIGGER trigger_concurrent_sessions
            BEFORE INSERT OR UPDATE ON public.study_sessions
            FOR EACH ROW EXECUTE FUNCTION check_concurrent_sessions()
        ');

        // validate_ended_at: garantia adicional (complementar ao CHECK existente)
        // O CHECK chk_session_end_after_start já valida ended_at > started_at.
        // Trigger adicional para mensagem de erro mais amigável (opcional).
        DB::statement('
            CREATE OR REPLACE FUNCTION validate_ended_at_trigger()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.ended_at IS NOT NULL AND NEW.ended_at <= NEW.started_at THEN
                    RAISE EXCEPTION \'ended_at deve ser posterior a started_at\';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        ');

        DB::statement('
            DROP TRIGGER IF EXISTS trigger_validate_ended_at ON public.study_sessions
        ');
        DB::statement('
            CREATE TRIGGER trigger_validate_ended_at
            BEFORE INSERT OR UPDATE ON public.study_sessions
            FOR EACH ROW EXECUTE FUNCTION validate_ended_at_trigger()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trigger_validate_ended_at ON public.study_sessions');
        DB::statement('DROP FUNCTION IF EXISTS validate_ended_at_trigger()');
        DB::statement('DROP TRIGGER IF EXISTS trigger_concurrent_sessions ON public.study_sessions');
        DB::statement('DROP FUNCTION IF EXISTS check_concurrent_sessions()');
    }
};
