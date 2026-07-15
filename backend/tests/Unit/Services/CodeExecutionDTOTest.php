<?php

namespace Tests\Unit\Services;

use App\Modules\CodeExecution\DTOs\CodeExecutionDTO;
use Tests\TestCase;

class CodeExecutionDTOTest extends TestCase
{
    public function test_dto_stores_code_and_language(): void
    {
        $dto = new CodeExecutionDTO(
            code: 'echo "hello"',
            language: 'bash',
            userId: 'user-123',
        );

        $this->assertEquals('echo "hello"', $dto->code);
        $this->assertEquals('bash', $dto->language);
        $this->assertEquals('user-123', $dto->userId);
    }

    public function test_dto_is_readonly(): void
    {
        $dto = new CodeExecutionDTO(
            code: 'test',
            language: 'javascript',
            userId: 'user-1',
        );

        $this->expectException(\Error::class);
        $dto->code = 'modified';
    }
}
