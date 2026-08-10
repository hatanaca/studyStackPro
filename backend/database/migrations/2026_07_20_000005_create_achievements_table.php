<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge_key', 50); // first_session, streak_7, streak_30, hours_100, etc.
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('icon', 20)->nullable(); // emoji
            $table->jsonb('metadata')->nullable(); // extra data like streak count, hours
            $table->timestampsTz();

            $table->unique(['user_id', 'badge_key']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
