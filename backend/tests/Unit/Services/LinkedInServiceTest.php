<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Modules\LinkedIn\DTOs\LinkedInPostDTO;
use App\Modules\LinkedIn\Services\LinkedInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkedInServiceTest extends TestCase
{
    use RefreshDatabase;

    private LinkedInService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LinkedInService;
    }

    public function test_is_connected_returns_false_when_no_linkedin_id(): void
    {
        $user = User::factory()->create(['linkedin_id' => null]);

        $this->assertFalse($this->service->isConnected($user));
    }

    public function test_is_connected_returns_true_when_has_linkedin_id(): void
    {
        $user = User::factory()->create(['linkedin_id' => 'linkedin-123']);

        $this->assertTrue($this->service->isConnected($user));
    }

    public function test_share_post_makes_correct_http_request(): void
    {
        $user = User::factory()->create([
            'linkedin_id' => 'urn:li:person:abc123',
            'linkedin_token' => 'test-access-token',
        ]);

        Http::fake(function ($request) {
            return Http::response([
                'id' => 'urn:li:share:123456',
            ], 201);
        });

        $dto = new LinkedInPostDTO(text: 'Estudei Laravel hoje!');

        $result = $this->service->sharePost($user, $dto);

        $this->assertEquals('urn:li:share:123456', $result['id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.linkedin.com/v2/ugcPosts'
                && $request->method() === 'POST';
        });
    }

    public function test_share_post_throws_on_api_error(): void
    {
        $user = User::factory()->create([
            'linkedin_id' => 'urn:li:person:abc123',
            'linkedin_token' => 'expired-token',
        ]);

        Http::fake(function ($request) {
            return Http::response([
                'message' => 'Invalid access token',
                'status' => 401,
            ], 401);
        });

        $dto = new LinkedInPostDTO(text: 'Teste');

        $this->expectException(RequestException::class);

        $this->service->sharePost($user, $dto);
    }

    public function test_get_profile_returns_user_data(): void
    {
        $user = User::factory()->create([
            'linkedin_id' => 'abc123',
            'linkedin_token' => 'test-token',
        ]);

        Http::fake([
            'https://api.linkedin.com/v2/me' => Http::response([
                'id' => 'abc123',
                'localizedFirstName' => 'João',
                'localizedLastName' => 'Silva',
            ]),
        ]);

        $profile = $this->service->getProfile($user);

        $this->assertEquals('abc123', $profile['id']);
        $this->assertEquals('João', $profile['localizedFirstName']);
    }

    public function test_refresh_token_makes_correct_request(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'linkedin_id' => 'abc123',
            'linkedin_refresh_token' => 'refresh-token-123',
        ])->save();

        Http::fake([
            'https://www.linkedin.com/oauth/v2/accessToken' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 5184000,
            ]),
        ]);

        $this->service->refreshToken($user);

        $user->refresh();
        $this->assertNotNull($user->linkedin_token);
        $this->assertNotNull($user->linkedin_refresh_token);
        $this->assertNotNull($user->linkedin_token_expires_at);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'linkedin.com/oauth/v2/accessToken');
        });
    }
}
