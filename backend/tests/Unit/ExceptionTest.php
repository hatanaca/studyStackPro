<?php

namespace Tests\Unit;

use App\Exceptions\ApiException;
use App\Exceptions\Domain\ConcurrentSessionException;
use Tests\TestCase;

class ExceptionTest extends TestCase
{
    public function test_api_exception_stores_message_and_status(): void
    {
        $exception = new class('Custom error', 422, 'CUSTOM_ERROR') extends ApiException {};

        $this->assertEquals('Custom error', $exception->getMessage());
        $this->assertEquals(422, $exception->statusCode);
        $this->assertEquals('CUSTOM_ERROR', $exception->errorCode);
    }

    public function test_api_exception_defaults(): void
    {
        $exception = new class extends ApiException {};

        $this->assertEquals('Erro na API', $exception->getMessage());
        $this->assertEquals(500, $exception->statusCode);
        $this->assertEquals('API_ERROR', $exception->errorCode);
    }

    public function test_concurrent_session_exception_has_correct_status(): void
    {
        $exception = new ConcurrentSessionException;

        $this->assertEquals(409, $exception->statusCode);
        $this->assertEquals('CONCURRENT_SESSION', $exception->errorCode);
    }

    public function test_concurrent_session_exception_default_message(): void
    {
        $exception = new ConcurrentSessionException;

        $this->assertEquals('O usuário já possui uma sessão ativa.', $exception->getMessage());
    }

    public function test_concurrent_session_exception_custom_message(): void
    {
        $exception = new ConcurrentSessionException('Custom message');

        $this->assertEquals('Custom message', $exception->getMessage());
    }

    public function test_concurrent_session_exception_is_api_exception(): void
    {
        $exception = new ConcurrentSessionException;

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
