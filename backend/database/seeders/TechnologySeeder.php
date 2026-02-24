<?php

namespace Database\Seeders;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TechnologySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'dev@studytrack.local')->first();
        if (! $user) {
            return;
        }

        $technologies = [
            ['name' => 'JavaScript', 'color' => '#F7DF1E', 'description' => 'Linguagem de programação para web'],
            ['name' => 'TypeScript', 'color' => '#3178C6', 'description' => 'JavaScript tipado'],
            ['name' => 'Vue.js', 'color' => '#42B883', 'description' => 'Framework progressivo para interfaces'],
            ['name' => 'Laravel', 'color' => '#FF2D20', 'description' => 'Framework PHP para aplicações web'],
            ['name' => 'PostgreSQL', 'color' => '#4169E1', 'description' => 'Banco de dados relacional'],
            ['name' => 'Redis', 'color' => '#DC382D', 'description' => 'Cache e filas em memória'],
            ['name' => 'Docker', 'color' => '#2496ED', 'description' => 'Containerização de aplicações'],
        ];

        foreach ($technologies as $tech) {
            Technology::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'slug' => Str::slug($tech['name']),
                ],
                [
                    'name' => $tech['name'],
                    'color' => $tech['color'],
                    'description' => $tech['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}
