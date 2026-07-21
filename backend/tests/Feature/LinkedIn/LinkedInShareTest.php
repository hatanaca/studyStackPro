<?php

namespace Tests\Feature\LinkedIn;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkedInShareTest extends TestCase
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

    public function test_status_returns_disconnected_when_no_linkedin(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/linkedin/status');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['connected' => false],
            ]);
    }

    public function test_status_returns_connected_when_has_linkedin(): void
    {
        $this->user->forceFill(['linkedin_id' => 'linkedin-123'])->save();

        Http::fake(function ($request) {
            return Http::response([
                'id' => 'linkedin-123',
                'localizedFirstName' => 'João',
                'localizedLastName' => 'Silva',
            ]);
        });

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/linkedin/status');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['connected' => true],
            ]);
    }

    public function test_share_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/linkedin/share', [
            'text' => 'Teste',
        ]);

        $response->assertUnauthorized();
    }

    public function test_share_returns_forbidden_when_not_connected(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/linkedin/share', [
                'text' => 'Teste',
            ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'LINKEDIN_NOT_CONNECTED'],
            ]);
    }

    public function test_share_validates_text_required(): void
    {
        $this->user->forceFill(['linkedin_id' => 'linkedin-123'])->save();

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/linkedin/share', []);

        $response->assertUnprocessable();
    }

    public function test_share_validates_text_max_length(): void
    {
        $this->user->forceFill(['linkedin_id' => 'linkedin-123'])->save();

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/linkedin/share', [
                'text' => str_repeat('a', 3001),
            ]);

        $response->assertUnprocessable();
    }

    public function test_share_posts_to_linkedin_successfully(): void
    {
        $this->user->forceFill([
            'linkedin_id' => 'abc123',
            'linkedin_token' => 'valid-token',
        ])->save();

        Http::fake(function ($request) {
            return Http::response([
                'id' => 'urn:li:share:789',
            ], 201);
        });

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/linkedin/share', [
                'text' => 'Estudei Laravel e Vue.js hoje!',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['id' => 'urn:li:share:789'],
            ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'ugcPosts')
                && str_contains($request->body(), 'Estudei Laravel e Vue.js hoje!');
        });
    }

    public function test_share_handles_api_error_gracefully(): void
    {
        $this->user->forceFill([
            'linkedin_id' => 'abc123',
            'linkedin_token' => 'expired-token',
        ])->save();

        Http::fake(function ($request) {
            return Http::response([
                'message' => 'Invalid token',
            ], 401);
        });

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/linkedin/share', [
                'text' => 'Teste',
            ]);

        $response->assertStatus(502)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'LINKEDIN_API_ERROR'],
            ]);
    }

    public function test_disconnect_removes_linkedin_data(): void
    {
        $this->user->forceFill([
            'linkedin_id' => 'abc123',
            'linkedin_token' => 'token',
            'linkedin_refresh_token' => 'refresh',
            'linkedin_token_expires_at' => now()->addDays(30),
        ])->save();

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/linkedin/disconnect');

        $response->assertOk();

        $this->user->refresh();
        $this->assertNull($this->user->linkedin_id);
        $this->assertNull($this->user->linkedin_token);
        $this->assertNull($this->user->linkedin_refresh_token);
    }
}
