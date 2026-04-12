<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Throwable;

class RedisLuaService
{
    /**
     * @return array<string, string>
     */
    public function scripts(): array
    {
        return [
            'job_dedup' => base_path('../redis-scripts/job_dedup.lua'),
            'sliding_window' => base_path('../redis-scripts/sliding_window.lua'),
            'streak_update' => base_path('../redis-scripts/streak_update.lua'),
        ];
    }

    public function loadScripts(): void
    {
        foreach ($this->scripts() as $name => $path) {
            $content = @file_get_contents($path);
            if ($content === false) {
                throw new RuntimeException("Não foi possível ler o script Lua [{$name}] em [{$path}].");
            }

            $sha = Redis::script('load', $content);
            Cache::forever($this->cacheKey($name), $sha);
        }
    }

    public function callScript(string $name, array $keys = [], array $args = []): mixed
    {
        $sha = Cache::get($this->cacheKey($name));
        $parameters = [...$keys, ...$args];

        if (! is_string($sha) || $sha === '') {
            $this->loadScripts();
            $sha = Cache::get($this->cacheKey($name));
        }

        try {
            return Redis::connection()->client()->evalsha($sha, $parameters, count($keys));
        } catch (Throwable $exception) {
            if (! $this->isNoScriptException($exception)) {
                throw $exception;
            }

            $this->loadScripts();

            $retrySha = Cache::get($this->cacheKey($name));
            if (! is_string($retrySha) || $retrySha === '') {
                throw new RuntimeException("SHA do script Lua [{$name}] não disponível após recarregamento.");
            }

            return Redis::connection()->client()->evalsha($retrySha, $parameters, count($keys));
        }
    }

    private function cacheKey(string $name): string
    {
        return "lua_sha:{$name}";
    }

    private function isNoScriptException(Throwable $exception): bool
    {
        return str_contains($exception->getMessage(), 'NOSCRIPT');
    }
}
