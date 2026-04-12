<?php

namespace App\Modules\Auth\Services;

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

        try {
            Redis::setex("token_blacklist:{$token->token}", $ttl, '1');
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
            Redis::pipeline(function ($pipe) use ($tokenList) {
                foreach ($tokenList as $token) {
                    $ttl = $this->resolveTtl($token);
                    $pipe->setex("token_blacklist:{$token->token}", $ttl, '1');
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

    private function resolveTtl(PersonalAccessToken $token): int
    {
        if ($token->expires_at instanceof Carbon) {
            return max(now()->diffInSeconds($token->expires_at, false), 1);
        }

        return 60 * 60 * 24 * 365;
    }
}
