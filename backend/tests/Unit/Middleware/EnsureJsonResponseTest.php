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
}
