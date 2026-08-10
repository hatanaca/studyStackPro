<?php

namespace Tests\Feature\Canvas;

use App\Models\CanvasArtwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanvasControllerTest extends TestCase
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

    public function test_index_lists_own_artworks(): void
    {
        $mine = CanvasArtwork::forceCreate(['user_id' => $this->user->id, 'title' => 'Esboço 1']);
        $other = CanvasArtwork::forceCreate(['user_id' => User::factory()->create()->id, 'title' => 'Alheio']);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/v1/canvas');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_store_creates_artwork(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/canvas', [
                'title' => 'Diagrama de fluxo',
                'canvas_data' => ['objects' => []],
                'width' => 800,
                'height' => 600,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['title' => 'Diagrama de fluxo', 'width' => 800, 'height' => 600],
            ]);

        $this->assertDatabaseHas('canvas_artworks', [
            'user_id' => $this->user->id,
            'title' => 'Diagrama de fluxo',
        ]);
    }

    public function test_show_own_artwork(): void
    {
        $artwork = CanvasArtwork::forceCreate(['user_id' => $this->user->id, 'title' => 'Esboço']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/canvas/{$artwork->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $artwork->id);
    }

    public function test_show_cross_user_artwork_is_forbidden(): void
    {
        $other = User::factory()->create();
        $artwork = CanvasArtwork::forceCreate(['user_id' => $other->id, 'title' => 'Alheio']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/canvas/{$artwork->id}");

        $response->assertStatus(403)
            ->assertJson(['error' => ['code' => 'FORBIDDEN']]);
    }

    public function test_update_artwork(): void
    {
        $artwork = CanvasArtwork::forceCreate(['user_id' => $this->user->id, 'title' => 'Antigo']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/canvas/{$artwork->id}", ['title' => 'Novo título']);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Novo título');
    }

    public function test_destroy_deletes_artwork(): void
    {
        $artwork = CanvasArtwork::forceCreate(['user_id' => $this->user->id, 'title' => 'Esboço']);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/canvas/{$artwork->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('canvas_artworks', ['id' => $artwork->id]);
    }
}
