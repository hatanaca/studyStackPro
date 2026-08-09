<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // study_subjects — Matérias (Matemática, Física, etc.)
        Schema::create('study_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('icon', 50)->comment('PrimeIcons class');
            $table->string('color', 7)->comment('Hex color #RRGGBB');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // study_topics — Tópicos dentro de cada matéria
        Schema::create('study_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subject_id')->constrained('study_subjects')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('slug', 200);
            $table->string('difficulty', 20)->default('fundamental');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['subject_id', 'sort_order']);
        });

        // study_sub_topics — Sub-tópicos dentro de cada tópico
        Schema::create('study_sub_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('topic_id')->constrained('study_topics')->cascadeOnDelete();
            $table->string('name', 300);
            $table->string('slug', 300);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['topic_id', 'sort_order']);
        });

        // study_questions — Banco de questões por sub-tópico
        Schema::create('study_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sub_topic_id')->constrained('study_sub_topics')->cascadeOnDelete();
            $table->string('kind', 20)->default('numeric'); // numeric | symbolic | multiple_choice | true_false
            $table->text('prompt'); // enunciado com LaTeX {{param}}
            $table->jsonb('parameters_spec'); // nome -> {type, min, max, choices}
            $table->text('answer_expression'); // expressão SymPy para avaliação
            $table->string('answer_type', 20)->default('numeric'); // numeric | symbolic | choice
            $table->jsonb('choices_spec')->nullable(); // alternativas para multiple_choice
            $table->text('solution_latex')->nullable();
            $table->text('explanation')->nullable();
            $table->text('hint')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->boolean('has_graph')->default(false);
            $table->jsonb('graph_config')->nullable();
            $table->string('visual_type', 30)->default('none'); // none | function_plot | geometric | diagram | table
            $table->jsonb('visual_config')->nullable();
            $table->timestamps();
            $table->index(['sub_topic_id', 'difficulty']);
        });

        // study_question_variants — Variantes geradas de questões
        Schema::create('study_question_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id')->constrained('study_questions')->cascadeOnDelete();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seed');
            $table->jsonb('parameters');
            $table->text('prompt_resolved');
            $table->string('answer_value', 500);
            $table->jsonb('choices_resolved')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['user_id', 'question_id']);
        });

        // study_attempts — Tentativas do usuário
        Schema::create('study_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('variant_id')->constrained('study_question_variants')->cascadeOnDelete();
            $table->string('answer', 2000);
            $table->boolean('is_correct');
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
            $table->index(['variant_id']);
        });

        // user_sub_topic_progress — Progresso do usuário por sub-tópico
        Schema::create('user_sub_topic_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('sub_topic_id')->constrained('study_sub_topics')->cascadeOnDelete();
            $table->unsignedInteger('attempted')->default(0);
            $table->unsignedInteger('correct')->default(0);
            $table->boolean('mastered')->default(false);
            $table->timestampTz('last_attempt_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'sub_topic_id']);
            $table->index(['user_id', 'mastered']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sub_topic_progress');
        Schema::dropIfExists('study_attempts');
        Schema::dropIfExists('study_question_variants');
        Schema::dropIfExists('study_questions');
        Schema::dropIfExists('study_sub_topics');
        Schema::dropIfExists('study_topics');
        Schema::dropIfExists('study_subjects');
    }
};
