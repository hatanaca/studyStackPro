<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flashcards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('deck_id')->constrained('flashcard_decks')->cascadeOnDelete();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->text('front_latex');
            $table->text('back_latex');
            // Snapshot do Card do ts-fsrs (JSON) — servidor é autoritativo, cliente calcula
            $table->jsonb('scheduling_state')->nullable();
            $table->string('fsrs_version', 20)->default('3');
            $table->timestamp('due_at');
            $table->timestamps();
            $table->index(['user_id', 'due_at']);
            $table->index(['deck_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcards');
    }
};
