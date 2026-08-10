<?php

namespace App\Modules\Exercises\Services;

use App\Exceptions\MathServiceException;
use App\Models\ExerciseAttempt;
use App\Models\ExerciseTemplate;
use App\Models\ExerciseVariant;
use App\Services\MathService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class ExerciseService
{
    public function __construct(
        private readonly MathService $math,
    ) {}

    /** Templates do usuário + globais (user_id null). */
    public function listTemplates(string $userId): Collection
    {
        return ExerciseTemplate::query()
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))
            ->orderByDesc('created_at')
            ->get();
    }

    public function createTemplate(string $userId, array $data): ExerciseTemplate
    {
        $template = new ExerciseTemplate($data);
        $template->user_id = $userId;
        $template->save();

        return $template;
    }

    /** Template próprio ou global. */
    public function findTemplateForUser(string $templateId, string $userId): ExerciseTemplate
    {
        return ExerciseTemplate::query()
            ->where('id', $templateId)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))
            ->firstOrFail();
    }

    /** Apenas templates criados pelo próprio usuário (globais são somente leitura). */
    public function findOwnTemplate(string $templateId, string $userId): ExerciseTemplate
    {
        $template = ExerciseTemplate::query()->where('id', $templateId)->firstOrFail();

        if ($template->user_id === null || $template->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return $template;
    }

    public function updateTemplate(string $userId, string $templateId, array $data): ExerciseTemplate
    {
        $template = $this->findOwnTemplate($templateId, $userId);
        $template->update($data);

        return $template;
    }

    public function deleteTemplate(string $userId, string $templateId): void
    {
        $this->findOwnTemplate($templateId, $userId)->delete();
    }

    /**
     * Gera e persiste uma variante (cache do resultado do math-service).
     *
     * @throws MathServiceException
     */
    public function generateVariant(string $userId, ExerciseTemplate $template, ?int $seed = null): ExerciseVariant
    {
        $generated = $this->math->generate(
            template: $template->prompt,
            answerExpression: $template->answer_expression,
            parametersSpec: $template->parameters_spec ?? [],
            seed: $seed,
        );

        $variant = new ExerciseVariant([
            'template_id' => $template->id,
            'seed' => $seed,
            'parameters' => $generated['parameters'],
            'prompt_latex' => $generated['prompt'],
            'answer_expr' => $generated['answer_expr'],
            'solution_latex' => $generated['answer_latex'] ?: $template->solution_latex,
        ]);
        $variant->user_id = $userId;
        $variant->save();

        return $variant;
    }

    /** Corrige a resposta de uma variante e registra a tentativa. */
    public function gradeAttempt(string $userId, string $variantId, string $answer): ExerciseAttempt
    {
        $variant = ExerciseVariant::query()
            ->with('template')
            ->where('id', $variantId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $result = $this->math->grade(
            mode: $variant->template->kind,
            studentAnswer: $answer,
            expectedExpression: $variant->answer_expr,
            variables: $variant->template->variables ?? [],
        );

        $attempt = new ExerciseAttempt([
            'variant_id' => $variant->id,
            'answer' => $answer,
            'is_correct' => $result['correct'],
            'graded_by' => 'sympy',
            'feedback_latex' => $result['feedback'],
            'expected_latex' => $result['expected_latex'],
            'submitted_at' => now(),
        ]);
        $attempt->user_id = $userId;
        $attempt->save();

        return $attempt;
    }

    public function listAttempts(string $userId, int $limit = 20): Collection
    {
        return ExerciseAttempt::query()
            ->with(['variant.template'])
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')
            ->limit($limit)
            ->get();
    }

    /** Resolve uma equação via math-service (para consulta rápida do usuário). */
    public function solve(string $expression, string $variable): array
    {
        return $this->math->solve($expression, $variable);
    }

    /** Estatísticas de acerto por template + geral. */
    public function stats(string $userId): array
    {
        $attempts = ExerciseAttempt::query()
            ->with('variant.template')
            ->where('user_id', $userId)
            ->get();

        $total = $attempts->count();
        $correct = $attempts->where('is_correct', true)->count();

        $byTemplate = $attempts
            ->groupBy(fn (ExerciseAttempt $a) => $a->variant?->template?->title ?? 'Sem título')
            ->map(fn ($group) => [
                'template_title' => $group->first()->variant?->template?->title ?? 'Sem título',
                'attempts' => $group->count(),
                'correct' => $group->where('is_correct', true)->count(),
            ])
            ->values();

        return [
            'total_attempts' => $total,
            'correct_attempts' => $correct,
            'accuracy' => $total > 0 ? round($correct / $total, 4) : 0.0,
            'by_template' => $byTemplate,
        ];
    }
}
