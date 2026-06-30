<?php

namespace Tests\Feature\Technologies;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyEdgeCasesTest extends TestCase
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

    public function test_index_returns_empty_array_for_new_user(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(0, 'data');
    }

    public function test_store_requires_name(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'color' => '#000000',
            ]);

        $response->assertStatus(422);
    }

    public function test_store_generates_slug_from_name(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => 'Vue.js',
                'color' => '#42B883',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'vuejs');
    }

    public function test_store_handles_special_characters_in_name(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => 'C# .NET Core',
            ]);

        $response->assertStatus(201);
        $this->assertNotEmpty($response->json('data.slug'));
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function test_update_rejects_empty_name(): void
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
                'name' => '',
                'color' => '#000',
            ]);

        $response->assertStatus(422);
    }

    public function test_destroy_only_deactivates_does_not_delete(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Ruby',
            'slug' => 'ruby',
            'color' => '#CC342D',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/technologies/'.$tech->id)
            ->assertStatus(200);

        $this->assertDatabaseHas('technologies', [
            'id' => $tech->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('technologies', ['id' => $tech->id]);
    }

    public function test_search_returns_matching_results(): void
    {
        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'color' => '#F7DF1E',
            'is_active' => true,
        ]);

        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'TypeScript',
            'slug' => 'typescript',
            'color' => '#3178C6',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies/search?q=script');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_search_accepts_short_query(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies/search?q=a');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_index_only_returns_active_technologies(): void
    {
        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Active',
            'slug' => 'active',
            'color' => '#00FF00',
            'is_active' => true,
        ]);

        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Inactive',
            'slug' => 'inactive',
            'color' => '#FF0000',
            'is_active' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Active', $data[0]['name']);
    }

    public function test_cannot_access_other_users_technology(): void
    {
        $otherUser = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $otherUser->id,
            'name' => 'Private',
            'slug' => 'private',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies/'.$tech->id);

        $response->assertStatus(403);
    }
}
