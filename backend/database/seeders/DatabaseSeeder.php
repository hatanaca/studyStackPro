<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Ponto único de entrada para popular o banco.
 *
 * Ordem: usuário dev → tecnologias do dev → sessões de demo (6 meses) → opcional usuário planilha.
 * Demo: dev@studytrack.local / password (techs + sessões de exemplo).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TechnologySeeder::class,
            DemoDataSeeder::class,
            StudySpreadsheetUserSeeder::class,
        ]);
    }
}
