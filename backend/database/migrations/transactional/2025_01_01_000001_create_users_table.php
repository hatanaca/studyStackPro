<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('timezone', 50)->default('UTC');
            $table->string('locale', 10)->default('pt_BR');
            $table->text('avatar_url')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
