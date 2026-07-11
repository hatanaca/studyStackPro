<?php

namespace App\Modules\Technologies\Repositories\Contracts;

use App\Models\Technology;
use App\Modules\Technologies\DTOs\TechnologyDTO;
use Illuminate\Support\Collection;

interface TechnologyRepositoryInterface
{
    public function listForUser(string $userId, bool $activeOnly = true): Collection;

    public function search(string $userId, string $query, int $limit = 10): Collection;

    public function findForUser(string $id, string $userId): Technology;

    public function create(TechnologyDTO $dto): Technology;

    public function update(Technology $technology, array $data): Technology;

    public function invalidateCacheForUser(string $userId): void;
}
