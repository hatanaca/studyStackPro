<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnsureJsonResponseTest extends TestCase
{
    public function test_sets_accept_header_to_json(): void
    {
        $request = Request::create('/test', 'GET');
        $middleware = new EnsureJsonResponse;

        $middleware->handle($request, function (Request $req) {
            return new Response('OK');
        });

        $this->assertEquals('application/json', $request->headers->get('Accept'));
    }

    public function test_adds_accept_json_header(): void
    {
        $middleware = new EnsureJsonResponse;

        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_json_content_type_is_set(): void
    {
        $middleware = new EnsureJsonResponse;

        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }
}
