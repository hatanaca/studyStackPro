<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class TokenService
{
    public function revoke(PersonalAccessToken $token): void
    {
        $ttl = $this->resolveTtl($token);
        $hashedToken = hash('sha256', $token->token);

        try {
            Redis::setex("token_blacklist:{$hashedToken}", $ttl, '1');
        } catch (Throwable $exception) {
            Log::warning('Falha ao enviar token para blacklist Redis; exclusão seguirá em fail-open.', [
                'token_id' => $token->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }

        $token->delete();
    }

    /**
     * @param  iterable<int, PersonalAccessToken>  $tokens
     */
    public function revokeMany(iterable $tokens): int
    {
        $tokenList = $tokens instanceof \Traversable
            ? iterator_to_array($tokens)
            : (array) $tokens;

        if (empty($tokenList)) {
            return 0;
        }

        // Blacklist all tokens in Redis via pipeline (single round-trip)
        try {
            /** @var RedisManager $redis */
            $redis = app('redis');
            /** @var PhpRedisConnection $connection */
            $connection = $redis->connection();
            $connection->pipeline(function ($pipe) use ($tokenList) {
                foreach ($tokenList as $token) {
                    $ttl = $this->resolveTtl($token);
                    $hashedToken = hash('sha256', $token->token);
                    $pipe->setex("token_blacklist:{$hashedToken}", $ttl, '1');
                }
            });
        } catch (Throwable $exception) {
            Log::warning('Falha ao enviar tokens para blacklist Redis em pipeline; exclusão seguirá.', [
                'count' => count($tokenList),
                'error' => $exception->getMessage(),
            ]);
        }

        // Batch delete from database
        $ids = array_map(fn (PersonalAccessToken $t) => $t->getKey(), $tokenList);
        PersonalAccessToken::whereIn('id', $ids)->delete();

        return count($tokenList);
    }

    /**
     * Cria um novo token de API para o usuário, removendo tokens anteriores com o mesmo nome.
     *
     * Esse padrão de "revoga anterior, cria novo" é usado em register, login e OAuth complete.
     * Centralizar aqui garante consistência e evita duplicação.
     */
    public function createApiToken(User $user, string $tokenName = 'auth-token'): string
    {
        $user->tokens()->where('name', $tokenName)->delete();

        return $user->createToken($tokenName)->plainTextToken;
    }

    private function resolveTtl(PersonalAccessToken $token): int
    {
        if ($token->expires_at instanceof Carbon) {
            return max(now()->diffInSeconds($token->expires_at, false), 1);
        }

        // Tokens sem expiração: blacklist por 7 dias (padrão Sanctum é 1440 min).
        // 1 ano ocuparia memória Redis desnecessariamente.
        return 60 * 60 * 24 * 7;
    }
}
