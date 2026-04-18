<?php

namespace Tests\Feature\Security;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PayloadInjectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Technology $technology;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();

        $this->user = User::factory()->create();
        $this->technology = Technology::create([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_register_ignores_extra_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'extra@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $user = User::where('email', 'extra@example.com')->first();
        $this->assertNotNull($user);
        $this->assertArrayNotHasKey('is_admin', $user->getAttributes());
    }

    public function test_session_create_ignores_user_id_in_payload(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'Ignorar user_id',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
                'user_id' => $otherUser->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertEquals($this->user->id, $response->json('data.user_id'));
    }

    public function test_session_create_ignores_duration_min(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'Ignorar duration',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
                'duration_min' => 999,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertNotEquals(999, $response->json('data.duration_min'));
    }

    public function test_technology_create_ignores_user_id_in_payload(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => 'Rust',
                'color' => '#DEA584',
                'user_id' => $otherUser->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertEquals($this->user->id, $response->json('data.user_id'));
    }

    public function test_sql_injection_in_technology_search(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson("/api/v1/technologies/search?q='.+DROP+TABLE+users.+--");

        $response->assertStatus(200);

        $tableExists = DB::select("SELECT to_regclass('public.users') AS exists");
        $this->assertNotNull($tableExists[0]->exists);
    }

    public function test_xss_in_session_notes(): void
    {
        $xssPayload = '<script>alert("xss")</script>';

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'Notas XSS',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
                'notes' => $xssPayload,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'notes' => $xssPayload,
                ],
            ]);
    }
}
