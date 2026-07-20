<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_sessions_user_completed
            ON public.study_sessions (user_id, started_at DESC)
            WHERE ended_at IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_sessions_user_completed');
    }
};
