<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sessões curtas iniciadas e encerradas pelo timer são válidas.
        // A integridade temporal já é garantida por ended_at > started_at.
        DB::statement('ALTER TABLE public.study_sessions DROP CONSTRAINT IF EXISTS chk_session_min_duration');
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE public.study_sessions
            ADD CONSTRAINT chk_session_min_duration
            CHECK (ended_at IS NULL OR ended_at - started_at >= INTERVAL '1 minute')
        ");
    }
};
