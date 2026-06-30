<?php

namespace Tests\Unit\Resource;

use App\Http\Resources\TechnologyResource;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_includes_expected_fields(): void
    {
        $user = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);

        $resource = new TechnologyResource($tech);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('color', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}
