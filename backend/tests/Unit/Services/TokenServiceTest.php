<?php

namespace Tests\Unit\Services;

use App\Modules\Auth\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TokenService();
    }

    public function test_revoke_deletes_token_from_database(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test-token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->getKey(),
        ]);

        $this->service->revoke($token->accessToken);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->getKey(),
        ]);
    }

    public function test_revoke_many_returns_count_of_revoked_tokens(): void
    {
        $user = \App\Models\User::factory()->create();
        $token1 = $user->createToken('token-1');
        $token2 = $user->createToken('token-2');
        $token3 = $user->createToken('token-3');

        $count = $this->service->revokeMany([
            $token1->accessToken,
            $token2->accessToken,
            $token3->accessToken,
        ]);

        $this->assertEquals(3, $count);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token1->accessToken->getKey(),
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token2->accessToken->getKey(),
        ]);
    }

    public function test_revoke_many_returns_zero_for_empty_array(): void
    {
        $count = $this->service->revokeMany([]);

        $this->assertEquals(0, $count);
    }

    public function test_revoke_many_with_traversable(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('traversable-token');

        $collection = collect([$token->accessToken]);
        $count = $this->service->revokeMany($collection);

        $this->assertEquals(1, $count);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->getKey(),
        ]);
    }
}
