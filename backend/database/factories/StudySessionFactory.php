<?php

namespace Database\Factories;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudySessionFactory extends Factory
{
    protected $model = StudySession::class;

    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-6 months', 'now');
        $endedAt = fake()->optional(0.8)->dateTimeBetween($startedAt, 'now');

        return [
            'user_id' => User::factory(),
            'technology_id' => Technology::factory(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'notes' => fake()->optional(0.4)->paragraph(),
            'mood' => fake()->optional(0.6)->numberBetween(1, 5),
            'focus_score' => fake()->optional(0.5)->numberBetween(1, 10),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
        ]);
    }
}
