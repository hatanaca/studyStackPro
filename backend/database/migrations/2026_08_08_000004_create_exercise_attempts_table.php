<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('variant_id')->constrained('exercise_variants')->cascadeOnDelete();
            $table->text('answer');
            $table->boolean('is_correct');
            $table->string('graded_by', 20)->default('sympy');
            $table->text('feedback_latex')->nullable();
            $table->text('expected_latex')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->index(['user_id', 'submitted_at']);
            $table->index(['variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_attempts');
    }
};
