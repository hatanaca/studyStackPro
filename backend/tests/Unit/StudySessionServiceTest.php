<?php

namespace Tests\Unit;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use App\Modules\StudySessions\DTOs\StudySessionFilterDTO;
use App\Modules\StudySessions\Services\StudySessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StudySessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudySessionService $service;

    private User $user;

    private Technology $technology;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
        $this->service = app(StudySessionService::class);
        $this->user = User::factory()->create();
        $this->technology = Technology::create([
            'user_id' => $this->user->id,
            'name' => 'Vue.js',
            'slug' => 'vuejs',
            'color' => '#42B883',
            'is_active' => true,
        ]);
    }

    public function test_list_for_user_returns_paginated_sessions(): void
    {
        StudySession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $filter = new StudySessionFilterDTO(perPage: 2);
        $result = $this->service->listForUser($this->user->id, $filter);

        $this->assertCount(2, $result->items());
        $this->assertEquals(3, $result->total());
        $this->assertEquals(2, $result->perPage());
    }

    public function test_list_for_user_filters_by_technology(): void
    {
        $tech2 = Technology::create([
            'user_id' => $this->user->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);
        StudySession::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'technology_id' => $tech2->id,
        ]);

        $filter = new StudySessionFilterDTO(technologyId: $tech2->id);
        $result = $this->service->listForUser($this->user->id, $filter);

        $this->assertCount(2, $result->items());
        foreach ($result->items() as $session) {
            $this->assertEquals($tech2->id, $session->technology_id);
        }
    }

    public function test_find_for_user_returns_session_when_owner(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $found = $this->service->findForUser($session->id, $this->user->id);

        $this->assertEquals($session->id, $found->id);
    }

    public function test_find_for_user_aborts_403_when_cross_user(): void
    {
        $otherUser = User::factory()->create();
        $otherTech = Technology::create([
            'user_id' => $otherUser->id,
            'name' => 'Outro',
            'slug' => 'outro',
            'color' => '#000',
            'is_active' => true,
        ]);
        $session = StudySession::factory()->create([
            'user_id' => $otherUser->id,
            'technology_id' => $otherTech->id,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionCode(403);

        $this->service->findForUser($session->id, $this->user->id);
    }

    public function test_create_dispatches_event(): void
    {
        $dto = new StudySessionDTO(
            userId: $this->user->id,
            technologyId: $this->technology->id,
            startedAt: Carbon::now(),
            endedAt: null,
            notes: null,
            mood: null,
        );

        $session = $this->service->create($this->user->id, $dto);

        $this->assertNotNull($session->id);
        $this->assertEquals($this->user->id, $session->user_id);
        $this->assertEquals($this->technology->id, $session->technology_id);
        Event::assertDispatched(StudySessionCreated::class);
    }

    public function test_update_dispatches_event(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'notes' => 'Original',
        ]);

        $updated = $this->service->update($session->id, $this->user->id, [
            'notes' => 'Atualizado',
        ]);

        $this->assertEquals('Atualizado', $updated->notes);
        Event::assertDispatched(StudySessionUpdated::class);
    }

    public function test_delete_dispatches_event_and_removes_session(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $this->service->delete($session->id, $this->user->id);

        $this->assertDatabaseMissing('study_sessions', ['id' => $session->id]);
        Event::assertDispatched(StudySessionDeleted::class);
    }
}
