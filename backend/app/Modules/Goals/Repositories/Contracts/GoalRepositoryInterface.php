<?php

namespace App\Modules\Goals\Repositories\Contracts;

use App\Models\Goal;
use App\Modules\Goals\DTOs\GoalDTO;
use Illuminate\Support\Collection;

interface GoalRepositoryInterface
{
    public function listForUser(string $userId, ?string $status = null): Collection;

    public function findForUser(string $id, string $userId): Goal;

    public function create(GoalDTO $dto): Goal;

    public function update(Goal $goal, array $data): Goal;

    public function delete(Goal $goal): void;

    public function invalidateCacheForUser(string $userId): void;
}
