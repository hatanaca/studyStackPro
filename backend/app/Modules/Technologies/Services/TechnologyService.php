<?php

namespace App\Modules\Technologies\Services;

use App\Modules\Technologies\DTOs\TechnologyDTO;
use App\Modules\Technologies\Repositories\Contracts\TechnologyRepositoryInterface;

class TechnologyService
{
    public function __construct(
        private TechnologyRepositoryInterface $repository
    ) {}

    public function listForUser(string $userId): \Illuminate\Support\Collection
    {
        return $this->repository->listForUser($userId);
    }

    public function search(string $userId, string $query, int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->repository->search($userId, $query, $limit);
    }

    public function findForUser(string $id, string $userId): \App\Models\Technology
    {
        return $this->repository->findForUser($id, $userId);
    }

    public function create(string $userId, array $data): \App\Models\Technology
    {
        $dto = new TechnologyDTO(
            userId: $userId,
            name: $data['name'],
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
            description: $data['description'] ?? null,
        );
        return $this->repository->create($dto);
    }

    public function update(string $id, string $userId, array $data): \App\Models\Technology
    {
        $tech = $this->repository->findForUser($id, $userId);
        return $this->repository->update($tech, $data);
    }

    public function deactivate(string $id, string $userId): void
    {
        $tech = $this->repository->findForUser($id, $userId);
        $tech->update(['is_active' => false]);
        $this->repository->invalidateCacheForUser($userId);
    }
}
