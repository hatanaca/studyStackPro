<?php

namespace App\Modules\ItaStudy\Services;

use App\Exceptions\Domain\StudyQuestionVariantNotFoundException;
use App\Models\StudyQuestion;
use App\Models\StudyTopic;
use App\Modules\ItaStudy\Repositories\Contracts\StudyQuestionRepositoryInterface;
use App\Modules\ItaStudy\Repositories\Contracts\StudySubjectRepositoryInterface;
use App\Modules\ItaStudy\Repositories\Contracts\UserSubTopicProgressRepositoryInterface;
use Illuminate\Support\Collection;

class ItaStudyService
{
    public function __construct(
        private readonly StudySubjectRepositoryInterface $subjects,
        private readonly StudyQuestionRepositoryInterface $questions,
        private readonly UserSubTopicProgressRepositoryInterface $progress,
        private readonly QuestionGeneratorService $generator,
        private readonly ProgressService $progressService,
    ) {}

    public function listSubjects(string $userId): Collection
    {
        $allSubjects = $this->subjects->getAll();

        return $allSubjects->map(function ($subject) use ($userId) {
            $progress = $this->progress->getSubjectProgress($userId, $subject->id);
            $subject->progress = $progress;

            return $subject;
        });
    }

    public function listTopics(string $subjectId, string $userId): array
    {
        return $this->subjects->getTopicsWithProgress($subjectId, $userId)->toArray();
    }

    public function listSubTopics(string $topicId, string $userId): array
    {
        $topic = StudyTopic::with('subTopics')->find($topicId);
        if (! $topic) {
            return [];
        }

        $subTopics = $topic->subTopics->map(function ($subTopic) use ($userId) {
            $progress = $this->progress->getOrCreate($userId, $subTopic->id);
            $subTopic->attempted = $progress->attempted;
            $subTopic->correct = $progress->correct;
            $subTopic->mastered = $progress->mastered;

            return $subTopic;
        });

        return $subTopics->toArray();
    }

    public function generateQuestion(string $subTopicId, string $userId, ?int $difficulty = null): ?array
    {
        $question = $this->questions->getRandomBySubTopic($subTopicId, $difficulty);
        if (! $question) {
            return null;
        }

        $result = $this->generator->generateVariant($question, $userId);

        return $this->formatQuestion($result['variant']->id, $question, $result['variant']);
    }

    public function submitAnswer(string $variantId, string $userId, string $answer, ?int $timeSpentSeconds = null): array
    {
        $variant = $this->questions->findVariant($variantId, $userId);

        if (! $variant) {
            throw new StudyQuestionVariantNotFoundException;
        }

        $question = $variant->question;
        $isCorrect = $this->generator->gradeAnswer($answer, $variant->answer_value, $question->answer_type);

        $attempt = $this->questions->createAttempt(
            $userId,
            $variantId,
            $answer,
            $isCorrect,
            $timeSpentSeconds,
        );

        // Update progress
        $subTopicId = $question->sub_topic_id;
        $this->progress->recordAttempt($userId, $subTopicId, $isCorrect);

        return [
            'attempt_id' => $attempt->id,
            'is_correct' => $isCorrect,
            'answer' => $answer,
            'expected' => $variant->answer_value,
            'solution_latex' => $question->solution_latex,
            'explanation' => $question->explanation,
            'time_spent_seconds' => $timeSpentSeconds,
        ];
    }

    public function generateBatch(string $subTopicId, string $userId, int $count, ?int $difficulty = null): array
    {
        $questions = $this->questions->getRandomBySubTopicBatch($subTopicId, $count, $difficulty);
        if ($questions->isEmpty()) {
            return [];
        }

        // Gera todas as variantes em memória para inserir em lote (1 query em vez de N).
        $generated = [];
        foreach ($questions as $question) {
            $generated[] = [
                'question_id' => $question->id,
                ...$this->generator->generateVariantData($question, $userId),
            ];
        }

        $variants = $this->questions->createVariantsBatch($userId, $generated);

        return $variants
            ->map(fn ($variant) => $this->formatQuestion($variant->id, $variant->question, $variant))
            ->values()
            ->toArray();
    }

    public function getProgress(string $userId): array
    {
        return $this->progressService->getOverallProgress($userId);
    }

    public function getSubjectProgress(string $subjectId, string $userId): array
    {
        return $this->progressService->getSubjectProgress($userId, $subjectId);
    }

    public function getTopicProgress(string $topicId, string $userId): array
    {
        return $this->progressService->getTopicProgress($userId, $topicId);
    }

    private function formatQuestion(string $variantId, StudyQuestion $question, $variant): array
    {
        return [
            'variant_id' => $variantId,
            'question_id' => $question->id,
            'kind' => $question->kind,
            'prompt' => $variant->prompt_resolved,
            'choices' => $variant->choices_resolved,
            'has_graph' => $question->has_graph,
            'graph_config' => $question->graph_config,
            'visual_type' => $question->visual_type,
            'visual_config' => $question->visual_config,
            'difficulty' => $question->difficulty,
            'hint' => $question->hint,
        ];
    }
}
