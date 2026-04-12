<?php

namespace Tests\Feature\Contract;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyContractTest extends TestCase
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

    public function test_technology_resource_contains_required_fields(): void
    {
        $tech = Technology::create([
            'user_id' => $this->user->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson("/api/v1/technologies/{$tech->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $requiredKeys = [
            'id', 'user_id', 'name', 'slug', 'color', 'icon',
            'description', 'is_active', 'created_at', 'updated_at',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Missing key: {$key}");
        }
    }

    public function test_technology_list_returns_array(): void
    {
        Technology::create([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        Technology::create([
            'user_id' => $this->user->id,
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'color' => '#F7DF1E',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function test_deactivated_technology_not_in_list(): void
    {
        $tech = Technology::create([
            'user_id' => $this->user->id,
            'name' => 'Ruby',
            'slug' => 'ruby',
            'color' => '#CC342D',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson("/api/v1/technologies/{$tech->id}");

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies');

        $response->assertStatus(200);

        $data = $response->json('data');
        $ids = array_column($data, 'id');
        $this->assertNotContains($tech->id, $ids);
    }
}
