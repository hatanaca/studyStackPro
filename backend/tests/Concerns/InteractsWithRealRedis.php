<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\SkippedWithMessageException;

/**
 * Redis em testes: o .env pode apontar para Docker (host/senha) enquanto o phpunit força 127.0.0.1.
 * Integração Lua: o probe tem de usar o mesmo script e aridade que os testes (EVAL curto não basta).
 */
trait InteractsWithRealRedis
{
    protected function skipUnlessRedisPingReachable(): void
    {
        try {
            Redis::connection()->command('ping');
        } catch (SkippedWithMessageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->abortOrSkipRedis($e);
        }
    }

    /**
     * Executa um probe real (ex.: primeiro EVAL com o script carregado). Em CI falha em vez de saltar.
     */
    protected function skipUnlessRedisIntegrationProbe(\Closure $probe): void
    {
        try {
            $probe();
        } catch (SkippedWithMessageException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->abortOrSkipRedis($e);
        }
    }

    private function abortOrSkipRedis(\Throwable $e): void
    {
        if ($this->mustRequireRedisInThisEnvironment()) {
            $this->fail('Ambiente CI: Redis tem de estar acessível. '.$e->getMessage());
        }

        $this->markTestSkipped('Redis indisponível para integração: '.$e->getMessage());
    }

    private function mustRequireRedisInThisEnvironment(): bool
    {
        $ci = getenv('CI');
        $gha = getenv('GITHUB_ACTIONS');

        return ($ci !== false && $ci !== '' && $ci !== 'false')
            || ($gha !== false && $gha !== '' && $gha !== 'false');
    }
}
