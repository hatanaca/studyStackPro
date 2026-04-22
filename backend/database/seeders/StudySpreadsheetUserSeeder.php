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

class StudySpreadsheetUserSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const SUBJECTS = [
        'HTML/CSS',
        'Javascript',
        'Redes',
        'Ingles',
        'Logica Computacional aplicada em programacao',
        'Revisoes',
        'GIT/GITHUB',
        'MYSql',
        'PHP',
        'Linux',
        'Apache',
        'Programacao em C',
        'DSA',
        'Angular',
        'Vue.js',
        'Typescript',
        'AWS',
        'Pipeline',
        'Oauth 2.0',
        'REST API',
        'Docker',
        'Laravel',
        'OOP',
        'Memory',
        'Audio Visual',
    ];

    /**
     * Distribuicao mensal por tecnologia extraida da planilha enviada.
     *
     * @var array<int, array{label: string, date: string, hours: array<string, string>}>
     */
    private const MONTHLY_BREAKDOWN = [
        ['label' => '2015/2017', 'date' => '2016-01-15 08:00:00', 'hours' => ['Ingles' => '215:00:00']],
        ['label' => '2020/2021', 'date' => '2020-07-15 08:00:00', 'hours' => ['HTML/CSS' => '120:00:00', 'Javascript' => '57:00:00']],
        ['label' => '2022/dezembro', 'date' => '2022-12-15 08:00:00', 'hours' => ['Redes' => '17:35:00']],
        ['label' => '2023/janeiro', 'date' => '2023-01-15 08:00:00', 'hours' => ['Redes' => '9:40:00']],
        ['label' => '2023/fevereiro', 'date' => '2023-02-15 08:00:00', 'hours' => ['Redes' => '3:40:00']],
        [
            'label' => '2023/marco',
            'date' => '2023-03-15 08:00:00',
            'hours' => [
                'HTML/CSS' => '1:00:00',
                'Javascript' => '2:00:00',
                'Redes' => '15:09:00',
                'Ingles' => '5:11:00',
                'Revisoes' => '1:25:00',
            ],
        ],
        [
            'label' => '2023/abril',
            'date' => '2023-04-15 08:00:00',
            'hours' => [
                'Javascript' => '2:50:00',
                'Redes' => '9:18:00',
                'Logica Computacional aplicada em programacao' => '18:38:00',
                'Revisoes' => '1:00:00',
            ],
        ],
        [
            'label' => '2023/maio',
            'date' => '2023-05-15 08:00:00',
            'hours' => [
                'Redes' => '0:40:00',
                'Logica Computacional aplicada em programacao' => '26:48:00',
            ],
        ],
        ['label' => '2023/junho', 'date' => '2023-06-15 08:00:00', 'hours' => ['Redes' => '20:56:00']],
        ['label' => '2023/julho', 'date' => '2023-07-15 08:00:00', 'hours' => ['Redes' => '49:03:00']],
        ['label' => '2023/agosto', 'date' => '2023-08-15 08:00:00', 'hours' => ['Redes' => '38:58:00']],
        [
            'label' => '2023/setembro',
            'date' => '2023-09-15 08:00:00',
            'hours' => ['Javascript' => '14:16:00', 'Redes' => '13:45:00'],
        ],
        ['label' => '2023/outubro', 'date' => '2023-10-15 08:00:00', 'hours' => ['HTML/CSS' => '2:00:00', 'Javascript' => '33:32:00']],
        ['label' => '2023/novembro', 'date' => '2023-11-15 08:00:00', 'hours' => ['Javascript' => '7:00:00']],
        ['label' => '2023/dezembro', 'date' => '2023-12-15 08:00:00', 'hours' => ['Javascript' => '45:41:00']],
        ['label' => '2024/janeiro', 'date' => '2024-01-15 08:00:00', 'hours' => ['Javascript' => '47:21:00']],
        [
            'label' => '2024/fevereiro',
            'date' => '2024-02-15 08:00:00',
            'hours' => ['Redes' => '3:31:00', 'GIT/GITHUB' => '7:00:00', 'MYSql' => '19:20:00', 'PHP' => '9:20:00'],
        ],
        [
            'label' => '2024/marco',
            'date' => '2024-03-15 08:00:00',
            'hours' => [
                'HTML/CSS' => '9:05:00',
                'Javascript' => '0:40:00',
                'Redes' => '2:00:00',
                'GIT/GITHUB' => '2:20:00',
                'PHP' => '15:59:00',
            ],
        ],
        [
            'label' => '2024/abril',
            'date' => '2024-04-15 08:00:00',
            'hours' => ['Redes' => '5:00:00', 'PHP' => '5:09:00', 'Linux' => '3:20:00', 'Apache' => '6:00:00'],
        ],
        ['label' => '2024/maio', 'date' => '2024-05-15 08:00:00', 'hours' => ['Linux' => '29:17:00']],
        ['label' => '2024/junho', 'date' => '2024-06-15 08:00:00', 'hours' => ['Linux' => '34:50:00']],
        ['label' => '2024/julho', 'date' => '2024-07-15 08:00:00', 'hours' => ['Linux' => '25:50:00']],
        ['label' => '2024/agosto', 'date' => '2024-08-15 08:00:00', 'hours' => ['Linux' => '12:20:00']],
        ['label' => '2024/setembro', 'date' => '2024-09-15 08:00:00', 'hours' => ['Redes' => '4:53:00', 'Linux' => '4:00:00']],
        ['label' => '2024/outubro', 'date' => '2024-10-15 08:00:00', 'hours' => ['PHP' => '4:30:00']],
        ['label' => '2024/dezembro', 'date' => '2024-12-15 08:00:00', 'hours' => ['Linux' => '12:40:00']],
        [
            'label' => '2025/janeiro',
            'date' => '2025-01-15 08:00:00',
            'hours' => ['Javascript' => '5:35:00', 'GIT/GITHUB' => '1:30:00', 'Linux' => '13:27:00', 'Vue.js' => '3:00:00'],
        ],
        [
            'label' => '2025/fevereiro',
            'date' => '2025-02-15 08:00:00',
            'hours' => [
                'HTML/CSS' => '19:13:00',
                'Javascript' => '19:13:00',
                'Linux' => '2:41:00',
                'Vue.js' => '9:05:00',
                'Docker' => '4:57:00',
                'Laravel' => '5:10:00',
            ],
        ],
        ['label' => '2025/marco', 'date' => '2025-03-15 08:00:00', 'hours' => ['Linux' => '5:56:00', 'Docker' => '15:41:00', 'Laravel' => '19:53:00']],
        ['label' => '2025/abril', 'date' => '2025-04-15 08:00:00', 'hours' => ['Vue.js' => '3:30:00', 'Docker' => '9:30:00', 'Laravel' => '10:50:00']],
        ['label' => '2025/maio', 'date' => '2025-05-15 08:00:00', 'hours' => ['Vue.js' => '1:00:00', 'Docker' => '2:40:00', 'Laravel' => '14:05:00']],
        ['label' => '2025/julho', 'date' => '2025-07-15 08:00:00', 'hours' => ['Audio Visual' => '80:00:00']],
        ['label' => '2025/agosto', 'date' => '2025-08-15 08:00:00', 'hours' => ['Audio Visual' => '80:00:00']],
        ['label' => '2025/setembro', 'date' => '2025-09-15 08:00:00', 'hours' => ['Audio Visual' => '80:00:00']],
        ['label' => '2025/outubro', 'date' => '2025-10-15 08:00:00', 'hours' => ['Memory' => '9:10:00']],
        ['label' => '2025/novembro', 'date' => '2025-11-15 08:00:00', 'hours' => ['Redes' => '15:32', 'OOP' => '17:52:00']],
        ['label' => '2025/dezembro', 'date' => '2025-12-15 08:00:00', 'hours' => ['Pipeline' => '08:39:00']],
    ];

    /**
     * @var array<int, array{name: string, start: string, end: string, duration: string, cert_duration: string}>
     */
    private const COMPLETED_COURSES = [
        [
            'name' => 'Arquitetura de Redes - Udemy',
            'start' => '23/01/2023',
            'end' => '05/07/2023',
            'duration' => '76:00:00',
            'cert_duration' => '24:00:00',
        ],
        [
            'name' => 'DNS Deep Dive - Udemy',
            'start' => '01/12/2022',
            'end' => '20/01/2023',
            'duration' => '23:15:00',
            'cert_duration' => '4:30:00',
        ],
        [
            'name' => 'Redes TCP/IP - Udemy',
            'start' => '09/07/2023',
            'end' => '19/09/2023',
            'duration' => '101:46:00',
            'cert_duration' => '24:00:00',
        ],
    ];

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'planilha.estudos@studytrack.local'],
            [
                'name' => 'Usuario Planilha de Estudos',
                'password' => Hash::make('password'),
                'timezone' => 'America/Sao_Paulo',
                'locale' => 'pt_BR',
            ]
        );

        // Reimportacao limpa para manter resultado deterministico.
        $user->studySessions()->delete();

        /** @var array<string, Technology> $technologyBySubject */
        $technologyBySubject = [];

        Model::unguarded(function () use ($user, &$technologyBySubject) {
            foreach (self::SUBJECTS as $index => $subject) {
                $technology = Technology::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'slug' => Str::slug($subject),
                    ],
                    [
                        'name' => $subject,
                        'color' => $this->colorByIndex($index),
                        'description' => 'Importado de planilha de estudos',
                        'is_active' => true,
                    ]
                );

                $technologyBySubject[$subject] = $technology;
            }
        });

        foreach (self::MONTHLY_BREAKDOWN as $row) {
            $baseStart = CarbonImmutable::parse($row['date'], 'America/Sao_Paulo');
            $slot = 0;

            foreach ($row['hours'] as $subject => $duration) {
                $durationMin = $this->durationToMinutes($duration);
                if ($durationMin <= 0 || ! isset($technologyBySubject[$subject])) {
                    continue;
                }

                $startedAt = $baseStart->addMinutes($slot * 5);
                $endedAt = $startedAt->addMinutes($durationMin);

                StudySession::forceCreate([
                    'user_id' => $user->id,
                    'technology_id' => $technologyBySubject[$subject]->id,
                    'started_at' => $startedAt->toIso8601String(),
                    'ended_at' => $endedAt->toIso8601String(),
                    'notes' => sprintf('Importado da planilha mensal (%s): %s', $row['label'], $duration),
                    'mood' => null,
                    'focus_score' => null,
                ]);

                $slot++;
            }
        }

        $networkTech = Model::unguarded(function () use ($user) {
            return Technology::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'slug' => Str::slug('Cursos concluidos'),
                ],
                [
                    'name' => 'Cursos concluidos',
                    'color' => '#6B7280',
                    'description' => 'Cursos concluidos importados da planilha',
                    'is_active' => true,
                ]
            );
        });

        foreach (self::COMPLETED_COURSES as $course) {
            $startedAt = CarbonImmutable::createFromFormat(
                'd/m/Y H:i',
                $course['start'].' 08:00',
                'America/Sao_Paulo'
            );

            if (! $startedAt) {
                continue;
            }

            $durationMin = $this->durationToMinutes($course['duration']);
            if ($durationMin <= 0) {
                continue;
            }

            StudySession::forceCreate([
                'user_id' => $user->id,
                'technology_id' => $networkTech->id,
                'started_at' => $startedAt->toIso8601String(),
                'ended_at' => $startedAt->addMinutes($durationMin)->toIso8601String(),
                'notes' => sprintf(
                    '%s | termino: %s | duracao certificado: %s',
                    $course['name'],
                    $course['end'],
                    $course['cert_duration']
                ),
                'mood' => null,
                'focus_score' => null,
            ]);
        }
    }

    private function durationToMinutes(string $duration): int
    {
        $parts = array_map('trim', explode(':', $duration));
        if (count($parts) < 2 || count($parts) > 3) {
            return 0;
        }

        if (count($parts) === 2) {
            [$hours, $minutes] = $parts;

            return ((int) $hours * 60) + (int) $minutes;
        }

        [$hours, $minutes, $seconds] = $parts;
        $extraMinute = (int) $seconds >= 30 ? 1 : 0;

        return ((int) $hours * 60) + (int) $minutes + $extraMinute;
    }

    private function colorByIndex(int $index): string
    {
        $palette = [
            '#2563EB',
            '#059669',
            '#D97706',
            '#DC2626',
            '#7C3AED',
            '#0891B2',
            '#4F46E5',
            '#65A30D',
        ];

        return $palette[$index % count($palette)];
    }
}
