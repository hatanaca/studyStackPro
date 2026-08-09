<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_study_reading_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('sub_topic_id')->constrained('study_sub_topics')->cascadeOnDelete();
            $table->decimal('progress', 5, 2)->default(0);
            $table->timestampTz('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'sub_topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_study_reading_progress');
    }
};
