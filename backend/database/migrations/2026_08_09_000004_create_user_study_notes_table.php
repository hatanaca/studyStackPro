<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_study_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('sub_topic_id')->constrained('study_sub_topics')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
            $table->unique(['user_id', 'sub_topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_study_notes');
    }
};
