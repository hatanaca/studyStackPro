<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SQLInjectionTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->token = $user->createToken('api-token')->plainTextToken;
    }

    public function test_sql_injection_in_login_email(): void
    {
        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/login', [
                'email' => "' OR '1'='1' --",
                'password' => 'password',
            ]);

        $response->assertStatus(422);

        $tableExists = DB::select("SELECT to_regclass('public.users') AS exists");
        $this->assertNotNull($tableExists[0]->exists);
    }

    public function test_sql_injection_in_technology_name(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/technologies', [
                'name' => "'; DROP TABLE users; --",
                'color' => '#000000',
            ]);

        $response->assertStatus(201);
        $tableExists = DB::select("SELECT to_regclass('public.users') AS exists");
        $this->assertNotNull($tableExists[0]->exists);
    }

    public function test_sql_injection_in_session_search(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/technologies/search?q='+UNION+SELECT+*+FROM+users+--");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_sql_injection_in_session_notes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => '00000000-0000-0000-0000-000000000000',
                'title' => "'; UPDATE users SET is_admin=1 WHERE email='test@test.com'; --",
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        $this->assertContains($response->getStatusCode(), [201, 422, 403, 404]);
        $admin = User::where('email', 'test@test.com')->first();
        if ($admin) {
            $this->assertFalse((bool) ($admin->is_admin ?? false));
        }
    }

    public function test_time_based_sql_injection_in_date_filter(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/study-sessions?date_from=' OR SLEEP(5)--");

        $response->assertStatus(422);
    }
}
