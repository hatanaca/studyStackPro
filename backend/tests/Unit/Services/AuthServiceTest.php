<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;
    private AuthRepositoryInterface $repository;
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(AuthRepositoryInterface::class);
        $this->tokenService = Mockery::mock(TokenService::class)->makePartial();
        $this->service = new AuthService($this->repository, $this->tokenService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_creates_user_with_hashed_password(): void
    {
        $dto = new RegisterDTO(
            name: 'Test User',
            email: 'test@example.com',
            password: 'password123',
            timezone: 'America/Sao_Paulo',
        );

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['name'] === 'Test User'
                    && $data['email'] === 'test@example.com'
                    && Hash::check('password123', $data['password'])
                    && $data['timezone'] === 'America/Sao_Paulo';
            }))
            ->andReturn(new User(['id' => 'user-1', 'name' => 'Test User']));

        $result = $this->service->register($dto);

        $this->assertEquals('Test User', $result->name);
    }

    public function test_login_returns_user_on_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $this->repository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('test@example.com')
            ->andReturn($user);

        $this->tokenService
            ->shouldReceive('revokeMany')
            ->once();

        $dto = new LoginDTO(email: 'test@example.com', password: 'password123');
        $result = $this->service->login($dto);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result['user']->id);
    }

    public function test_login_returns_null_on_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $this->repository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('test@example.com')
            ->andReturn($user);

        $dto = new LoginDTO(email: 'test@example.com', password: 'wrong-password');
        $result = $this->service->login($dto);

        $this->assertNull($result);
    }

    public function test_login_returns_null_on_nonexistent_email(): void
    {
        $this->repository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('nonexistent@example.com')
            ->andReturn(null);

        $dto = new LoginDTO(email: 'nonexistent@example.com', password: 'password123');
        $result = $this->service->login($dto);

        $this->assertNull($result);
    }

    public function test_change_password_returns_true_on_success(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->tokenService
            ->shouldReceive('revokeMany')
            ->once();

        $this->repository
            ->shouldReceive('updatePassword')
            ->once()
            ->andReturn(true);

        $result = $this->service->changePassword($user, 'old-password', 'new-password');

        $this->assertTrue($result);
    }

    public function test_change_password_returns_false_on_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $result = $this->service->changePassword($user, 'wrong-password', 'new-password');

        $this->assertFalse($result);
    }
}
