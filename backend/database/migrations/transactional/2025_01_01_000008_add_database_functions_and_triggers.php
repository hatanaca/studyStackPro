<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Funções e triggers conforme spec:
     * - fn_set_updated_at: trigger universal para updated_at
     * - fn_generate_slug: geração de slug (fallback)
     * - Triggers em users, technologies, study_sessions
     */
    public function up(): void
    {
        // Função universal para auto-atualizar updated_at
        DB::statement('
            CREATE OR REPLACE FUNCTION fn_set_updated_at()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        ');

        // Função de geração de slug
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_generate_slug(p_input TEXT)
            RETURNS TEXT AS \$\$
            DECLARE
                v_slug TEXT;
            BEGIN
                v_slug := LOWER(TRIM(p_input));
                v_slug := translate(v_slug, 'áàâãäéèêëíìîïóòôõöúùûüçñ', 'aaaaaeeeeiiiiooooouuuucn');
                v_slug := regexp_replace(v_slug, '[^a-z0-9]+', '-', 'g');
                v_slug := trim(both '-' from v_slug);
                RETURN v_slug;
            END;
            \$\$ LANGUAGE plpgsql IMMUTABLE STRICT
        ");

        // Trigger users
        DB::statement('DROP TRIGGER IF EXISTS trg_users_updated_at ON public.users');
        DB::statement('
            CREATE TRIGGER trg_users_updated_at
            BEFORE UPDATE ON public.users
            FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at()
        ');

        // Índice explícito para email (login) - unique já cria índice, mas spec pede idx_users_email
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_email ON public.users(email)');

        // Trigger technologies
        DB::statement('DROP TRIGGER IF EXISTS trg_technologies_updated_at ON public.technologies');
        DB::statement('
            CREATE TRIGGER trg_technologies_updated_at
            BEFORE UPDATE ON public.technologies
            FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at()
        ');

        // Trigger study_sessions
        DB::statement('DROP TRIGGER IF EXISTS trg_sessions_updated_at ON public.study_sessions');
        DB::statement('
            CREATE TRIGGER trg_sessions_updated_at
            BEFORE UPDATE ON public.study_sessions
            FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_sessions_updated_at ON public.study_sessions');
        DB::statement('DROP TRIGGER IF EXISTS trg_technologies_updated_at ON public.technologies');
        DB::statement('DROP TRIGGER IF EXISTS trg_users_updated_at ON public.users');
        DB::statement('DROP INDEX IF EXISTS idx_users_email');
        DB::statement('DROP FUNCTION IF EXISTS fn_generate_slug(TEXT)');
        DB::statement('DROP FUNCTION IF EXISTS fn_set_updated_at()');
    }
};
