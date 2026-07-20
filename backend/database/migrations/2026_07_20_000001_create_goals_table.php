<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // minutes_per_week, sessions_per_week, streak_days
            $table->unsignedInteger('target_value');
            $table->unsignedInteger('current_value')->default(0);
            $table->string('status', 20)->default('active'); // active, completed, cancelled
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
