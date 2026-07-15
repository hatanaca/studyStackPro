<?php

namespace Tests\Unit\Services;

use App\Modules\CodeExecution\Services\CodeExecutionService;
use App\Modules\CodeExecution\Services\DockerSandboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CodeExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CodeExecutionService $service;

    private DockerSandboxService $sandbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sandbox = Mockery::mock(DockerSandboxService::class);
        $this->service = new CodeExecutionService($this->sandbox);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_supported_languages_returns_all_languages(): void
    {
        $languages = $this->service->supportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('javascript', $languages);
        $this->assertContains('php', $languages);
        $this->assertContains('lua', $languages);
        $this->assertContains('html', $languages);
        $this->assertContains('css', $languages);
        $this->assertContains('sql', $languages);
        $this->assertContains('laravel', $languages);
        $this->assertContains('bash', $languages);
    }

    public function test_validate_rejects_empty_code(): void
    {
        $this->assertFalse($this->service->validate('', 'javascript'));
    }

    public function test_validate_rejects_invalid_language(): void
    {
        $this->assertFalse($this->service->validate('echo hello', 'invalid_lang'));
    }

    public function test_validate_accepts_valid_code_and_language(): void
    {
        $this->assertTrue($this->service->validate('console.log("hello")', 'javascript'));
    }

    public function test_validate_rejects_code_exceeding_limit(): void
    {
        $longCode = str_repeat('a', 10001);
        $this->assertFalse($this->service->validate($longCode, 'javascript'));
    }

    public function test_execute_php_delegates_to_sandbox(): void
    {
        $this->sandbox
            ->shouldReceive('run')
            ->once()
            ->with('<?php echo "hello";', 'php')
            ->andReturn([
                'success' => true,
                'output' => 'hello',
                'error' => null,
                'executionTime' => 50,
            ]);

        $result = $this->service->execute(new \App\Modules\CodeExecution\DTOs\CodeExecutionDTO(
            code: '<?php echo "hello";',
            language: 'php',
            userId: 'user-1',
        ));

        $this->assertTrue($result['success']);
        $this->assertEquals('hello', $result['output']);
    }

    public function test_execute_javascript_does_not_use_sandbox(): void
    {
        $this->sandbox
            ->shouldReceive('run')
            ->never();

        // JavaScript runs client-side, so execute() should return
        // a result indicating it should run on the client
        $result = $this->service->execute(new \App\Modules\CodeExecution\DTOs\CodeExecutionDTO(
            code: 'console.log("hello")',
            language: 'javascript',
            userId: 'user-1',
        ));

        $this->assertArrayHasKey('executor', $result);
        $this->assertEquals('client', $result['executor']);
    }
}
