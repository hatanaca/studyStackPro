<?php

namespace App\Modules\StudyPaths\Repositories\Contracts;

use App\Models\StudyPath;
use App\Modules\StudyPaths\DTOs\StudyPathDTO;
use Illuminate\Support\Collection;

interface StudyPathRepositoryInterface
{
    public function listForUser(string $userId): Collection;

    public function findForUser(string $id, string $userId): StudyPath;

    public function findByTechnology(string $userId, string $technologyId): ?StudyPath;

    public function create(StudyPathDTO $dto): StudyPath;

    public function update(StudyPath $path, array $data): StudyPath;

    public function delete(StudyPath $path): void;
}
