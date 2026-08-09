<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Padroniza timestamps para timestampTz (consistente com users/study_sessions).
 * Tabelas: exercises, flashcards e ita-study (study_subjects/topics/sub_topics/questions).
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private array $tables = [
        'exercise_templates' => ['created_at', 'updated_at'],
        'exercise_variants' => ['created_at', 'updated_at'],
        'exercise_attempts' => ['created_at', 'updated_at'],
        'flashcard_decks' => ['created_at', 'updated_at'],
        'flashcards' => ['created_at', 'updated_at'],
        'flashcard_reviews' => ['created_at', 'updated_at'],
        'study_subjects' => ['created_at', 'updated_at'],
        'study_topics' => ['created_at', 'updated_at'],
        'study_sub_topics' => ['created_at', 'updated_at'],
        'study_questions' => ['created_at', 'updated_at'],
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->timestampTz($column)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->timestamp($column)->nullable()->change();
                }
            });
        }
    }
};
