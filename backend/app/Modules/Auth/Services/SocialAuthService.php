<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Serviço de autenticação OAuth (Google, Discord).
 *
 * Gerencia upsert de usuário por provider e retorna o model User.
 * A sessão web (cookie) é iniciada pelo controller (Sanctum SPA).
 */
class SocialAuthService
{
    /**
     * Processa o callback OAuth: cria ou atualiza o usuário.
     *
     * Prioridade de busca:
     * 1. provider_id (usuário já fez login com este provider antes)
     * 2. email (usuário já existe com este email)
     *
     * Se o email já existe mas com outro provider, vincula o provider ao usuário existente.
     */
    public function handleOAuthUser(SocialiteUser $socialUser, string $provider): User
    {
        $providerId = $provider.'_id';
        $email = $socialUser->getEmail();

        // 1. Busca por provider_id primeiro (usuário já fez login com este provider)
        $user = User::where($providerId, $socialUser->getId())->first();

        // 2. Se não encontrou, busca por email (usuário já existe com este email)
        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            // Cria novo usuário
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $email,
                $providerId => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
                'password' => bcrypt(Str::random(32)),
            ]);
        } else {
            // Atualiza o provider_id se não estiver vinculado
            if (! $user->{$providerId}) {
                $user->update([
                    $providerId => $socialUser->getId(),
                    'avatar_url' => $socialUser->getAvatar() ?? $user->avatar_url,
                ]);
            } else {
                // Apenas atualiza avatar se necessário
                $user->update([
                    'avatar_url' => $socialUser->getAvatar() ?? $user->avatar_url,
                ]);
            }
        }

        if ($provider === 'discord') {
            $user->update([
                'discord_token' => $socialUser->token,
                'discord_refresh_token' => $socialUser->refreshToken,
                'discord_token_expires_at' => $socialUser->expiresIn
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);
        }

        if ($provider === 'google') {
            $user->update([
                'google_token' => $socialUser->token,
                'google_refresh_token' => $socialUser->refreshToken,
                'google_token_expires_at' => $socialUser->expiresIn
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);
        }

        if ($provider === 'linkedin') {
            $user->update([
                'linkedin_token' => $socialUser->token,
                'linkedin_refresh_token' => $socialUser->refreshToken,
                'linkedin_token_expires_at' => $socialUser->expiresIn
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);
        }

        return $user->fresh();
    }
}
