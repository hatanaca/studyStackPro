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
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->withHeader('Content-Type', 'application/xml')
            ->post('/api/v1/technologies', '<tech><name>Hacker</name></tech>');

        $this->assertContains($response->getStatusCode(), [415, 422, 400]);
    }

    public function test_post_with_text_plain_content_type_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->withHeader('Content-Type', 'text/plain')
            ->post('/api/v1/technologies', 'name=Hacker');

        $this->assertContains($response->getStatusCode(), [415, 422, 400]);
    }

    public function test_json_endpoint_requires_json_content_type(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->post('/api/v1/technologies', [
                'name' => 'Test',
                'color' => '#000000',
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
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->withHeader('Content-Type', 'multipart/form-data')
            ->post('/api/v1/technologies', [
                'name' => 'Hacker',
                'color' => '#000000',
            ]);

        $this->assertContains($response->getStatusCode(), [415, 422]);
    }
}
