<?php

namespace Tests\Feature\Technologies;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyCrudTest extends TestCase
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

    public function test_index_returns_technologies_for_authenticated_user(): void
    {
        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Vue.js',
            'slug' => 'vuejs',
            'color' => '#42B883',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }

    public function test_store_creates_technology(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => 'TypeScript',
                'color' => '#3178C6',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Tecnologia criada.',
                'data' => [
                    'name' => 'TypeScript',
                    'slug' => 'typescript',
                ],
            ]);

        $this->assertDatabaseHas('technologies', [
            'user_id' => $this->user->id,
            'name' => 'TypeScript',
        ]);
    }

    public function test_show_returns_technology_for_owner(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies/'.$tech->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['name' => 'Laravel', 'slug' => 'laravel'],
            ]);
    }

    public function test_show_returns_403_for_cross_user(): void
    {
        $otherUser = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $otherUser->id,
            'name' => 'Outro',
            'slug' => 'outro',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies/'.$tech->id);

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'error' => ['code' => 'FORBIDDEN']]);
    }

    public function test_update_modifies_technology(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/v1/technologies/'.$tech->id, [
                'name' => 'PHP 8',
                'color' => '#777BB4',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['name' => 'PHP 8'],
            ]);
    }

    public function test_destroy_deactivates_technology(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Ruby',
            'slug' => 'ruby',
            'color' => '#CC342D',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/technologies/'.$tech->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('technologies', [
            'id' => $tech->id,
            'is_active' => false,
        ]);
    }
}
