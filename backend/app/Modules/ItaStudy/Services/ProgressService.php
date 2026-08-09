<?php

namespace App\Modules\ItaStudy\Services;

use App\Modules\ItaStudy\Repositories\Contracts\StudySubjectRepositoryInterface;
use App\Modules\ItaStudy\Repositories\Contracts\UserSubTopicProgressRepositoryInterface;

class ProgressService
{
    public function __construct(
        private readonly StudySubjectRepositoryInterface $subjects,
        private readonly UserSubTopicProgressRepositoryInterface $progress,
    ) {}

    public function getOverallProgress(string $userId): array
    {
        $allSubjects = $this->subjects->getAll();

        $subjectProgress = $allSubjects->map(function ($subject) use ($userId) {
            $progress = $this->progress->getSubjectProgress($userId, $subject->id);

            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'slug' => $subject->slug,
                'icon' => $subject->icon,
                'color' => $subject->color,
                'progress' => $progress,
            ];
        });

        $totalAttempted = $subjectProgress->sum('progress.attempted');
        $totalMastered = $subjectProgress->sum('progress.mastered');
        $totalTopics = $subjectProgress->sum('progress.total');

        return [
            'subjects' => $subjectProgress,
            'overall' => [
                'attempted' => $totalAttempted,
                'mastered' => $totalMastered,
                'total' => $totalTopics,
                'percentage' => $totalTopics > 0 ? round(($totalMastered / $totalTopics) * 100, 1) : 0,
            ],
        ];
    }

    public function getSubjectProgress(string $userId, string $subjectId): array
    {
        $topics = $this->subjects->getTopicsWithProgress($subjectId, $userId);

        return [
            'topics' => $topics,
            'progress' => $this->progress->getSubjectProgress($userId, $subjectId),
        ];
    }

    public function getTopicProgress(string $userId, string $topicId): array
    {
        return $this->progress->getTopicProgress($userId, $topicId);
    }
}
