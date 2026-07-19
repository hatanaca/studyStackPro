<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\LogApiRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LogApiRequestsTest extends TestCase
{
    use RefreshDatabase;

    private LogApiRequests $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new LogApiRequests;
    }

    public function test_handle_sets_request_start_attribute(): void
    {
        $request = new Request;
        $response = new Response('ok');

        $result = $this->middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertNotNull($request->attributes->get('_request_start'));
    }

    public function test_handle_preserves_response(): void
    {
        $request = new Request;
        $expectedResponse = new Response('test body', 201);

        $result = $this->middleware->handle($request, function ($req) use ($expectedResponse) {
            return $expectedResponse;
        });

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('test body', $result->getContent());
    }

    public function test_terminate_logs_api_request(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('API Request', Mockery::on(function ($data) {
                return isset($data['method'])
                    && isset($data['path'])
                    && isset($data['status'])
                    && isset($data['duration_ms']);
            }));

        $request = Request::create('/api/v1/test', 'GET');
        $response = new Response('ok', 200);

        $this->middleware->terminate($request, $response);
    }

    public function test_handle_with_different_http_methods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            $request = Request::create('/api/v1/test', $method);
            $response = new Response('ok');

            $result = $this->middleware->handle($request, function ($req) use ($response) {
                return $response;
            });

            $this->assertInstanceOf(Response::class, $result);
        }
    }
}
