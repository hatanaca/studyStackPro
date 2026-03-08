<?php

namespace App\Modules\StudySessions\Repositories\Contracts;

use App\Models\StudySession;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StudySessionRepositoryInterface
{
    public function findByUser(string $userId, array $filters): LengthAwarePaginator;

    public function findActiveByUser(string $userId): ?StudySession;

    public function findById(string $id): ?StudySession;

    public function create(StudySessionDTO $dto): StudySession;

    public function update(StudySession $session, array $data): StudySession;

    public function delete(StudySession $session): void;
}
