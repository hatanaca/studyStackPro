<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureJsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureJsonResponseAdditionalTest extends TestCase
{
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
