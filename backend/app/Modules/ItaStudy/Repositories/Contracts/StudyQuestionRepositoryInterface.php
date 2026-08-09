<?php

namespace App\Modules\ItaStudy\Repositories\Contracts;

use App\Models\StudyAttempt;
use App\Models\StudyQuestion;
use App\Models\StudyQuestionVariant;
use Illuminate\Support\Collection;

interface StudyQuestionRepositoryInterface
{
    public function getRandomBySubTopic(string $subTopicId, ?int $difficulty = null): ?StudyQuestion;

    /** Retorna até $count questões aleatórias do sub-tópico em uma única query. */
    public function getRandomBySubTopicBatch(string $subTopicId, int $count, ?int $difficulty = null): Collection;

    public function createVariant(string $questionId, string $userId, int $seed, array $parameters, string $promptResolved, string $answerValue, ?array $choicesResolved = null): StudyQuestionVariant;

    /** Insere várias variantes em uma única operação (evita N inserts em loop). */
    public function createVariantsBatch(string $userId, array $variants): Collection;

    public function findVariant(string $variantId, string $userId): ?StudyQuestionVariant;

    public function createAttempt(string $userId, string $variantId, string $answer, bool $isCorrect, ?int $timeSpentSeconds = null): StudyAttempt;

    public function getQuestionsBySubTopic(string $subTopicId): Collection;
}
