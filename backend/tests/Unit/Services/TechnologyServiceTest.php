<?php

namespace Tests\Unit\Services;

use App\Models\Technology;
use App\Models\User;
use App\Modules\Technologies\Repositories\Contracts\TechnologyRepositoryInterface;
use App\Modules\Technologies\Services\TechnologyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TechnologyServiceTest extends TestCase
{
    use RefreshDatabase;

    private TechnologyService $service;

    private TechnologyRepositoryInterface $repository;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(TechnologyRepositoryInterface::class);
        $this->service = new TechnologyService($this->repository);
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_for_user_delegates_to_repository(): void
    {
        $tech = new Technology;
        $tech->setRawAttributes(['name' => 'Laravel']);
        $techs = collect([$tech]);

        $this->repository
            ->shouldReceive('listForUser')
            ->once()
            ->with($this->user->id)
            ->andReturn($techs);

        $result = $this->service->listForUser($this->user->id);

        $this->assertCount(1, $result);
    }

    public function test_search_delegates_to_repository(): void
    {
        $tech = new Technology;
        $tech->setRawAttributes(['name' => 'Vue.js']);
        $techs = collect([$tech]);

        $this->repository
            ->shouldReceive('search')
            ->once()
            ->with($this->user->id, 'vue', 5)
            ->andReturn($techs);

        $result = $this->service->search($this->user->id, 'vue', 5);

        $this->assertCount(1, $result);
    }

    public function test_find_for_user_delegates_to_repository(): void
    {
        $tech = new Technology;
        $tech->setRawAttributes(['id' => 'tech-1', 'name' => 'Laravel']);

        $this->repository
            ->shouldReceive('findForUser')
            ->once()
            ->with('tech-1', $this->user->id)
            ->andReturn($tech);

        $result = $this->service->findForUser('tech-1', $this->user->id);

        $this->assertEquals('Laravel', $result->name);
    }

    public function test_deactivate_sets_is_active_false_and_invalidates_cache(): void
    {
        $tech = Mockery::mock(Technology::class);
        $tech->shouldReceive('update')->once()->with(['is_active' => false]);

        $this->repository
            ->shouldReceive('findForUser')
            ->once()
            ->with('tech-1', $this->user->id)
            ->andReturn($tech);

        $this->repository
            ->shouldReceive('invalidateCacheForUser')
            ->once()
            ->with($this->user->id);

        $this->service->deactivate('tech-1', $this->user->id);
    }
}
