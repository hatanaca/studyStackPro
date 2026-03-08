<?php

namespace Database\Seeders;

use App\Models\StudySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@studytrack.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'timezone' => 'America/Sao_Paulo',
            ]
        );

        $technologies = $user->technologies()->pluck('id')->toArray();
        if (empty($technologies)) {
            $techs = [
                ['name' => 'JavaScript', 'color' => '#F7DF1E'],
                ['name' => 'TypeScript', 'color' => '#3178C6'],
                ['name' => 'Vue.js', 'color' => '#42B883'],
                ['name' => 'Laravel', 'color' => '#FF2D20'],
                ['name' => 'PostgreSQL', 'color' => '#4169E1'],
            ];
            foreach ($techs as $t) {
                $tech = $user->technologies()->firstOrCreate(
                    ['slug' => Str::slug($t['name'])],
                    ['name' => $t['name'], 'color' => $t['color'], 'is_active' => true]
                );
                $technologies[] = $tech->id;
            }
        }
        if (empty($technologies)) {
            return;
        }

        $startDate = Carbon::now()->subMonths(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        for ($day = 0; $day <= $startDate->diffInDays($endDate); $day++) {
            $date = $startDate->copy()->addDays($day);

            if ($date->isWeekend() && rand(1, 3) === 1) {
                continue;
            }

            $sessionsPerDay = rand(0, 3);
            if ($sessionsPerDay === 0 && rand(1, 5) > 2) {
                continue;
            }

            $current = $date->copy()->setTime(rand(8, 14), rand(0, 59));

            for ($s = 0; $s < $sessionsPerDay; $s++) {
                $durationMin = [15, 25, 30, 45, 60, 90, 120][array_rand([15, 25, 30, 45, 60, 90, 120])];
                $techId = $technologies[array_rand($technologies)];

                $endedAt = $current->copy()->addMinutes($durationMin);
                if ($endedAt->gt($endDate)) {
                    break;
                }

                StudySession::create([
                    'user_id' => $user->id,
                    'technology_id' => $techId,
                    'started_at' => $current->toIso8601String(),
                    'ended_at' => $endedAt->toIso8601String(),
                    'notes' => rand(1, 5) === 1 ? 'Sessão de demonstração' : null,
                    'mood' => rand(1, 5) === 1 ? rand(3, 5) : null,
                    'focus_score' => rand(1, 4) === 1 ? rand(1, 10) : null,
                ]);

                $current = $endedAt->copy()->addMinutes(rand(30, 180));
            }
        }
    }
}
