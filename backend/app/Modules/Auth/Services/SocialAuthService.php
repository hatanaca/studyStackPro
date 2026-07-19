<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    public function handleOAuthUser(SocialiteUser $socialUser, string $provider): User
    {
        $providerId = $provider.'_id';
        $email = $socialUser->getEmail();

        // Discord pode não retornar email para contas sem email verificado.
        // Gera um placeholder para evitar NOT NULL violation no banco.
        if (! $email) {
            $email = $provider.'_'.$socialUser->getId().'@placeholder.local';
        }

        $user = User::where($providerId, $socialUser->getId())->first();
        if (! $user) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $user = User::forceCreate([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $email,
                $providerId => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
                'password' => Str::random(60),
            ]);
        }

        // Preparar dados para atualização em uma única query
        $updateData = [];
        if (! $user->{$providerId}) {
            $updateData[$providerId] = $socialUser->getId();
        }
        $updateData['avatar_url'] = $socialUser->getAvatar() ?? $user->avatar_url;

        // Tokens OAuth
        $tokenFields = [
            'discord' => ['discord_token', 'discord_refresh_token', 'discord_token_expires_at'],
            'google' => ['google_token', 'google_refresh_token', 'google_token_expires_at'],
            'linkedin' => ['linkedin_token', 'linkedin_refresh_token', 'linkedin_token_expires_at'],
        ];

        if (isset($tokenFields[$provider])) {
            [$tokenKey, $refreshKey, $expiresKey] = $tokenFields[$provider];
            $updateData[$tokenKey] = $socialUser->token;
            $updateData[$refreshKey] = $socialUser->refreshToken;
            $updateData[$expiresKey] = $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null;
        }

        $user->forceFill($updateData)->save();

        return $user->fresh();
    }
}
