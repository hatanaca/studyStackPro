<?php

namespace Database\Seeders;

use App\Models\StudySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Cria sessões de demonstração para o usuário dev (dev@studytrack.local).
 * Depende de UserSeeder e TechnologySeeder já terem sido executados.
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO_USER_EMAIL = 'dev@studytrack.local';

    public function run(): void
    {
        $user = User::where('email', self::DEMO_USER_EMAIL)->first();
        if (! $user) {
            return;
        }

        $technologies = $user->technologies()->pluck('id')->toArray();
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
