<?php

namespace App\Modules\ItaStudy\Repositories;

use App\Models\StudyAttempt;
use App\Models\StudyQuestion;
use App\Models\StudyQuestionVariant;
use App\Modules\ItaStudy\Repositories\Contracts\StudyQuestionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EloquentStudyQuestionRepository implements StudyQuestionRepositoryInterface
{
    public function getRandomBySubTopic(string $subTopicId, ?int $difficulty = null): ?StudyQuestion
    {
        $query = StudyQuestion::where('sub_topic_id', $subTopicId);

        if ($difficulty !== null) {
            $query->where('difficulty', $difficulty);
        }

        return $query->inRandomOrder()->first();
    }

    public function getRandomBySubTopicBatch(string $subTopicId, int $count, ?int $difficulty = null): Collection
    {
        $query = StudyQuestion::where('sub_topic_id', $subTopicId);

        if ($difficulty !== null) {
            $query->where('difficulty', $difficulty);
        }

        return $query->inRandomOrder()->limit($count)->get();
    }

    public function createVariant(
        string $questionId,
        string $userId,
        int $seed,
        array $parameters,
        string $promptResolved,
        string $answerValue,
        ?array $choicesResolved = null,
    ): StudyQuestionVariant {
        return StudyQuestionVariant::create([
            'question_id' => $questionId,
            'user_id' => $userId,
            'seed' => $seed,
            'parameters' => $parameters,
            'prompt_resolved' => $promptResolved,
            'answer_value' => $answerValue,
            'choices_resolved' => $choicesResolved,
        ]);
    }

    public function createVariantsBatch(string $userId, array $variants): Collection
    {
        $now = now();
        $rows = [];

        foreach ($variants as $variant) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'question_id' => $variant['question_id'],
                'user_id' => $userId,
                'seed' => $variant['seed'],
                'parameters' => json_encode($variant['parameters']),
                'prompt_resolved' => $variant['prompt_resolved'],
                'answer_value' => $variant['answer_value'],
                'choices_resolved' => isset($variant['choices_resolved'])
                    ? json_encode($variant['choices_resolved'])
                    : null,
                'created_at' => $now,
            ];
        }

        if (empty($rows)) {
            return new Collection;
        }

        StudyQuestionVariant::insert($rows);

        return StudyQuestionVariant::with('question')
            ->whereIn('id', array_column($rows, 'id'))
            ->get();
    }

    public function findVariant(string $variantId, string $userId): ?StudyQuestionVariant
    {
        return StudyQuestionVariant::where('id', $variantId)
            ->where('user_id', $userId)
            ->first();
    }

    public function createAttempt(
        string $userId,
        string $variantId,
        string $answer,
        bool $isCorrect,
        ?int $timeSpentSeconds = null,
    ): StudyAttempt {
        return StudyAttempt::create([
            'user_id' => $userId,
            'variant_id' => $variantId,
            'answer' => $answer,
            'is_correct' => $isCorrect,
            'time_spent_seconds' => $timeSpentSeconds,
        ]);
    }

    public function getQuestionsBySubTopic(string $subTopicId): Collection
    {
        return StudyQuestion::where('sub_topic_id', $subTopicId)->get();
    }
}
