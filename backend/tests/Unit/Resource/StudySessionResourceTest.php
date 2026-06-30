<?php

namespace Tests\Unit\Resource;

use App\Http\Resources\StudySessionResource;
use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudySessionResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Technology $technology;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->technology = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);
    }

    public function test_resource_includes_expected_fields(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'title' => 'Test Session',
            'notes' => 'Some notes',
            'mood' => 4,
            'focus_score' => 8,
        ]);

        $resource = new StudySessionResource($session);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('technology_id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('notes', $array);
        $this->assertArrayHasKey('mood', $array);
        $this->assertArrayHasKey('focus_score', $array);
        $this->assertArrayHasKey('started_at', $array);
        $this->assertArrayHasKey('ended_at', $array);
        $this->assertArrayHasKey('duration_min', $array);
        $this->assertArrayHasKey('technology', $array);
    }

    public function test_resource_includes_technology_relation(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $resource = new StudySessionResource($session->load('technology'));
        $array = $resource->toArray(request());

        $this->assertNotNull($array['technology']);
        $this->assertEquals($this->technology->name, $array['technology']['name']);
    }

    public function test_resource_serializes_dates_to_iso8601(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => '2026-01-15T10:00:00Z',
            'ended_at' => '2026-01-15T11:00:00Z',
        ]);

        $resource = new StudySessionResource($session);
        $array = $resource->toArray(request());

        $this->assertIsString($array['started_at']);
        $this->assertIsString($array['ended_at']);
    }
}
