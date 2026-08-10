<?php

namespace App\Modules\ItaStudy\Services;

use App\Models\StudySubTopic;
use App\Models\UserStudyFavorite;
use App\Models\UserStudyNote;
use App\Models\UserStudyReadingProgress;
use Illuminate\Support\Carbon;

class UserStudyService
{
    public function subTopicDetail(string $subTopicId): ?array
    {
        $subTopic = StudySubTopic::find($subTopicId);
        if (!$subTopic) {
            return null;
        }

        return [
            'id' => $subTopic->id,
            'topic_id' => $subTopic->topic_id,
            'name' => $subTopic->name,
            'slug' => $subTopic->slug,
            'description' => $subTopic->description,
            'content' => $subTopic->content,
            'faqs' => $subTopic->faqs,
            'learning_objectives' => $subTopic->learning_objectives,
            'simulation_config' => $subTopic->simulation_config,
        ];
    }

    public function listFavorites(string $userId): array
    {
        return UserStudyFavorite::where('user_id', $userId)
            ->with('subTopic')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($favorite) => [
                'id' => $favorite->id,
                'sub_topic_id' => $favorite->sub_topic_id,
                'sub_topic_name' => $favorite->subTopic?->name,
                'created_at' => $favorite->created_at,
            ])
            ->toArray();
    }

    public function addFavorite(string $userId, string $subTopicId): ?array
    {
        if (!StudySubTopic::whereKey($subTopicId)->exists()) {
            return null;
        }

        $favorite = UserStudyFavorite::firstOrCreate([
            'user_id' => $userId,
            'sub_topic_id' => $subTopicId,
        ]);

        return [
            'id' => $favorite->id,
            'sub_topic_id' => $favorite->sub_topic_id,
            'created_at' => $favorite->created_at,
        ];
    }

    public function removeFavorite(string $userId, string $subTopicId): void
    {
        UserStudyFavorite::where('user_id', $userId)
            ->where('sub_topic_id', $subTopicId)
            ->delete();
    }

    public function isFavorited(string $userId, string $subTopicId): bool
    {
        return UserStudyFavorite::where('user_id', $userId)
            ->where('sub_topic_id', $subTopicId)
            ->exists();
    }

    public function getNote(string $userId, string $subTopicId): ?array
    {
        $note = UserStudyNote::where('user_id', $userId)
            ->where('sub_topic_id', $subTopicId)
            ->first();

        if (!$note) {
            return null;
        }

        return [
            'id' => $note->id,
            'sub_topic_id' => $note->sub_topic_id,
            'content' => $note->content,
            'updated_at' => $note->updated_at,
        ];
    }

    public function saveNote(string $userId, string $subTopicId, string $content): array
    {
        $note = UserStudyNote::updateOrCreate(
            ['user_id' => $userId, 'sub_topic_id' => $subTopicId],
            ['content' => $content],
        );

        return [
            'id' => $note->id,
            'sub_topic_id' => $note->sub_topic_id,
            'content' => $note->content,
            'updated_at' => $note->updated_at,
        ];
    }

    public function deleteNote(string $userId, string $subTopicId): void
    {
        UserStudyNote::where('user_id', $userId)
            ->where('sub_topic_id', $subTopicId)
            ->delete();
    }

    public function getReadingProgress(string $userId, string $subTopicId): array
    {
        $progress = UserStudyReadingProgress::where('user_id', $userId)
            ->where('sub_topic_id', $subTopicId)
            ->first();

        return [
            'sub_topic_id' => $subTopicId,
            'progress' => $progress?->progress ?? 0,
            'last_read_at' => $progress?->last_read_at,
        ];
    }

    public function updateReadingProgress(string $userId, string $subTopicId, float $progress): array
    {
        $record = UserStudyReadingProgress::updateOrCreate(
            ['user_id' => $userId, 'sub_topic_id' => $subTopicId],
            [
                'progress' => max(0, min(100, $progress)),
                'last_read_at' => Carbon::now(),
            ],
        );

        return [
            'sub_topic_id' => $record->sub_topic_id,
            'progress' => $record->progress,
            'last_read_at' => $record->last_read_at,
        ];
    }
}
