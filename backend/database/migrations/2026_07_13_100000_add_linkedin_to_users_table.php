<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('linkedin_id')->nullable()->unique()->after('discord_id');
            $table->text('linkedin_token')->nullable()->after('google_token_expires_at');
            $table->text('linkedin_refresh_token')->nullable()->after('linkedin_token');
            $table->timestamp('linkedin_token_expires_at')->nullable()->after('linkedin_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'linkedin_id',
                'linkedin_token', 'linkedin_refresh_token', 'linkedin_token_expires_at',
            ]);
        });
    }
};
