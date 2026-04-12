<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->integer('productivity_score')->default(0);
        });

        DB::unprepared(<<<'SQL'
            CREATE EXTENSION IF NOT EXISTS pllua;

            CREATE OR REPLACE FUNCTION public.calculate_study_session_productivity_score()
            RETURNS TRIGGER AS $$
                if not new then
                    return
                end

                local ok = pcall(function()
                    local new_min = tonumber(new.duration_min) or 0
                    local weight = 1.0

                    if new_min < 15 then
                        weight = 0.5
                    elseif new_min >= 90 then
                        weight = 1.3
                    elseif new_min >= 45 then
                        weight = 1.1
                    end

                    new.productivity_score = math.floor(new_min * weight)
                end)

                if not ok then
                    new.productivity_score = tonumber(new.productivity_score) or 0
                end

                return new
            $$ LANGUAGE pllua;

            DROP TRIGGER IF EXISTS trg_study_session_productivity_score ON public.study_sessions;

            CREATE TRIGGER trg_study_session_productivity_score
                BEFORE INSERT OR UPDATE OF started_at, ended_at, duration_min ON public.study_sessions
                FOR EACH ROW
                EXECUTE FUNCTION public.calculate_study_session_productivity_score();

            UPDATE public.study_sessions
            SET productivity_score = CASE
                WHEN duration_min IS NULL THEN 0
                WHEN duration_min < 15 THEN FLOOR(duration_min * 0.5)::integer
                WHEN duration_min >= 90 THEN FLOOR(duration_min * 1.3)::integer
                WHEN duration_min >= 45 THEN FLOOR(duration_min * 1.1)::integer
                ELSE duration_min
            END;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS trg_study_session_productivity_score ON public.study_sessions;
            DROP FUNCTION IF EXISTS public.calculate_study_session_productivity_score();
        SQL);

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropColumn('productivity_score');
        });
    }
};
