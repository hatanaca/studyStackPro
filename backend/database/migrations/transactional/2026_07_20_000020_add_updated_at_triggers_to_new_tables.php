<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['goals', 'canvas_artworks', 'study_paths', 'notifications', 'achievements'];
        foreach ($tables as $table) {
            DB::statement("CREATE TRIGGER trg_{$table}_updated_at BEFORE UPDATE ON {$table} FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at()");
        }
    }

    public function down(): void
    {
        $tables = ['goals', 'canvas_artworks', 'study_paths', 'notifications', 'achievements'];
        foreach ($tables as $table) {
            DB::statement("DROP TRIGGER IF EXISTS trg_{$table}_updated_at ON {$table}");
        }
    }
};
