<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItaStudy\GenerateBatchQuestionRequest;
use App\Http\Requests\ItaStudy\GenerateQuestionRequest;
use App\Http\Requests\ItaStudy\SubmitAnswerRequest;
use App\Modules\ItaStudy\Services\ItaStudyService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;

class ItaStudyController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ItaStudyService $itaStudy,
    ) {}

    public function subjects(): JsonResponse
    {
        return $this->success($this->itaStudy->listSubjects(request()->user()->id));
    }

    public function topics(string $subjectId): JsonResponse
    {
        return $this->success($this->itaStudy->listTopics($subjectId, request()->user()->id));
    }

    public function subTopics(string $topicId): JsonResponse
    {
        return $this->success($this->itaStudy->listSubTopics($topicId, request()->user()->id));
    }

    public function generate(GenerateQuestionRequest $request): JsonResponse
    {
        $result = $this->itaStudy->generateQuestion(
            $request->validated('sub_topic_id'),
            $request->user()->id,
            $request->validated('difficulty'),
        );

        if (! $result) {
            return $this->error('Nenhuma questão disponível para este sub-tópico.', 'NO_QUESTIONS');
        }

        return $this->success($result);
    }

    public function answer(SubmitAnswerRequest $request): JsonResponse
    {
        $result = $this->itaStudy->submitAnswer(
            $request->validated('variant_id'),
            $request->user()->id,
            $request->validated('answer'),
            $request->validated('time_spent_seconds'),
        );

        return $this->success($result);
    }

    public function generateBatch(GenerateBatchQuestionRequest $request): JsonResponse
    {
        $result = $this->itaStudy->generateBatch(
            $request->validated('sub_topic_id'),
            $request->user()->id,
            $request->validated('count'),
            $request->validated('difficulty'),
        );

        return $this->success($result);
    }

    public function progress(): JsonResponse
    {
        return $this->success($this->itaStudy->getProgress(request()->user()->id));
    }

    public function subjectProgress(string $subjectId): JsonResponse
    {
        return $this->success($this->itaStudy->getSubjectProgress($subjectId, request()->user()->id));
    }

    public function topicProgress(string $topicId): JsonResponse
    {
        return $this->success($this->itaStudy->getTopicProgress($topicId, request()->user()->id));
    }
}
