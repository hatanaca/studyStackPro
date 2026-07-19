<?php

namespace Tests\Feature\LinkedIn;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class LinkedInOAuthTest extends TestCase
{
    use RefreshDatabase;

    private function validState(): string
    {
        return urlencode(Crypt::encryptString(
            json_encode(['ts' => time()])
        ));
    }

    private function makeSocialiteUser(array $data): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->id = $data['id'];
        $user->name = $data['name'] ?? null;
        $user->email = $data['email'] ?? null;
        $user->avatar = $data['avatar'] ?? null;
        $user->token = $data['token'] ?? null;
        $user->refreshToken = $data['refreshToken'] ?? null;
        $user->expiresIn = $data['expiresIn'] ?? null;

        return $user;
    }

    public function test_linkedin_redirect_returns_302(): void
    {
        $mockUser = $this->makeSocialiteUser([
            'id' => 'linkedin-123',
            'name' => 'Test User',
            'email' => 'test@linkedin.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        Socialite::shouldReceive('driver')->with('linkedin')->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('redirect')->andReturn(response()->redirectTo('https://linkedin.com/oauth', 302));

        $response = $this->get('/api/v1/auth/linkedin');

        $response->assertStatus(302);
    }

    public function test_invalid_provider_returns_404(): void
    {
        $response = $this->getJson('/api/v1/auth/invalid-provider');

        $response->assertStatus(404);
    }

    public function test_callback_creates_user_with_linkedin_id(): void
    {
        $mockUser = $this->makeSocialiteUser([
            'id' => 'linkedin-abc-123',
            'name' => 'LinkedIn User',
            'email' => 'linkedin@test.com',
            'avatar' => 'https://example.com/photo.jpg',
            'token' => 'access-token-123',
            'refreshToken' => 'refresh-token-123',
            'expiresIn' => 5184000,
        ]);

        Socialite::shouldReceive('driver')->with('linkedin')->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($mockUser);

        $response = $this->get('/api/v1/auth/linkedin/callback?state='.$this->validState());

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'linkedin@test.com',
            'linkedin_id' => 'linkedin-abc-123',
        ]);
    }

    public function test_callback_stores_encrypted_token(): void
    {
        $mockUser = $this->makeSocialiteUser([
            'id' => 'linkedin-abc-123',
            'name' => 'LinkedIn User',
            'email' => 'linkedin@test.com',
            'avatar' => 'https://example.com/photo.jpg',
            'token' => 'my-access-token',
            'refreshToken' => 'my-refresh-token',
            'expiresIn' => 5184000,
        ]);

        Socialite::shouldReceive('driver')->with('linkedin')->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($mockUser);

        $this->get('/api/v1/auth/linkedin/callback?state='.$this->validState());

        $user = User::where('email', 'linkedin@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->linkedin_token);
        // O cast 'encrypted' decriptografa automaticamente, então o valor lido é igual ao original
        $rawToken = $user->getRawOriginal('linkedin_token');
        $this->assertNotEquals('my-access-token', $rawToken);
        $this->assertNotNull($user->linkedin_refresh_token);
        $this->assertNotNull($user->linkedin_token_expires_at);
    }

    public function test_callback_links_to_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@test.com']);

        $mockUser = $this->makeSocialiteUser([
            'id' => 'linkedin-new-id',
            'name' => 'Existing User',
            'email' => 'existing@test.com',
            'avatar' => 'https://example.com/new-photo.jpg',
            'token' => 'token',
            'refreshToken' => null,
            'expiresIn' => null,
        ]);

        Socialite::shouldReceive('driver')->with('linkedin')->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($mockUser);

        $this->get('/api/v1/auth/linkedin/callback?state='.$this->validState());

        $existingUser->refresh();
        $this->assertEquals('linkedin-new-id', $existingUser->linkedin_id);
    }

    public function test_callback_redirects_to_frontend_on_success(): void
    {
        $mockUser = $this->makeSocialiteUser([
            'id' => 'linkedin-123',
            'name' => 'Test',
            'email' => 'test@test.com',
            'avatar' => null,
            'token' => 'token',
            'refreshToken' => null,
            'expiresIn' => null,
        ]);

        Socialite::shouldReceive('driver')->with('linkedin')->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($mockUser);

        $response = $this->get('/api/v1/auth/linkedin/callback?state='.$this->validState());

        $expectedPrefix = config('services.frontend_url').'/auth/callback?status=ok';
        $this->assertTrue(
            str_starts_with($response->headers->get('Location') ?? '', $expectedPrefix),
            'Redirect should start with '.$expectedPrefix
        );
    }
}
