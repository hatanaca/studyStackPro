<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS analytics');
        DB::statement("COMMENT ON SCHEMA analytics IS 'Dados pré-calculados para o dashboard'");
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS analytics CASCADE');
    }
};
