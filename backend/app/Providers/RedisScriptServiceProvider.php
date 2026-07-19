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
            // Falha ao logar NUNCA deve quebrar o boot da aplicação.
            // Erros comuns: Redis indisponível, permissão de arquivo de log.
            try {
                Log::warning('Falha ao carregar scripts Lua do Redis no boot; seguindo em fail-open.', [
                    'error' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                // Silencia — o boot da aplicação não pode falhar por causa de log
            }
        }
    }
}
