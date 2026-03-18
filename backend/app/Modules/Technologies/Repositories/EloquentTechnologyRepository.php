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

/**
 * Implementação Eloquent do repositório de tecnologias.
 * Usa cache com tags (technologies, user:{id}). Busca com ILIKE. Invalida cache em create/update.
 */
class EloquentTechnologyRepository implements TechnologyRepositoryInterface
{
    private const CACHE_TTL_MINUTES = 5;

    /** Lista tecnologias do usuário (cache 5min). activeOnly filtra is_active=true */
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

    /** Busca por nome (ILIKE). Mínimo 2 caracteres. Não usa cache. */
    public function search(string $userId, string $query, int $limit = 10): Collection
    {
        $q = trim($query);
        if (strlen($q) < 2) {
            return collect();
        }

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $q);

        return Technology::where('user_id', $userId)
            ->where('is_active', true)
            ->where('name', 'ilike', "%{$escaped}%")
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /** Busca por ID restrita ao usuário. ModelNotFoundException se não existir ou não pertencer ao usuário */
    public function findForUser(string $id, string $userId): Technology
    {
        $tech = Technology::where('user_id', $userId)->find($id);
        if (! $tech) {
            throw (new ModelNotFoundException)->setModel(Technology::class, $id);
        }

        return $tech;
    }

    /** Cria tecnologia e invalida cache do usuário */
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

    /** Atualiza tecnologia. Recalcula slug se name mudar. Invalida cache. */
    public function update(Technology $technology, array $data): Technology
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $technology->update($data);
        $this->invalidateCacheForUser($technology->user_id);

        return $technology->fresh();
    }

    /** Limpa cache de tecnologias do usuário */
    public function invalidateCacheForUser(string $userId): void
    {
        Cache::tags(['technologies', "user:{$userId}"])->flush();
    }
}
