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
     * @return User
     */
    public function handleOAuthUser(SocialiteUser $socialUser, string $provider): User
    {
        $providerId = $provider . '_id';

        $user = User::where($providerId, $socialUser->getId())
            ->orWhere('email', $socialUser->getEmail())
            ->first();

        if (! $user) {
            $user = User::create([
                'name'       => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email'      => $socialUser->getEmail(),
                $providerId  => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
                'password'   => bcrypt(Str::random(32)),
            ]);
        } else {
            $user->update([
                $providerId  => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar() ?? $user->avatar_url,
            ]);
        }

        if ($provider === 'discord') {
            $user->update([
                'discord_token'            => $socialUser->token,
                'discord_refresh_token'    => $socialUser->refreshToken,
                'discord_token_expires_at' => $socialUser->expiresIn
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);
        }

        return $user->fresh();
    }
}
