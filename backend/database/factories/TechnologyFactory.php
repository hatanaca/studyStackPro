<?php

namespace Database\Factories;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TechnologyFactory extends Factory
{
    protected $model = Technology::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->randomElement(['#3498DB', '#2ECC71', '#E74C3C', '#9B59B6', '#F39C12', '#1ABC9C']),
            'icon' => null,
            'description' => fake()->optional(0.3)->sentence(),
            'is_active' => true,
        ];
    }
}
