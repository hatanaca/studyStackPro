<?php

namespace App\Modules\ItaStudy\Repositories\Contracts;

use App\Models\StudySubject;
use Illuminate\Support\Collection;

interface StudySubjectRepositoryInterface
{
    public function getAll(): Collection;

    public function findById(string $id): ?StudySubject;

    public function getTopicsWithProgress(string $subjectId, string $userId): Collection;

    public function getSubjectProgress(string $subjectId, string $userId): array;
}
