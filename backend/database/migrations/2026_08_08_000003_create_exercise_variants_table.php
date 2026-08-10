<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id')->constrained('exercise_templates')->cascadeOnDelete();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('seed')->nullable();
            $table->jsonb('parameters'); // valores materializados da variante
            $table->text('prompt_latex'); // prompt com params preenchidos
            $table->text('answer_expr'); // resposta correta com params preenchidos
            $table->text('solution_latex')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_variants');
    }
};
