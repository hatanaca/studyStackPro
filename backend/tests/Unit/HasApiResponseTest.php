<?php

namespace Tests\Unit;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class HasApiResponseTest extends TestCase
{
    private function decodeJson(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true) ?? [];
    }

    public function test_success_returns_json_with_success_true(): void
    {
        $controller = new class extends Controller
        {
            use \App\Traits\HasApiResponse;

            public function doSuccess(): JsonResponse
            {
                return $this->success(['key' => 'value'], 'OK', 200);
            }
        };

        $response = $controller->doSuccess();
        $data = $this->decodeJson($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('OK', $data['message']);
        $this->assertEquals('value', $data['data']['key']);
    }

    public function test_success_with_201_status(): void
    {
        $controller = new class extends Controller
        {
            use \App\Traits\HasApiResponse;

            public function doCreated(): JsonResponse
            {
                return $this->success(['id' => 1], 'Created', 201);
            }
        };

        $response = $controller->doCreated();
        $data = $this->decodeJson($response);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
    }

    public function test_error_returns_json_with_success_false(): void
    {
        $controller = new class extends Controller
        {
            use \App\Traits\HasApiResponse;

            public function doError(): JsonResponse
            {
                return $this->error('Not found', 'NOT_FOUND', null, 404);
            }
        };

        $response = $controller->doError();
        $data = $this->decodeJson($response);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('NOT_FOUND', $data['error']['code']);
        $this->assertEquals('Not found', $data['error']['message']);
    }

    public function test_error_includes_details_when_provided(): void
    {
        $controller = new class extends Controller
        {
            use \App\Traits\HasApiResponse;

            public function doErrorWithDetails(): JsonResponse
            {
                return $this->error('Validation failed', 'VALIDATION_ERROR', ['field' => ['error msg']], 422);
            }
        };

        $response = $controller->doErrorWithDetails();
        $data = $this->decodeJson($response);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('details', $data['error']);
    }
}
