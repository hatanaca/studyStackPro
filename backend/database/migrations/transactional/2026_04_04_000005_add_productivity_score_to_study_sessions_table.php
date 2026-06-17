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
            CREATE OR REPLACE FUNCTION public.calculate_study_session_productivity_score()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.duration_min IS NULL THEN
                    NEW.productivity_score := 0;
                ELSIF NEW.duration_min < 15 THEN
                    NEW.productivity_score := FLOOR(NEW.duration_min * 0.5)::integer;
                ELSIF NEW.duration_min >= 90 THEN
                    NEW.productivity_score := FLOOR(NEW.duration_min * 1.3)::integer;
                ELSIF NEW.duration_min >= 45 THEN
                    NEW.productivity_score := FLOOR(NEW.duration_min * 1.1)::integer;
                ELSE
                    NEW.productivity_score := NEW.duration_min;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

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
