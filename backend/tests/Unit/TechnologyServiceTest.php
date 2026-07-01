<?php

namespace Tests\Unit;

use App\Models\Technology;
use App\Models\User;
use App\Modules\Technologies\Services\TechnologyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TechnologyServiceTest extends TestCase
{
    use RefreshDatabase;

    private TechnologyService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->service = app(TechnologyService::class);
        $this->user = User::factory()->create();
    }

    public function test_list_for_user_returns_active_technologies(): void
    {
        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);

        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Old Tech',
            'slug' => 'old-tech',
            'color' => '#999999',
            'is_active' => false,
        ]);

        $result = $this->service->listForUser($this->user->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Laravel', $result->first()->name);
    }

    public function test_search_returns_matching_technologies(): void
    {
        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'color' => '#F7DF1E',
            'is_active' => true,
        ]);

        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'TypeScript',
            'slug' => 'typescript',
            'color' => '#3178C6',
            'is_active' => true,
        ]);

        Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Python',
            'slug' => 'python',
            'color' => '#3776AB',
            'is_active' => true,
        ]);

        $result = $this->service->search($this->user->id, 'script');

        $this->assertCount(2, $result);
    }

    public function test_search_respects_limit(): void
    {
        for ($i = 0; $i < 15; $i++) {
            Technology::forceCreate([
                'user_id' => $this->user->id,
                'name' => "Tech {$i}",
                'slug' => "tech-{$i}",
                'color' => '#000000',
                'is_active' => true,
            ]);
        }

        $result = $this->service->search($this->user->id, 'Tech', 5);

        $this->assertCount(5, $result);
    }

    public function test_find_for_user_returns_technology(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);

        $found = $this->service->findForUser($tech->id, $this->user->id);

        $this->assertEquals($tech->id, $found->id);
    }

    public function test_find_for_user_aborts_403_for_cross_user(): void
    {
        $otherUser = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $otherUser->id,
            'name' => 'Private',
            'slug' => 'private',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->service->findForUser($tech->id, $this->user->id);
    }

    public function test_create_returns_technology_with_generated_slug(): void
    {
        $data = ['name' => 'Vue.js', 'color' => '#42B883'];

        $tech = $this->service->create($this->user->id, $data);

        $this->assertNotNull($tech->id);
        $this->assertEquals('Vue.js', $tech->name);
        $this->assertEquals('vuejs', $tech->slug);
        $this->assertEquals($this->user->id, $tech->user_id);
    }

    public function test_update_modifies_technology_fields(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        $updated = $this->service->update($tech->id, $this->user->id, ['name' => 'PHP 8']);

        $this->assertEquals('PHP 8', $updated->name);
    }

    public function test_deactivate_sets_is_active_false(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Ruby',
            'slug' => 'ruby',
            'color' => '#CC342D',
            'is_active' => true,
        ]);

        $this->service->deactivate($tech->id, $this->user->id);

        $tech->refresh();
        $this->assertFalse($tech->is_active);
    }
}
