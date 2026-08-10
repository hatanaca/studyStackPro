<?php

namespace App\Modules\StudyPaths\Services;

use App\Models\StudyPath;
use App\Modules\StudyPaths\DTOs\StudyPathDTO;
use App\Modules\StudyPaths\Repositories\Contracts\StudyPathRepositoryInterface;
use Illuminate\Support\Collection;

class StudyPathService
{
    public function __construct(
        private StudyPathRepositoryInterface $repository
    ) {}

    public function listForUser(string $userId): Collection
    {
        return $this->repository->listForUser($userId);
    }

    public function findForUser(string $id, string $userId): StudyPath
    {
        return $this->repository->findForUser($id, $userId);
    }

    public function findByTechnology(string $userId, string $technologyId): ?StudyPath
    {
        return $this->repository->findByTechnology($userId, $technologyId);
    }

    public function create(string $userId, array $data): StudyPath
    {
        $dto = new StudyPathDTO(
            userId: $userId,
            title: $data['title'] ?? 'Mapa de Estudo',
            technologyId: $data['technology_id'] ?? null,
            nodes: $data['nodes'] ?? null,
            edges: $data['edges'] ?? null,
        );

        return $this->repository->create($dto);
    }

    public function update(string $id, string $userId, array $data): StudyPath
    {
        $path = $this->repository->findForUser($id, $userId);

        return $this->repository->update($path, $data);
    }

    public function delete(string $id, string $userId): void
    {
        $path = $this->repository->findForUser($id, $userId);
        $this->repository->delete($path);
    }
}
