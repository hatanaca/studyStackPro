<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migração para criptografar tokens OAuth existentes em texto plano.
 *
 * Após esta migration, o cast 'encrypted' no model User cuida de
 * criptografar/decryptar automaticamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')
            ->whereNotNull('google_token')
            ->orWhereNotNull('google_refresh_token')
            ->orWhereNotNull('discord_token')
            ->orWhereNotNull('discord_refresh_token')
            ->get();

        foreach ($users as $user) {
            $updates = [];

            if ($user->google_token && ! $this->isAlreadyEncrypted($user->google_token)) {
                $updates['google_token'] = Crypt::encryptString($user->google_token);
            }
            if ($user->google_refresh_token && ! $this->isAlreadyEncrypted($user->google_refresh_token)) {
                $updates['google_refresh_token'] = Crypt::encryptString($user->google_refresh_token);
            }
            if ($user->discord_token && ! $this->isAlreadyEncrypted($user->discord_token)) {
                $updates['discord_token'] = Crypt::encryptString($user->discord_token);
            }
            if ($user->discord_refresh_token && ! $this->isAlreadyEncrypted($user->discord_refresh_token)) {
                $updates['discord_refresh_token'] = Crypt::encryptString($user->discord_refresh_token);
            }

            if (! empty($updates)) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        $users = DB::table('users')
            ->whereNotNull('google_token')
            ->orWhereNotNull('google_refresh_token')
            ->orWhereNotNull('discord_token')
            ->orWhereNotNull('discord_refresh_token')
            ->get();

        foreach ($users as $user) {
            $updates = [];

            if ($user->google_token && $this->isAlreadyEncrypted($user->google_token)) {
                $updates['google_token'] = Crypt::decryptString($user->google_token);
            }
            if ($user->google_refresh_token && $this->isAlreadyEncrypted($user->google_refresh_token)) {
                $updates['google_refresh_token'] = Crypt::decryptString($user->google_refresh_token);
            }
            if ($user->discord_token && $this->isAlreadyEncrypted($user->discord_token)) {
                $updates['discord_token'] = Crypt::decryptString($user->discord_token);
            }
            if ($user->discord_refresh_token && $this->isAlreadyEncrypted($user->discord_refresh_token)) {
                $updates['discord_refresh_token'] = Crypt::decryptString($user->discord_refresh_token);
            }

            if (! empty($updates)) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }
    }

    /**
     * Verifica se o valor já está criptografado pelo Laravel.
     * Valores criptografados começam com 'eyJ' (base64 do JSON do IV+payload).
     */
    private function isAlreadyEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);

        return $decoded !== false && str_starts_with($decoded, '{"iv":');
    }
};
