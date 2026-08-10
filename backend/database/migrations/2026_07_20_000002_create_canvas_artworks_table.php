<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_artworks', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200)->default('Sem título');
            $table->jsonb('canvas_data')->nullable(); // Fabric.js JSON serialization
            $table->jsonb('mural_items')->nullable(); // Array of mural image URLs/metadata
            $table->unsignedInteger('width')->default(800);
            $table->unsignedInteger('height')->default(600);
            $table->string('bg_color', 20)->default('#ffffff');
            $table->timestampsTz();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_artworks');
    }
};
