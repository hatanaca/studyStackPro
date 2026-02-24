<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS analytics.weekly_summaries');
        DB::statement('DROP TABLE IF EXISTS analytics.daily_minutes');
        DB::statement('DROP TABLE IF EXISTS analytics.technology_metrics');
        DB::statement('DROP TABLE IF EXISTS analytics.user_metrics');
    }
};
