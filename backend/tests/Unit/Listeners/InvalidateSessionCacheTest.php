<?php

namespace Tests\Unit\Listeners;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Listeners\StudySession\InvalidateSessionCache;
use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class InvalidateSessionCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_flushes_session_cache_on_created(): void
    {
        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $taggedCache = Mockery::mock();
        $taggedCache->shouldReceive('flush')->once();
        Cache::shouldReceive('tags')
            ->with(['sessions', "sessions:user:{$userId}"])
            ->once()
            ->andReturn($taggedCache);

        $listener = new InvalidateSessionCache;
        $listener->handle(new StudySessionCreated($session));

        $this->addToAssertionCount(1);
    }

    public function test_flushes_session_cache_on_updated(): void
    {
        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $taggedCache = Mockery::mock();
        $taggedCache->shouldReceive('flush')->once();
        Cache::shouldReceive('tags')
            ->with(['sessions', "sessions:user:{$userId}"])
            ->once()
            ->andReturn($taggedCache);

        $listener = new InvalidateSessionCache;
        $listener->handle(new StudySessionUpdated($session, ['notes']));

        $this->addToAssertionCount(1);
    }

    public function test_flushes_session_cache_on_deleted(): void
    {
        $userId = (string) Str::uuid();

        $taggedCache = Mockery::mock();
        $taggedCache->shouldReceive('flush')->once();
        Cache::shouldReceive('tags')
            ->with(['sessions', "sessions:user:{$userId}"])
            ->once()
            ->andReturn($taggedCache);

        $event = new StudySessionDeleted($userId, (string) Str::uuid(), 45, Carbon::now());

        $listener = new InvalidateSessionCache;
        $listener->handle($event);

        $this->addToAssertionCount(1);
    }
}
