<?php

namespace App\Modules\Goals\Repositories;

use App\Models\Goal;
use App\Modules\Goals\DTOs\GoalDTO;
use App\Modules\Goals\Repositories\Contracts\GoalRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EloquentGoalRepository implements GoalRepositoryInterface
{
    private const CACHE_TTL_MINUTES = 5;

    public function listForUser(string $userId, ?string $status = null): Collection
    {
        $cacheKey = "goals:list:{$userId}:".($status ?? 'all');

        return Cache::tags(['goals', "goals:user:{$userId}"])->remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($userId, $status) {
                $query = Goal::where('user_id', $userId)->orderByDesc('created_at');
                if ($status) {
                    $query->where('status', $status);
                }

                return $query->get();
            }
        );
    }

    public function findForUser(string $id, string $userId): Goal
    {
        $goal = Goal::find($id);
        if (! $goal) {
            throw (new ModelNotFoundException)->setModel(Goal::class, $id);
        }
        if ($goal->user_id !== $userId) {
            throw new AuthorizationException('Acesso negado a este recurso.');
        }

        return $goal;
    }

    public function create(GoalDTO $dto): Goal
    {
        $goal = Goal::forceCreate([
            'user_id' => $dto->userId,
            'type' => $dto->type,
            'target_value' => $dto->targetValue,
            'current_value' => 0,
            'status' => 'active',
            'start_date' => $dto->startDate,
            'end_date' => $dto->endDate,
            'meta' => $dto->meta,
        ]);
        $this->invalidateCacheForUser($dto->userId);

        return $goal;
    }

    public function update(Goal $goal, array $data): Goal
    {
        $goal->update($data);
        $this->invalidateCacheForUser($goal->user_id);

        return $goal->fresh();
    }

    public function delete(Goal $goal): void
    {
        $userId = $goal->user_id;
        $goal->delete();
        $this->invalidateCacheForUser($userId);
    }

    public function invalidateCacheForUser(string $userId): void
    {
        Cache::tags(['goals', "goals:user:{$userId}"])->flush();
    }
}
