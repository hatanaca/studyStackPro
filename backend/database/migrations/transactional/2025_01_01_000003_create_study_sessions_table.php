<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('technology_id')->nullable()->nullOnDelete();
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->smallInteger('mood')->nullable();
            $table->smallInteger('focus_score')->nullable();
            $table->timestampsTz();
        });

        // duration_min calculado pelo banco: evita inconsistência e garante integridade
        // GENERATED STORED = valor persistido, não recalculado a cada SELECT
        DB::statement('
            ALTER TABLE study_sessions
            ADD COLUMN duration_min INTEGER GENERATED ALWAYS AS (
                CASE WHEN ended_at IS NOT NULL
                THEN EXTRACT(EPOCH FROM (ended_at - started_at))::INTEGER / 60
                ELSE NULL
                END
            ) STORED
        ');
        DB::statement('
            ALTER TABLE study_sessions
            ADD CONSTRAINT chk_session_end_after_start
            CHECK (ended_at IS NULL OR ended_at > started_at)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
