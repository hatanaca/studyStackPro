<?php

namespace App\Modules\ItaStudy\Repositories;

use App\Models\UserSubTopicProgress;
use App\Modules\ItaStudy\Repositories\Contracts\UserSubTopicProgressRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentUserSubTopicProgressRepository implements UserSubTopicProgressRepositoryInterface
{
    public function getOrCreate(string $userId, string $subTopicId): UserSubTopicProgress
    {
        return UserSubTopicProgress::firstOrCreate(
            ['user_id' => $userId, 'sub_topic_id' => $subTopicId],
            ['attempted' => 0, 'correct' => 0, 'mastered' => false]
        );
    }

    public function recordAttempt(string $userId, string $subTopicId, bool $isCorrect): UserSubTopicProgress
    {
        $progress = $this->getOrCreate($userId, $subTopicId);

        $progress->increment('attempted');
        if ($isCorrect) {
            $progress->increment('correct');
        }
        $progress->last_attempt_at = now();

        // Mastered: >= 80% accuracy with at least 5 attempts
        $progress->mastered = $progress->attempted >= 5
            && ($progress->correct / $progress->attempted) >= 0.8;

        $progress->save();

        return $progress;
    }

    public function getUserProgress(string $userId): Collection
    {
        return UserSubTopicProgress::where('user_id', $userId)->get();
    }

    public function getSubjectProgress(string $userId, string $subjectId): array
    {
        $result = DB::table('user_sub_topic_progress')
            ->join('study_sub_topics', 'user_sub_topic_progress.sub_topic_id', '=', 'study_sub_topics.id')
            ->join('study_topics', 'study_sub_topics.topic_id', '=', 'study_topics.id')
            ->where('user_sub_topic_progress.user_id', $userId)
            ->where('study_topics.subject_id', $subjectId)
            ->selectRaw('
                COUNT(*) as attempted,
                SUM(CASE WHEN user_sub_topic_progress.mastered THEN 1 ELSE 0 END) as mastered
            ')
            ->first();

        $total = DB::table('study_sub_topics')
            ->join('study_topics', 'study_sub_topics.topic_id', '=', 'study_topics.id')
            ->where('study_topics.subject_id', $subjectId)
            ->count();

        $attempted = $result->attempted ?? 0;
        $mastered = $result->mastered ?? 0;

        return [
            'attempted' => (int) $attempted,
            'mastered' => (int) $mastered,
            'total' => $total,
            'percentage' => $total > 0 ? round(($mastered / $total) * 100, 1) : 0,
        ];
    }

    public function getTopicProgress(string $userId, string $topicId): array
    {
        $result = DB::table('user_sub_topic_progress')
            ->join('study_sub_topics', 'user_sub_topic_progress.sub_topic_id', '=', 'study_sub_topics.id')
            ->where('user_sub_topic_progress.user_id', $userId)
            ->where('study_sub_topics.topic_id', $topicId)
            ->selectRaw('
                COUNT(*) as attempted,
                SUM(CASE WHEN user_sub_topic_progress.mastered THEN 1 ELSE 0 END) as mastered
            ')
            ->first();

        $total = DB::table('study_sub_topics')
            ->where('topic_id', $topicId)
            ->count();

        $attempted = $result->attempted ?? 0;
        $mastered = $result->mastered ?? 0;

        return [
            'attempted' => (int) $attempted,
            'mastered' => (int) $mastered,
            'total' => $total,
            'percentage' => $total > 0 ? round(($mastered / $total) * 100, 1) : 0,
        ];
    }
}
