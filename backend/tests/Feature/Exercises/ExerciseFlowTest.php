<?php

namespace Tests\Feature\Exercises;

use App\Models\ExerciseTemplate;
use App\Models\ExerciseVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExerciseFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_store_creates_template(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/templates', [
                'title' => 'Equação do 1º grau',
                'kind' => 'numeric',
                'prompt' => 'Resolva {{a}}x + {{b}} = 0',
                'parameters_spec' => [
                    'a' => ['type' => 'int', 'min' => 1, 'max' => 9],
                    'b' => ['type' => 'int', 'min' => 1, 'max' => 9],
                ],
                'answer_expression' => '-{{b}}/{{a}}',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('exercise_templates', [
            'user_id' => $this->user->id,
            'title' => 'Equação do 1º grau',
        ]);
    }

    public function test_store_rejects_invalid_payload(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/templates', [
                'title' => 'Sem resposta',
                'kind' => 'numerico',
            ]);

        $response->assertStatus(422)
            ->assertJson(['error' => ['code' => 'VALIDATION_ERROR']]);
    }

    public function test_index_includes_own_and_global_templates(): void
    {
        ExerciseTemplate::forceCreate([
            'user_id' => null,
            'title' => 'Template global',
            'kind' => 'numeric',
            'prompt' => 'Quanto é {{a}} + {{b}}?',
            'parameters_spec' => ['a' => ['type' => 'int', 'min' => 1, 'max' => 9]],
            'answer_expression' => '{{a}}',
        ]);
        ExerciseTemplate::forceCreate([
            'user_id' => $this->user->id,
            'title' => 'Template próprio',
            'kind' => 'numeric',
            'prompt' => 'Quanto é {{a}}?',
            'parameters_spec' => ['a' => ['type' => 'int', 'min' => 1, 'max' => 9]],
            'answer_expression' => '{{a}}',
        ]);
        ExerciseTemplate::forceCreate([
            'user_id' => User::factory()->create()->id,
            'title' => 'Template alheio',
            'kind' => 'numeric',
            'prompt' => 'Quanto é {{a}}?',
            'parameters_spec' => ['a' => ['type' => 'int', 'min' => 1, 'max' => 9]],
            'answer_expression' => '{{a}}',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/exercises/templates');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_global_template_is_read_only(): void
    {
        $global = ExerciseTemplate::forceCreate([
            'user_id' => null,
            'title' => 'Global',
            'kind' => 'numeric',
            'prompt' => 'Quanto é {{a}}?',
            'parameters_spec' => ['a' => ['type' => 'int', 'min' => 1, 'max' => 9]],
            'answer_expression' => '{{a}}',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/v1/exercises/templates/'.$global->id, ['title' => 'Hackeado']);

        $response->assertStatus(403)
            ->assertJson(['error' => ['code' => 'FORBIDDEN']]);
    }

    public function test_generate_variant_calls_math_service_and_caches(): void
    {
        Http::fake([
            'http://math-service:8000/generate' => Http::response([
                'parameters' => ['a' => 2, 'b' => 4],
                'prompt' => 'Resolva 2x + 4 = 0',
                'answer_expr' => '-2',
                'answer_latex' => '-2',
            ], 200),
        ]);

        $template = ExerciseTemplate::forceCreate([
            'user_id' => $this->user->id,
            'title' => 'Equação',
            'kind' => 'numeric',
            'prompt' => 'Resolva {{a}}x + {{b}} = 0',
            'parameters_spec' => ['a' => ['type' => 'int', 'min' => 1, 'max' => 9]],
            'answer_expression' => '-{{b}}/{{a}}',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/templates/'.$template->id.'/generate');

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'template_id' => $template->id,
                    'prompt_latex' => 'Resolva 2x + 4 = 0',
                ],
            ]);

        $this->assertDatabaseHas('exercise_variants', [
            'template_id' => $template->id,
            'user_id' => $this->user->id,
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'http://math-service:8000/generate'
            && $request->hasHeader('X-Math-Token'));
    }

    public function test_grade_records_attempt_with_feedback(): void
    {
        Http::fake([
            'http://math-service:8000/grade' => Http::response([
                'correct' => true,
                'student_latex' => '-2',
                'expected_latex' => '-2',
                'feedback' => 'Resposta correta!',
            ], 200),
        ]);

        $template = ExerciseTemplate::forceCreate([
            'user_id' => $this->user->id,
            'title' => 'Equação',
            'kind' => 'numeric',
            'prompt' => 'Resolva x + 2 = 0',
            'parameters_spec' => [],
            'answer_expression' => '-2',
        ]);
        $variant = ExerciseVariant::forceCreate([
            'template_id' => $template->id,
            'user_id' => $this->user->id,
            'parameters' => ['a' => 1, 'b' => 2],
            'prompt_latex' => 'Resolva x + 2 = 0',
            'answer_expr' => '-2',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/grade', [
                'variant_id' => $variant->id,
                'answer' => '-2',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_correct' => true, 'feedback_latex' => 'Resposta correta!'],
            ]);

        $this->assertDatabaseHas('exercise_attempts', [
            'variant_id' => $variant->id,
            'user_id' => $this->user->id,
            'is_correct' => true,
            'graded_by' => 'sympy',
        ]);
    }

    public function test_grade_variant_from_another_user_is_not_found(): void
    {
        $other = User::factory()->create();
        $template = ExerciseTemplate::forceCreate([
            'user_id' => $other->id,
            'title' => 'Equação',
            'kind' => 'numeric',
            'prompt' => 'Resolva x + 2 = 0',
            'parameters_spec' => [],
            'answer_expression' => '-2',
        ]);
        $variant = ExerciseVariant::forceCreate([
            'template_id' => $template->id,
            'user_id' => $other->id,
            'parameters' => [],
            'prompt_latex' => 'Resolva x + 2 = 0',
            'answer_expr' => '-2',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/grade', [
                'variant_id' => $variant->id,
                'answer' => '-2',
            ]);

        $response->assertStatus(404)
            ->assertJson(['error' => ['code' => 'NOT_FOUND']]);
    }

    public function test_math_service_failure_returns_502(): void
    {
        Http::fake([
            'http://math-service:8000/grade' => Http::response('', 500),
        ]);

        $template = ExerciseTemplate::forceCreate([
            'user_id' => $this->user->id,
            'title' => 'Equação',
            'kind' => 'numeric',
            'prompt' => 'Resolva x + 2 = 0',
            'parameters_spec' => [],
            'answer_expression' => '-2',
        ]);
        $variant = ExerciseVariant::forceCreate([
            'template_id' => $template->id,
            'user_id' => $this->user->id,
            'parameters' => [],
            'prompt_latex' => 'Resolva x + 2 = 0',
            'answer_expr' => '-2',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/grade', [
                'variant_id' => $variant->id,
                'answer' => '-2',
            ]);

        $response->assertStatus(502)
            ->assertJson(['error' => ['code' => 'MATH_SERVICE_ERROR']]);
    }

    public function test_solve_calls_math_service(): void
    {
        Http::fake([
            'http://math-service:8000/solve' => Http::response([
                'solutions' => ['-2', '2'],
                'solution_latex' => '-2, 2',
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/solve', [
                'expression' => 'x^2 - 4 = 0',
                'variable' => 'x',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['solutions' => ['-2', '2']],
            ]);

        Http::assertSent(fn ($request) => $request->url() === 'http://math-service:8000/solve');
    }

    public function test_stats_computes_accuracy(): void
    {
        Http::fake(function ($request) {
            $payload = json_decode((string) $request->body(), true) ?? [];
            $answer = is_array($payload) ? ($payload['student_answer'] ?? '') : '';

            return Http::response([
                'correct' => $answer === '-2',
                'student_latex' => (string) $answer,
                'expected_latex' => '-2',
                'feedback' => $answer === '-2' ? 'Resposta correta!' : 'Resposta incorreta.',
            ], 200);
        });

        $template = ExerciseTemplate::forceCreate([
            'user_id' => $this->user->id,
            'title' => 'Equação',
            'kind' => 'numeric',
            'prompt' => 'Resolva x + 2 = 0',
            'parameters_spec' => [],
            'answer_expression' => '-2',
        ]);
        $variant = ExerciseVariant::forceCreate([
            'id' => '22222222-2222-2222-2222-222222222222',
            'template_id' => $template->id,
            'user_id' => $this->user->id,
            'parameters' => [],
            'prompt_latex' => 'Resolva x + 2 = 0',
            'answer_expr' => '-2',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/grade', [
                'variant_id' => $variant->id,
                'answer' => '-2',
            ])->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/grade', [
                'variant_id' => $variant->id,
                'answer' => '3',
            ])->assertStatus(200);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/exercises/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_attempts' => 2,
                    'correct_attempts' => 1,
                    'accuracy' => 0.5,
                ],
            ]);
    }

    public function test_attempts_lists_history(): void
    {
        Http::fake([
            'http://math-service:8000/grade' => Http::response([
                'correct' => true,
                'student_latex' => '-2',
                'expected_latex' => '-2',
                'feedback' => 'Resposta correta!',
            ], 200),
        ]);

        $template = ExerciseTemplate::forceCreate([
            'user_id' => $this->user->id,
            'title' => 'Equação',
            'kind' => 'numeric',
            'prompt' => 'Resolva x + 2 = 0',
            'parameters_spec' => [],
            'answer_expression' => '-2',
        ]);
        ExerciseVariant::forceCreate([
            'id' => '11111111-1111-1111-1111-111111111111',
            'template_id' => $template->id,
            'user_id' => $this->user->id,
            'parameters' => [],
            'prompt_latex' => 'Resolva x + 2 = 0',
            'answer_expr' => '-2',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/exercises/grade', [
                'variant_id' => '11111111-1111-1111-1111-111111111111',
                'answer' => '-2',
            ])->assertStatus(200);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/exercises/attempts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [['template_title' => 'Equação', 'is_correct' => true]],
            ]);
    }
}
