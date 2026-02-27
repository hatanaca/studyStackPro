<?php

namespace App\Modules\Technologies\Repositories;

use App\Models\Technology;
use App\Modules\Technologies\DTOs\TechnologyDTO;
use App\Modules\Technologies\Repositories\Contracts\TechnologyRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EloquentTechnologyRepository implements TechnologyRepositoryInterface
{
    private const CACHE_TTL_MINUTES = 5;

    public function listForUser(string $userId, bool $activeOnly = true): Collection
    {
        $cacheKey = "technologies:list:{$userId}:".($activeOnly ? 'active' : 'all');

        return Cache::tags(['technologies', "user:{$userId}"])->remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($userId, $activeOnly) {
                $query = Technology::where('user_id', $userId)->orderBy('name');
                if ($activeOnly) {
                    $query->where('is_active', true);
                }

                return $query->get();
            }
        );
    }

    public function search(string $userId, string $query, int $limit = 10): Collection
    {
        $q = trim($query);
        if (strlen($q) < 2) {
            return collect();
        }

        return Technology::where('user_id', $userId)
            ->where('is_active', true)
            ->where('name', 'ilike', "%{$q}%")
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function findForUser(string $id, string $userId): Technology
    {
        $tech = Technology::find($id);
        if (! $tech) {
            throw (new ModelNotFoundException)->setModel(Technology::class, $id);
        }
        if ($tech->user_id !== $userId) {
            throw new AuthorizationException('Acesso negado a este recurso.');
        }

        return $tech;
    }

    public function create(TechnologyDTO $dto): Technology
    {
        $tech = Technology::create([
            'user_id' => $dto->userId,
            'name' => $dto->name,
            'slug' => Str::slug($dto->name),
            'color' => $dto->color ?? '#3498DB',
            'icon' => $dto->icon,
            'description' => $dto->description,
        ]);
        $this->invalidateCacheForUser($dto->userId);

        return $tech;
    }

    public function update(Technology $technology, array $data): Technology
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $technology->update($data);
        $this->invalidateCacheForUser($technology->user_id);

        return $technology->fresh();
    }

    public function invalidateCacheForUser(string $userId): void
    {
        Cache::tags(['technologies', "user:{$userId}"])->flush();
    }
}
