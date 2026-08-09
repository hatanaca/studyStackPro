<?php

namespace Tests\Feature\Reminders;

use App\Models\Reminder;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_index_lists_own_reminders(): void
    {
        $mine = Reminder::forceCreate(['user_id' => $this->user->id, 'text' => 'Revisar aula 3']);
        $other = Reminder::forceCreate(['user_id' => User::factory()->create()->id, 'text' => 'Alheio']);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/v1/reminders');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_index_filters_by_technology(): void
    {
        $tech = Technology::factory()->create(['user_id' => $this->user->id]);
        Reminder::forceCreate(['user_id' => $this->user->id, 'text' => 'Com tech', 'technology_id' => $tech->id]);
        Reminder::forceCreate(['user_id' => $this->user->id, 'text' => 'Sem tech']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/reminders?technology_id={$tech->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.text', 'Com tech');
    }

    public function test_store_creates_reminder(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/reminders', ['text' => 'Revisar derivadas']);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['text' => 'Revisar derivadas'],
            ]);

        $this->assertDatabaseHas('reminders', [
            'user_id' => $this->user->id,
            'text' => 'Revisar derivadas',
        ]);
    }

    public function test_store_rejects_empty_text(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/reminders', ['text' => '']);

        $response->assertStatus(422)
            ->assertJson(['error' => ['code' => 'VALIDATION_ERROR']]);
    }

    public function test_show_own_reminder(): void
    {
        $reminder = Reminder::forceCreate(['user_id' => $this->user->id, 'text' => 'Lembrete']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/reminders/{$reminder->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $reminder->id);
    }

    public function test_show_cross_user_reminder_is_forbidden(): void
    {
        $other = User::factory()->create();
        $reminder = Reminder::forceCreate(['user_id' => $other->id, 'text' => 'Alheio']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/reminders/{$reminder->id}");

        $response->assertStatus(403)
            ->assertJson(['error' => ['code' => 'FORBIDDEN']]);
    }

    public function test_update_marks_completed(): void
    {
        $reminder = Reminder::forceCreate(['user_id' => $this->user->id, 'text' => 'Revisar']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/reminders/{$reminder->id}", ['completed' => true]);

        $response->assertStatus(200)
            ->assertJsonPath('data.completed', true);
    }

    public function test_destroy_deletes_reminder(): void
    {
        $reminder = Reminder::forceCreate(['user_id' => $this->user->id, 'text' => 'Revisar']);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/reminders/{$reminder->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }
}
