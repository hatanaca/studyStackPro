<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTypeManipulationTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->token = $user->createToken('api-token')->plainTextToken;
    }

    public function test_post_with_xml_content_type_rejected(): void
    {
        $response = $this->call('POST', '/api/v1/technologies', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->token,
            'CONTENT_TYPE' => 'application/xml',
        ], '<tech><name>Hacker</name></tech>');

        $this->assertContains($response->getStatusCode(), [415, 422, 400]);
    }

    public function test_post_with_text_plain_content_type_rejected(): void
    {
        $response = $this->call('POST', '/api/v1/technologies', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->token,
            'CONTENT_TYPE' => 'text/plain',
        ], 'name=Hacker');

        $this->assertContains($response->getStatusCode(), [415, 422, 400]);
    }

    public function test_json_endpoint_requires_json_content_type(): void
    {
        $response = $this->call('POST', '/api/v1/technologies', [
            'name' => 'Test',
            'color' => '#000000',
        ], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->token,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ]);

        $this->assertContains($response->getStatusCode(), [415, 422]);
    }

    public function test_get_with_body_ignored_safely(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->withHeader('Content-Type', 'application/json')
            ->get('/api/v1/technologies', ['malicious' => 'data']);

        $response->assertStatus(200);
    }

    public function test_multipart_upload_for_json_endpoint_rejected(): void
    {
        $response = $this->call('POST', '/api/v1/technologies', [
            'name' => 'Hacker',
            'color' => '#000000',
        ], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->token,
            'CONTENT_TYPE' => 'multipart/form-data',
        ]);

        $this->assertContains($response->getStatusCode(), [415, 422]);
    }
}
