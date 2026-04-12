<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class StreakService
{
    public function __construct(
        private RedisLuaService $redisLuaService
    ) {}

    public function update(string $userId): int
    {
        $timezone = Cache::remember(
            "user_timezone:{$userId}",
            300,
            fn () => User::query()->whereKey($userId)->value('timezone') ?? 'UTC'
        );
        $today = Carbon::now($timezone)->toDateString();
        $yesterday = Carbon::now($timezone)->subDay()->toDateString();

        try {
            return (int) $this->redisLuaService->callScript('streak_update', [
                "streak:user:{$userId}",
                "streak:last_day:{$userId}",
            ], [
                $today,
                $yesterday,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Falha ao atualizar streak via Lua; retornando valor seguro.', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }
    }
}
