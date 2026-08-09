<?php

namespace App\Modules\ItaStudy\Repositories;

use App\Models\StudySubject;
use App\Models\StudyTopic;
use App\Modules\ItaStudy\Repositories\Contracts\StudySubjectRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentStudySubjectRepository implements StudySubjectRepositoryInterface
{
    public function getAll(): Collection
    {
        return StudySubject::orderBy('sort_order')->get();
    }

    public function findById(string $id): ?StudySubject
    {
        return StudySubject::find($id);
    }

    public function getTopicsWithProgress(string $subjectId, string $userId): Collection
    {
        return StudyTopic::where('subject_id', $subjectId)
            ->orderBy('sort_order')
            ->withCount(['subTopics'])
            ->get()
            ->map(function ($topic) use ($userId) {
                $subTopicIds = $topic->subTopics->pluck('id');
                $progress = DB::table('user_sub_topic_progress')
                    ->where('user_id', $userId)
                    ->whereIn('sub_topic_id', $subTopicIds)
                    ->selectRaw('COUNT(*) as attempted, SUM(CASE WHEN mastered THEN 1 ELSE 0 END) as mastered')
                    ->first();

                $total = $topic->sub_topics_count;
                $attempted = $progress->attempted ?? 0;
                $mastered = $progress->mastered ?? 0;

                $topic->progress = [
                    'attempted' => (int) $attempted,
                    'mastered' => (int) $mastered,
                    'total' => $total,
                    'percentage' => $total > 0 ? round(($mastered / $total) * 100, 1) : 0,
                ];

                return $topic;
            });
    }

    public function getSubjectProgress(string $subjectId, string $userId): array
    {
        $topicIds = StudyTopic::where('subject_id', $subjectId)->pluck('id');
        $subTopicCount = DB::table('study_sub_topics')
            ->whereIn('topic_id', $topicIds)
            ->count();

        $progress = DB::table('user_sub_topic_progress')
            ->where('user_id', $userId)
            ->whereIn('sub_topic_id', function ($query) use ($topicIds) {
                $query->select('id')
                    ->from('study_sub_topics')
                    ->whereIn('topic_id', $topicIds);
            })
            ->selectRaw('COUNT(*) as attempted, SUM(CASE WHEN mastered THEN 1 ELSE 0 END) as mastered')
            ->first();

        $attempted = $progress->attempted ?? 0;
        $mastered = $progress->mastered ?? 0;

        return [
            'attempted' => (int) $attempted,
            'mastered' => (int) $mastered,
            'total' => $subTopicCount,
            'percentage' => $subTopicCount > 0 ? round(($mastered / $subTopicCount) * 100, 1) : 0,
        ];
    }
}
