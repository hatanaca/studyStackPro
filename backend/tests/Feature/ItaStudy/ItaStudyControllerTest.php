<?php

namespace Tests\Feature\ItaStudy;

use App\Models\StudyQuestion;
use App\Models\StudySubject;
use App\Models\StudySubTopic;
use App\Models\StudyTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItaStudyControllerTest extends TestCase
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

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function seedChain(): array
    {
        $subject = StudySubject::forceCreate([
            'name' => 'Matemática',
            'slug' => 'matematica',
            'icon' => 'pi-calculator',
            'color' => '#3B82F6',
            'sort_order' => 1,
        ]);
        $topic = StudyTopic::forceCreate([
            'subject_id' => $subject->id,
            'name' => 'Conjuntos numéricos',
            'slug' => 'conjuntos-numericos',
            'difficulty' => 'fundamental',
            'sort_order' => 1,
        ]);
        $subTopic = StudySubTopic::forceCreate([
            'topic_id' => $topic->id,
            'name' => 'Números inteiros',
            'slug' => 'numeros-inteiros',
            'sort_order' => 1,
        ]);
        $question = StudyQuestion::forceCreate([
            'sub_topic_id' => $subTopic->id,
            'kind' => 'numeric',
            'prompt' => 'Qual é o resultado de {{a}} + {{b}}?',
            'parameters_spec' => [
                'a' => ['type' => 'int', 'min' => 1, 'max' => 5],
                'b' => ['type' => 'int', 'min' => 1, 'max' => 5],
            ],
            'answer_expression' => 'a + b',
            'answer_type' => 'numeric',
            'solution_latex' => 'a + b',
            'explanation' => 'Soma simples.',
            'difficulty' => 1,
            'has_graph' => false,
        ]);

        return compact('subject', 'topic', 'subTopic', 'question');
    }

    public function test_subjects_lists_with_progress(): void
    {
        $this->seedChain();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ita-study/subjects');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'matematica');
    }

    public function test_topics_for_subject(): void
    {
        $subject = $this->seedChain()['subject'];

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ita-study/subjects/{$subject->id}/topics");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_subtopics_for_topic(): void
    {
        $topic = $this->seedChain()['topic'];

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ita-study/topics/{$topic->id}/subtopics");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'numeros-inteiros');
    }

    public function test_generate_returns_question(): void
    {
        $subTopic = $this->seedChain()['subTopic'];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ita-study/questions/generate', [
                'sub_topic_id' => $subTopic->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['kind' => 'numeric'],
            ])
            ->assertJsonStructure(['data' => ['variant_id', 'prompt', 'difficulty']]);
    }

    public function test_generate_batch_returns_multiple_questions(): void
    {
        $subTopic = $this->seedChain()['subTopic'];

        // Cria mais duas questões para o batch ter 3 disponíveis.
        for ($i = 0; $i < 2; $i++) {
            StudyQuestion::forceCreate([
                'sub_topic_id' => $subTopic->id,
                'kind' => 'numeric',
                'prompt' => 'Questão extra {{a}}?',
                'parameters_spec' => ['a' => ['type' => 'int', 'min' => 1, 'max' => 5]],
                'answer_expression' => 'a',
                'answer_type' => 'numeric',
                'difficulty' => 1,
                'has_graph' => false,
            ]);
        }

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ita-study/questions/generate-batch', [
                'sub_topic_id' => $subTopic->id,
                'count' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_generate_batch_rejects_invalid_count(): void
    {
        $subTopic = $this->seedChain()['subTopic'];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ita-study/questions/generate-batch', [
                'sub_topic_id' => $subTopic->id,
                'count' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJson(['error' => ['code' => 'VALIDATION_ERROR']]);
    }

    public function test_answer_grades_and_records_attempt(): void
    {
        $subTopic = $this->seedChain()['subTopic'];

        $generated = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ita-study/questions/generate', [
                'sub_topic_id' => $subTopic->id,
            ])
            ->json('data');

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ita-study/questions/answer', [
                'variant_id' => $generated['variant_id'],
                'answer' => '42',
                'time_spent_seconds' => 10,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['attempt_id', 'is_correct', 'expected']]);
    }

    public function test_answer_rejects_unknown_variant(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ita-study/questions/answer', [
                'variant_id' => '00000000-0000-4000-8000-000000000000',
                'answer' => '1',
            ]);

        $response->assertStatus(422)
            ->assertJson(['error' => ['code' => 'VARIANT_NOT_FOUND']]);
    }

    public function test_progress_returns_overview(): void
    {
        $this->seedChain();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ita-study/progress');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['overall']]);
    }
}
