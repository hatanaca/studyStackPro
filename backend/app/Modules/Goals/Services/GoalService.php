<?php

namespace App\Modules\Goals\Services;

use App\Models\Goal;
use App\Modules\Goals\DTOs\GoalDTO;
use App\Modules\Goals\Repositories\Contracts\GoalRepositoryInterface;
use Illuminate\Support\Collection;

class GoalService
{
    public function __construct(
        private GoalRepositoryInterface $repository
    ) {}

    public function listForUser(string $userId, ?string $status = null): Collection
    {
        return $this->repository->listForUser($userId, $status);
    }

    public function findForUser(string $id, string $userId): Goal
    {
        return $this->repository->findForUser($id, $userId);
    }

    public function create(string $userId, array $data): Goal
    {
        $dto = new GoalDTO(
            userId: $userId,
            type: $data['type'],
            targetValue: $data['target_value'],
            startDate: $data['start_date'],
            endDate: $data['end_date'] ?? null,
            meta: $data['meta'] ?? null,
        );

        return $this->repository->create($dto);
    }

    public function update(string $id, string $userId, array $data): Goal
    {
        $goal = $this->repository->findForUser($id, $userId);

        return $this->repository->update($goal, $data);
    }

    public function delete(string $id, string $userId): void
    {
        $goal = $this->repository->findForUser($id, $userId);
        $this->repository->delete($goal);
    }
}
