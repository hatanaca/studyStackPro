<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // null = template global (seedado); usuário só altera os próprios
            $table->uuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('kind', 20)->default('numeric'); // numeric | symbolic
            $table->text('prompt'); // com placeholders {{param}} e LaTeX
            $table->jsonb('parameters_spec'); // nome -> {type, min, max, choices}
            $table->text('answer_expression'); // forma SymPy com params
            $table->text('solution_latex')->nullable();
            $table->jsonb('variables')->nullable(); // símbolos para equivalência simbólica
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->timestamps();
            $table->index(['user_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_templates');
    }
};
