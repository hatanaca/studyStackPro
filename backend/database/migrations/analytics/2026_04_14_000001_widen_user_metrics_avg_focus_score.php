<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * focus_score nas sessões pode ir de 1 a 10; a média pode ser 10,00.
 * NUMERIC(3,2) só permite valores abaixo de 10, causando overflow ao persistir métricas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE analytics.user_metrics ALTER COLUMN avg_focus_score TYPE NUMERIC(4,2)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE analytics.user_metrics ALTER COLUMN avg_focus_score TYPE NUMERIC(3,2)'
        );
    }
};
