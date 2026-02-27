<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Índices para otimizar queries frequentes em study_sessions:
     * - listagem por usuário ordenada por started_at (findByUser)
     * - sessões ativas por usuário (whereNull ended_at)
     * - filtros por technology_id e range de datas
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS study_sessions_user_started_idx ON study_sessions (user_id, started_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS study_sessions_user_ended_idx ON study_sessions (user_id, ended_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS study_sessions_technology_id_idx ON study_sessions (technology_id) WHERE technology_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS study_sessions_user_started_idx');
        DB::statement('DROP INDEX IF EXISTS study_sessions_user_ended_idx');
        DB::statement('DROP INDEX IF EXISTS study_sessions_technology_id_idx');
    }
};
