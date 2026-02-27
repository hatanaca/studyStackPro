<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reestrutura schema analytics conforme spec:
     * - user_metrics: total_hours GENERATED, avg_session_min, longest/shortest, avg_mood, avg_focus_score, recalculated_at
     * - technology_metrics: id UUID, total_hours GENERATED, avg_session_min, percentage_total, first/last_studied_at
     * - daily_minutes: id UUID, study_date, session_count, technologies UUID[], avg_mood
     * - weekly_summaries: id UUID, week_number, year, active_days
     */
    public function up(): void
    {
        DB::statement('DROP TABLE IF EXISTS analytics.weekly_summaries');
        DB::statement('DROP TABLE IF EXISTS analytics.daily_minutes');
        DB::statement('DROP TABLE IF EXISTS analytics.technology_metrics');
        DB::statement('DROP TABLE IF EXISTS analytics.user_metrics');

        DB::statement('
            CREATE TABLE analytics.user_metrics (
                user_id UUID PRIMARY KEY REFERENCES public.users(id) ON DELETE CASCADE,
                total_sessions INTEGER NOT NULL DEFAULT 0,
                total_minutes INTEGER NOT NULL DEFAULT 0,
                total_hours NUMERIC(8,1) GENERATED ALWAYS AS (total_minutes / 60.0) STORED,
                avg_session_min NUMERIC(8,2) NOT NULL DEFAULT 0,
                longest_session_min INTEGER NOT NULL DEFAULT 0,
                shortest_session_min INTEGER NOT NULL DEFAULT 0,
                current_streak_days INTEGER NOT NULL DEFAULT 0,
                max_streak_days INTEGER NOT NULL DEFAULT 0,
                avg_mood NUMERIC(3,2),
                avg_focus_score NUMERIC(3,2),
                last_session_at TIMESTAMPTZ,
                recalculated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        ');

        DB::statement('
            CREATE TABLE analytics.technology_metrics (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
                technology_id UUID NOT NULL REFERENCES public.technologies(id) ON DELETE CASCADE,
                total_minutes INTEGER NOT NULL DEFAULT 0,
                total_hours NUMERIC(8,1) GENERATED ALWAYS AS (total_minutes / 60.0) STORED,
                session_count INTEGER NOT NULL DEFAULT 0,
                avg_session_min NUMERIC(8,2) NOT NULL DEFAULT 0,
                percentage_total NUMERIC(5,2) NOT NULL DEFAULT 0,
                first_studied_at TIMESTAMPTZ,
                last_studied_at TIMESTAMPTZ,
                recalculated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                CONSTRAINT uq_tech_metrics_user_tech UNIQUE (user_id, technology_id)
            )
        ');
        DB::statement('
            CREATE INDEX idx_tech_metrics_user_hours
            ON analytics.technology_metrics(user_id, total_minutes DESC)
        ');

        DB::statement('
            CREATE TABLE analytics.daily_minutes (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
                study_date DATE NOT NULL,
                total_minutes INTEGER NOT NULL DEFAULT 0,
                session_count INTEGER NOT NULL DEFAULT 0,
                technologies UUID[],
                avg_mood NUMERIC(3,2),
                recalculated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                CONSTRAINT uq_daily_user_date UNIQUE (user_id, study_date)
            )
        ');
        DB::statement('
            CREATE INDEX idx_daily_user_date
            ON analytics.daily_minutes(user_id, study_date DESC)
        ');
        DB::statement('
            CREATE INDEX idx_daily_date_brin
            ON analytics.daily_minutes USING BRIN(study_date)
        ');

        DB::statement('
            CREATE TABLE analytics.weekly_summaries (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
                week_start DATE NOT NULL,
                week_number SMALLINT NOT NULL,
                year SMALLINT NOT NULL,
                total_minutes INTEGER NOT NULL DEFAULT 0,
                session_count INTEGER NOT NULL DEFAULT 0,
                active_days SMALLINT NOT NULL DEFAULT 0,
                recalculated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                CONSTRAINT uq_weekly_user_week UNIQUE (user_id, week_start)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS analytics.weekly_summaries');
        DB::statement('DROP TABLE IF EXISTS analytics.daily_minutes');
        DB::statement('DROP TABLE IF EXISTS analytics.technology_metrics');
        DB::statement('DROP TABLE IF EXISTS analytics.user_metrics');

        // Restaurar estrutura anterior
        DB::statement('
            CREATE TABLE analytics.user_metrics (
                user_id UUID PRIMARY KEY,
                total_sessions BIGINT DEFAULT 0,
                total_minutes INTEGER DEFAULT 0,
                current_streak_days INTEGER DEFAULT 0,
                max_streak_days INTEGER DEFAULT 0,
                last_session_at TIMESTAMPTZ,
                updated_at TIMESTAMPTZ
            )
        ');
        DB::statement('
            CREATE TABLE analytics.technology_metrics (
                user_id UUID,
                technology_id UUID,
                total_minutes INTEGER DEFAULT 0,
                session_count BIGINT DEFAULT 0,
                last_used_at TIMESTAMPTZ,
                updated_at TIMESTAMPTZ,
                PRIMARY KEY (user_id, technology_id)
            )
        ');
        DB::statement('
            CREATE TABLE analytics.daily_minutes (
                user_id UUID,
                date DATE,
                total_minutes INTEGER DEFAULT 0,
                updated_at TIMESTAMPTZ,
                PRIMARY KEY (user_id, date)
            )
        ');
        DB::statement('
            CREATE TABLE analytics.weekly_summaries (
                user_id UUID,
                week_start DATE,
                total_minutes INTEGER DEFAULT 0,
                session_count BIGINT DEFAULT 0,
                technologies_used JSONB,
                updated_at TIMESTAMPTZ,
                PRIMARY KEY (user_id, week_start)
            )
        ');
    }
};
