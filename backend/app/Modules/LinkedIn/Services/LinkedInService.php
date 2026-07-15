<?php

namespace App\Modules\LinkedIn\Services;

use App\Models\User;
use App\Modules\LinkedIn\DTOs\LinkedInPostDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para integração com a LinkedIn API v2.
 *
 * Gerencia publicação de posts (ugcPosts), perfil do usuário,
 * e renovação de access tokens via refresh token.
 */
class LinkedInService
{
    private const API_BASE = 'https://api.linkedin.com/v2';

    private const OAUTH_TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';

    /** Scope necessário para postar em nome do membro. */
    private const SHARE_SCOPE = 'w_member_social';

    /**
     * Verifica se o usuário tem conta LinkedIn conectada.
     */
    public function isConnected(User $user): bool
    {
        return filled($user->linkedin_id);
    }

    /**
     * Obtém o perfil básico do usuário no LinkedIn.
     *
     * @return array{id: string, localizedFirstName: string, localizedLastName: string}
     *
     * @throws RequestException|ConnectionException
     */
    public function getProfile(User $user): array
    {
        $response = Http::timeout(10)
            ->retry(2, 200)
            ->withToken($user->linkedin_token)
            ->get(self::API_BASE.'/me');

        $response->throw();

        return $response->json();
    }

    /**
     * Publica um post no LinkedIn usando a UGC Posts API.
     *
     * @return array{id: string}
     *
     * @throws RequestException|ConnectionException
     */
    public function sharePost(User $user, LinkedInPostDTO $dto): array
    {
        $author = 'urn:li:person:'.$user->linkedin_id;

        $payload = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $dto->text,
                    ],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = Http::timeout(15)
            ->retry(2, 200)
            ->withToken($user->linkedin_token)
            ->withHeaders([
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->post(self::API_BASE.'/ugcPosts', $payload);

        $response->throw();

        Log::info('LinkedIn post shared', [
            'user_id' => $user->id,
            'post_id' => $response->json('id'),
        ]);

        return $response->json();
    }

    /**
     * Renova o access token usando o refresh token.
     *
     * @throws RequestException|ConnectionException
     */
    public function refreshToken(User $user): void
    {
        $response = Http::asForm()
            ->timeout(10)
            ->post(self::OAUTH_TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $user->linkedin_refresh_token,
                'client_id' => config('services.linkedin.client_id'),
                'client_secret' => config('services.linkedin.client_secret'),
            ]);

        $response->throw();

        $data = $response->json();

        $user->update([
            'linkedin_token' => $data['access_token'],
            'linkedin_refresh_token' => $data['refresh_token'] ?? $user->linkedin_refresh_token,
            'linkedin_token_expires_at' => now()->addSeconds($data['expires_in']),
        ]);

        Log::info('LinkedIn token refreshed', ['user_id' => $user->id]);
    }
}
