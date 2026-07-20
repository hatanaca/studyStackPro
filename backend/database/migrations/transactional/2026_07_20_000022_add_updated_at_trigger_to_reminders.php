<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TRIGGER trg_reminders_updated_at BEFORE UPDATE ON reminders FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at()');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_reminders_updated_at ON reminders');
    }
};
