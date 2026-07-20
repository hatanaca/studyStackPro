<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('technology_id')->nullable()->constrained('technologies')->nullOnDelete();
            $table->string('text', 500);
            $table->boolean('completed')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'completed']);
            $table->index(['user_id', 'technology_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
