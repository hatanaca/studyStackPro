<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Constraints e índices conforme spec:
     * - technologies: uq_tech_user_name, CHECK color, idx_tech_user_id
     * - study_sessions: CHECK mood, focus_score, chk_session_min_duration, índices estratégicos
     */
    public function up(): void
    {
        // Technologies: UNIQUE (user_id, name) e CHECK color (spec)
        DB::statement('
            ALTER TABLE public.technologies
            ADD CONSTRAINT uq_tech_user_name UNIQUE (user_id, name)
        ');
        DB::statement("
            ALTER TABLE public.technologies
            ADD CONSTRAINT chk_tech_color CHECK (color ~ '^#[0-9A-Fa-f]{6}$')
        ");
        DB::statement('CREATE INDEX IF NOT EXISTS idx_tech_user_id ON public.technologies(user_id)');

        // Study_sessions: CHECK mood, focus_score, duração mínima
        DB::statement('
            ALTER TABLE public.study_sessions
            ADD CONSTRAINT chk_mood CHECK (mood IS NULL OR mood BETWEEN 1 AND 5)
        ');
        DB::statement('
            ALTER TABLE public.study_sessions
            ADD CONSTRAINT chk_focus_score CHECK (focus_score IS NULL OR focus_score BETWEEN 1 AND 10)
        ');
        DB::statement("
            ALTER TABLE public.study_sessions
            ADD CONSTRAINT chk_session_min_duration
            CHECK (ended_at IS NULL OR ended_at - started_at >= INTERVAL '1 minute')
        ");

        // Remover índices antigos e criar conforme spec
        DB::statement('DROP INDEX IF EXISTS study_sessions_user_started_idx');
        DB::statement('DROP INDEX IF EXISTS study_sessions_user_ended_idx');
        DB::statement('DROP INDEX IF EXISTS study_sessions_technology_id_idx');

        DB::statement('
            CREATE INDEX idx_sessions_user_date
            ON public.study_sessions(user_id, started_at DESC)
        ');
        DB::statement('
            CREATE INDEX idx_sessions_user_tech
            ON public.study_sessions(user_id, technology_id, started_at DESC)
        ');
        DB::statement('
            CREATE INDEX idx_sessions_in_progress
            ON public.study_sessions(user_id)
            WHERE ended_at IS NULL
        ');
        DB::statement('
            CREATE INDEX idx_sessions_started_brin
            ON public.study_sessions USING BRIN(started_at)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_sessions_started_brin');
        DB::statement('DROP INDEX IF EXISTS idx_sessions_in_progress');
        DB::statement('DROP INDEX IF EXISTS idx_sessions_user_tech');
        DB::statement('DROP INDEX IF EXISTS idx_sessions_user_date');

        DB::statement('ALTER TABLE public.study_sessions DROP CONSTRAINT IF EXISTS chk_session_min_duration');
        DB::statement('ALTER TABLE public.study_sessions DROP CONSTRAINT IF EXISTS chk_focus_score');
        DB::statement('ALTER TABLE public.study_sessions DROP CONSTRAINT IF EXISTS chk_mood');

        DB::statement('DROP INDEX IF EXISTS idx_tech_user_id');
        DB::statement('ALTER TABLE public.technologies DROP CONSTRAINT IF EXISTS chk_tech_color');
        DB::statement('ALTER TABLE public.technologies DROP CONSTRAINT IF EXISTS uq_tech_user_name');

        // Restaurar índices originais
        DB::statement('CREATE INDEX study_sessions_user_started_idx ON public.study_sessions (user_id, started_at DESC)');
        DB::statement('CREATE INDEX study_sessions_user_ended_idx ON public.study_sessions (user_id, ended_at)');
        DB::statement('CREATE INDEX study_sessions_technology_id_idx ON public.study_sessions (technology_id) WHERE technology_id IS NOT NULL');
    }
};
