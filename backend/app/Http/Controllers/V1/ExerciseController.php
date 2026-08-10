<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exercises\GenerateVariantRequest;
use App\Http\Requests\Exercises\GradeExerciseRequest;
use App\Http\Requests\Exercises\SolveExerciseRequest;
use App\Http\Requests\Exercises\StoreExerciseTemplateRequest;
use App\Http\Requests\Exercises\UpdateExerciseTemplateRequest;
use App\Http\Resources\ExerciseAttemptResource;
use App\Http\Resources\ExerciseTemplateResource;
use App\Http\Resources\ExerciseVariantResource;
use App\Modules\Exercises\Services\ExerciseService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ExerciseService $exercises,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $templates = $this->exercises->listTemplates($request->user()->id);

        return $this->success(ExerciseTemplateResource::collection($templates));
    }

    public function store(StoreExerciseTemplateRequest $request): JsonResponse
    {
        $template = $this->exercises->createTemplate($request->user()->id, $request->validated());

        return $this->success(new ExerciseTemplateResource($template), 'Exercício criado.', 201);
    }

    public function show(Request $request, string $template): JsonResponse
    {
        $model = $this->exercises->findTemplateForUser($template, $request->user()->id);

        return $this->success(new ExerciseTemplateResource($model));
    }

    public function update(UpdateExerciseTemplateRequest $request, string $template): JsonResponse
    {
        $model = $this->exercises->updateTemplate($request->user()->id, $template, $request->validated());

        return $this->success(new ExerciseTemplateResource($model), 'Exercício atualizado.');
    }

    public function destroy(Request $request, string $template): JsonResponse
    {
        $this->exercises->deleteTemplate($request->user()->id, $template);

        return $this->success(null, 'Exercício excluído.');
    }

    public function generate(Request $request, GenerateVariantRequest $variantRequest, string $template): JsonResponse
    {
        $model = $this->exercises->findTemplateForUser($template, $request->user()->id);
        $variant = $this->exercises->generateVariant(
            $request->user()->id,
            $model,
            $variantRequest->validated('seed'),
        );

        return $this->success(new ExerciseVariantResource($variant), 'Variante gerada.', 201);
    }

    public function grade(GradeExerciseRequest $request): JsonResponse
    {
        $attempt = $this->exercises->gradeAttempt(
            $request->user()->id,
            $request->validated('variant_id'),
            $request->validated('answer'),
        );

        return $this->success(new ExerciseAttemptResource($attempt));
    }

    public function attempts(Request $request): JsonResponse
    {
        $attempts = $this->exercises->listAttempts($request->user()->id);

        return $this->success(ExerciseAttemptResource::collection($attempts));
    }

    public function stats(Request $request): JsonResponse
    {
        return $this->success($this->exercises->stats($request->user()->id));
    }

    public function solve(SolveExerciseRequest $request): JsonResponse
    {
        return $this->success(
            $this->exercises->solve(
                $request->validated('expression'),
                $request->validated('variable'),
            )
        );
    }
}
