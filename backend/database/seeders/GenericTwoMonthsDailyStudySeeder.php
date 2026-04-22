<?php

namespace Database\Seeders;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenericTwoMonthsDailyStudySeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const TECHNOLOGIES = [
        'Tecnologia A',
        'Tecnologia B',
        'Tecnologia C',
        'Tecnologia D',
        'Tecnologia E',
    ];

    public function run(): void
    {
        $timezone = 'America/Sao_Paulo';

        $user = User::updateOrCreate(
            ['email' => 'usuario.generico@studytrack.local'],
            [
                'name' => 'Usuario Generico',
                'password' => Hash::make('password'),
                'timezone' => $timezone,
                'locale' => 'pt_BR',
            ]
        );

        $technologyByName = [];
        Model::unguarded(function () use ($user, &$technologyByName) {
            foreach (self::TECHNOLOGIES as $index => $name) {
                $technology = Technology::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'slug' => Str::slug($name),
                    ],
                    [
                        'name' => $name,
                        'color' => $this->colorByIndex($index),
                        'description' => 'Tecnologia generica para dados de estudo diario',
                        'is_active' => true,
                    ]
                );

                $technologyByName[$name] = $technology;
            }
        });

        // Reimportacao limpa para evitar duplicidade.
        $user->studySessions()->delete();

        $start = CarbonImmutable::now($timezone)->subMonth()->startOfMonth();
        $end = CarbonImmutable::now($timezone)->endOfDay();
        $currentDay = $start;
        $dayIndex = 0;

        while ($currentDay->lte($end)) {
            $techName = self::TECHNOLOGIES[$dayIndex % count(self::TECHNOLOGIES)];
            $technology = $technologyByName[$techName];

            $durationMin = $this->durationByDayIndex($dayIndex);
            $startedAt = $currentDay->setTime(19, 0);
            $endedAt = $startedAt->addMinutes($durationMin);

            StudySession::forceCreate([
                'user_id' => $user->id,
                'technology_id' => $technology->id,
                'started_at' => $startedAt->toIso8601String(),
                'ended_at' => $endedAt->toIso8601String(),
                'notes' => 'Sessao diaria generica importada automaticamente',
                'mood' => null,
                'focus_score' => null,
            ]);

            $currentDay = $currentDay->addDay();
            $dayIndex++;
        }
    }

    private function durationByDayIndex(int $dayIndex): int
    {
        $options = [45, 60, 75, 90, 120];

        return $options[$dayIndex % count($options)];
    }

    private function colorByIndex(int $index): string
    {
        $palette = [
            '#3B82F6',
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#8B5CF6',
        ];

        return $palette[$index % count($palette)];
    }
}
