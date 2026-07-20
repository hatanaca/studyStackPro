<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_paths', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('technology_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200)->default('Mapa de Estudo');
            $table->jsonb('nodes')->nullable(); // Vue Flow nodes array
            $table->jsonb('edges')->nullable(); // Vue Flow edges array
            $table->timestampsTz();

            $table->index(['user_id', 'technology_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_paths');
    }
};
