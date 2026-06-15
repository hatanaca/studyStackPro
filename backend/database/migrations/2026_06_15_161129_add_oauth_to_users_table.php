<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('discord_id')->nullable()->unique()->after('google_id');
            $table->text('discord_token')->nullable()->after('avatar_url');
            $table->text('discord_refresh_token')->nullable()->after('discord_token');
            $table->timestamp('discord_token_expires_at')->nullable()->after('discord_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id', 'discord_id',
                'discord_token', 'discord_refresh_token', 'discord_token_expires_at',
            ]);
        });
    }
};
