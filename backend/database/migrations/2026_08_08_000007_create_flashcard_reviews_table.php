<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flashcard_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('flashcard_id')->constrained('flashcards')->cascadeOnDelete();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            // 1=Again, 2=Hard, 3=Good, 4=Easy (escala do FSRS)
            $table->unsignedTinyInteger('rating');
            $table->jsonb('state_before')->nullable();
            $table->jsonb('state_after');
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->index(['flashcard_id', 'reviewed_at']);
            $table->index(['user_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcard_reviews');
    }
};
