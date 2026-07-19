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
 *
 * Nota: Acessa atributos OAuth (linkedin_id, tokens) via getAttributes()
 * para evitar MissingAttributeException quando o model é carregado
 * com select() parcial ou após serialização.
 */
class LinkedInService
{
    private const API_BASE = 'https://api.linkedin.com/v2';

    private const OAUTH_TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';

    /** Scope necessário para postar em nome do membro. */
    private const SHARE_SCOPE = 'w_member_social';

    /** Retorna atributo do model sem lançar MissingAttributeException. */
    private function attr(User $user, string $key): mixed
    {
        return $user->getAttributes()[$key] ?? null;
    }

    /**
     * Verifica se o usuário tem conta LinkedIn conectada.
     */
    public function isConnected(User $user): bool
    {
        return filled($this->attr($user, 'linkedin_id'));
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
        $token = $this->attr($user, 'linkedin_token');
        // Sem token não é possível obter perfil
        if (! $token) {
            throw new \RuntimeException('LinkedIn token não disponível.');
        }

        $response = Http::timeout(10)
            ->retry(2, 200)
            ->withToken($token)
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
        $linkedinId = $this->attr($user, 'linkedin_id');
        $token = $this->attr($user, 'linkedin_token');
        if (! $linkedinId || ! $token) {
            throw new \RuntimeException('LinkedIn não conectado — token ou ID ausente.');
        }

        $author = 'urn:li:person:'.$linkedinId;

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
        $refreshToken = $this->attr($user, 'linkedin_refresh_token');
        if (! $refreshToken) {
            throw new \RuntimeException('LinkedIn refresh token não disponível.');
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post(self::OAUTH_TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.linkedin.client_id'),
                'client_secret' => config('services.linkedin.client_secret'),
            ]);

        $response->throw();

        $data = $response->json();

        $user->update([
            'linkedin_token' => $data['access_token'],
            'linkedin_refresh_token' => $data['refresh_token'] ?? $this->attr($user, 'linkedin_refresh_token'),
            'linkedin_token_expires_at' => now()->addSeconds($data['expires_in']),
        ]);

        Log::info('LinkedIn token refreshed', ['user_id' => $user->id]);
    }
}
