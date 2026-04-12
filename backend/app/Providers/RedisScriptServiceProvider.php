<?php

namespace App\Providers;

use App\Services\RedisLuaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

class RedisScriptServiceProvider extends ServiceProvider
{
    public function boot(RedisLuaService $redisLuaService): void
    {
        try {
            $redisLuaService->loadScripts();
        } catch (Throwable $exception) {
            Log::warning('Falha ao carregar scripts Lua do Redis no boot; seguindo em fail-open.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
