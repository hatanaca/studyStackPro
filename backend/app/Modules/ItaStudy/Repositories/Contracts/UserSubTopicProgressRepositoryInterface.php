<?php

namespace App\Modules\ItaStudy\Repositories\Contracts;

use App\Models\UserSubTopicProgress;
use Illuminate\Support\Collection;

interface UserSubTopicProgressRepositoryInterface
{
    public function getOrCreate(string $userId, string $subTopicId): UserSubTopicProgress;

    public function recordAttempt(string $userId, string $subTopicId, bool $isCorrect): UserSubTopicProgress;

    public function getUserProgress(string $userId): Collection;

    public function getSubjectProgress(string $userId, string $subjectId): array;

    public function getTopicProgress(string $userId, string $topicId): array;
}
