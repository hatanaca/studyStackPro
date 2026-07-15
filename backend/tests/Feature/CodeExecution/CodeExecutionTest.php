<?php

namespace Tests\Feature\CodeExecution;

use App\Models\User;
use App\Modules\CodeExecution\Services\DockerSandboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CodeExecutionTest extends TestCase
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_execute_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/code/execute', [
            'code' => 'echo "hello"',
            'language' => 'bash',
        ]);

        $response->assertUnauthorized();
    }

    public function test_execute_validates_code_required(): void
    {
        $response = $this->withBearerToken($this->token)
            ->postJson('/api/v1/code/execute', [
                'language' => 'javascript',
            ]);

        $response->assertUnprocessable();
    }

    public function test_execute_validates_language_required(): void
    {
        $response = $this->withBearerToken($this->token)
            ->postJson('/api/v1/code/execute', [
                'code' => 'echo "hello"',
            ]);

        $response->assertUnprocessable();
    }

    public function test_execute_validates_language_enum(): void
    {
        $response = $this->withBearerToken($this->token)
            ->postJson('/api/v1/code/execute', [
                'code' => 'echo "hello"',
                'language' => 'invalid_lang',
            ]);

        $response->assertUnprocessable();
    }

    public function test_execute_validates_code_max_length(): void
    {
        $response = $this->withBearerToken($this->token)
            ->postJson('/api/v1/code/execute', [
                'code' => str_repeat('a', 10001),
                'language' => 'javascript',
            ]);

        $response->assertUnprocessable();
    }

    public function test_execute_javascript_returns_client_executor(): void
    {
        $response = $this->withBearerToken($this->token)
            ->postJson('/api/v1/code/execute', [
                'code' => 'console.log("hello")',
                'language' => 'javascript',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['executor' => 'client'],
            ]);
    }

    public function test_execute_lua_returns_client_executor(): void
    {
        $response = $this->withBearerToken($this->token)
            ->postJson('/api/v1/code/execute', [
                'code' => 'print("hello")',
                'language' => 'lua',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['executor' => 'client'],
            ]);
    }

    public function test_execute_html_returns_client_executor(): void
    {
        $response = $this->withBearerToken($this->token)
            ->postJson('/api/v1/code/execute', [
                'code' => '<html><body><h1>Hello</h1></body></html>',
                'language' => 'html',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['executor' => 'client'],
            ]);
    }

    public function test_execute_php_returns_backend_executor(): void
    {
        $sandbox = Mockery::mock(DockerSandboxService::class);
        $sandbox->shouldReceive('run')
            ->once()
            ->andReturn([
                'success' => true,
                'output' => 'hello',
                'error' => null,
                'executionTime' => 50,
            ]);
        $this->app->instance(DockerSandboxService::class, $sandbox);

        $response = $this->withBearerToken($this->token)
            ->postJson('/api/v1/code/execute', [
                'code' => '<?php echo "hello";',
                'language' => 'php',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['executor' => 'backend'],
            ]);
    }

    public function test_languages_returns_supported_list(): void
    {
        $response = $this->withBearerToken($this->token)
            ->getJson('/api/v1/code/languages');

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [],
            ]);

        $languages = $response->json('data');
        $this->assertCount(8, $languages);
    }
}
