<?php

namespace Tests\Unit\Services;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Models\StudySession;
use App\Models\User;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use App\Modules\StudySessions\Repositories\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudySessions\Services\StudySessionService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class StudySessionServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    private StudySessionService $service;

    private StudySessionRepositoryInterface $repository;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->repository = Mockery::mock(StudySessionRepositoryInterface::class);
        $this->service = new StudySessionService($this->repository);
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_find_for_user_throws_404_when_not_found(): void
    {
        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with('nonexistent-id')
            ->andReturn(null);

        $this->expectException(ModelNotFoundException::class);
        $this->service->findForUser('nonexistent-id', $this->user->id);
    }

    public function test_find_for_user_throws_403_when_cross_user(): void
    {
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('getAttribute')->with('user_id')->andReturn('other-user-id');
        $session->shouldReceive('getAttribute')->with('id')->andReturn('session-1');
        $session->user_id = 'other-user-id';

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with('session-1')
            ->andReturn($session);

        $this->expectException(AuthorizationException::class);
        $this->service->findForUser('session-1', $this->user->id);
    }

    public function test_create_throws_when_dto_user_differs_from_caller(): void
    {
        $dto = new StudySessionDTO(
            userId: 'other-user-id',
            technologyId: 'tech-1',
            startedAt: Carbon::now(),
            endedAt: null,
            notes: null,
            mood: null,
        );

        $this->expectException(AuthorizationException::class);
        $this->service->create($this->user->id, $dto);
    }

    public function test_create_dispatches_created_event(): void
    {
        $session = new StudySession;
        $session->setRawAttributes(['id' => 'session-1', 'user_id' => $this->user->id]);

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->andReturn($session);

        $dto = new StudySessionDTO(
            userId: $this->user->id,
            technologyId: 'tech-1',
            startedAt: Carbon::now(),
            endedAt: null,
            notes: null,
            mood: null,
        );

        $result = $this->service->create($this->user->id, $dto);

        $this->assertEquals('session-1', $result->id);
        Event::assertDispatched(StudySessionCreated::class);
    }

    public function test_update_dispatches_updated_event(): void
    {
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('getAttribute')->with('user_id')->andReturn($this->user->id);
        $session->shouldReceive('getAttribute')->with('id')->andReturn('session-1');
        $session->user_id = $this->user->id;
        $session->id = 'session-1';

        $updatedSession = new StudySession;
        $updatedSession->setRawAttributes(['id' => 'session-1', 'notes' => 'Updated']);

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($session);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->andReturn($updatedSession);

        $result = $this->service->update('session-1', $this->user->id, ['notes' => 'Updated']);

        $this->assertEquals('Updated', $result->notes);
        Event::assertDispatched(StudySessionUpdated::class);
    }

    public function test_delete_dispatches_deleted_event_and_removes(): void
    {
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('getAttribute')->with('user_id')->andReturn($this->user->id);
        $session->shouldReceive('getAttribute')->with('id')->andReturn('session-1');
        $session->shouldReceive('getAttribute')->with('duration_min')->andReturn(60);
        $session->shouldReceive('getAttribute')->with('started_at')->andReturn(Carbon::now()->subHour());
        $session->user_id = $this->user->id;
        $session->id = 'session-1';
        $session->duration_min = 60;
        $session->started_at = Carbon::now()->subHour();

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($session);

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with($session);

        $this->service->delete('session-1', $this->user->id);

        Event::assertDispatched(StudySessionDeleted::class);
    }

    public function test_get_active_for_user_delegates_to_repository(): void
    {
        $session = new StudySession;
        $session->setRawAttributes(['id' => 'active-session']);

        $this->repository
            ->shouldReceive('findActiveByUser')
            ->once()
            ->with($this->user->id)
            ->andReturn($session);

        $result = $this->service->getActiveForUser($this->user->id);

        $this->assertEquals('active-session', $result->id);
    }

    public function test_get_active_for_user_returns_null_when_no_active(): void
    {
        $this->repository
            ->shouldReceive('findActiveByUser')
            ->once()
            ->with($this->user->id)
            ->andReturn(null);

        $result = $this->service->getActiveForUser($this->user->id);

        $this->assertNull($result);
    }
}
