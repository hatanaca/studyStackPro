<?php

namespace Tests\Feature\ItaStudy;

use App\Models\StudySubTopic;
use App\Models\StudySubject;
use App\Models\StudyTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStudyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private StudySubTopic $subTopic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;

        $subject = StudySubject::forceCreate([
            'name' => 'Matemática',
            'slug' => 'matematica',
            'icon' => 'pi-calculator',
            'color' => '#3B82F6',
            'sort_order' => 1,
        ]);
        $topic = StudyTopic::forceCreate([
            'subject_id' => $subject->id,
            'name' => 'Função quadrática',
            'slug' => 'funcao-quadratica',
            'difficulty' => 'fundamental',
            'sort_order' => 1,
        ]);
        $this->subTopic = StudySubTopic::forceCreate([
            'topic_id' => $topic->id,
            'name' => 'Vértice da parábola',
            'slug' => 'vertice-da-parabola',
            'sort_order' => 1,
            'description' => 'Como calcular o vértice.',
            'content' => [
                'blocks' => [
                    ['type' => 'heading', 'level' => 2, 'text' => 'Vértice'],
                    ['type' => 'paragraph', 'text' => 'O vértice é o ponto extremo da parábola.'],
                ],
            ],
            'faqs' => [
                ['question' => 'O que é o vértice?', 'answer' => 'É o ponto máximo ou mínimo.'],
            ],
            'simulation_config' => [
                'type' => 'function_plot',
                'functions' => ['x^2'],
                'sliders' => [],
            ],
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_subtopic_detail_returns_content(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', 'Vértice da parábola')
            ->assertJsonPath('data.simulation_config.type', 'function_plot')
            ->assertJsonCount(2, 'data.content.blocks')
            ->assertJsonCount(1, 'data.faqs')
            ->assertJsonPath('data.is_favorited', false);
    }

    public function test_subtopic_detail_404_when_missing(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ita-study/subtopics/00000000-0000-4000-8000-000000000000');

        $response->assertStatus(404)
            ->assertJson(['error' => ['code' => 'SUBTOPIC_NOT_FOUND']]);
    }

    public function test_favorite_lifecycle(): void
    {
        // Adiciona
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ita-study/favorites', [
                'sub_topic_id' => $this->subTopic->id,
            ])
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        // Lista
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ita-study/favorites')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sub_topic_id', $this->subTopic->id);

        // Detail reflete o favorito
        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}")
            ->assertJsonPath('data.is_favorited', true);

        // Remove
        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/ita-study/favorites/{$this->subTopic->id}")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ita-study/favorites')
            ->assertJsonCount(0, 'data');
    }

    public function test_note_lifecycle(): void
    {
        // Sem nota ainda
        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}/note")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        // Salva
        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}/note", [
                'content' => 'Revisar o discriminante.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.content', 'Revisar o discriminante.');

        // Atualiza
        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}/note", [
                'content' => 'Atualizado.',
            ])
            ->assertJsonPath('data.content', 'Atualizado.');

        // Deleta
        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}/note")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}/note")
            ->assertJsonPath('data', null);
    }

    public function test_reading_progress_update_and_read(): void
    {
        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}/reading-progress", [
                'progress' => 75.5,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.progress', 75.5);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}/reading-progress")
            ->assertStatus(200)
            ->assertJsonPath('data.progress', 75.5);
    }

    public function test_reading_progress_clamps_to_100(): void
    {
        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/ita-study/subtopics/{$this->subTopic->id}/reading-progress", [
                'progress' => 250,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.progress', 100);
    }
}
