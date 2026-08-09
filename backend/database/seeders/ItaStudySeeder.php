<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ItaStudySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ItaStudySubjectSeeder::class,
            ItaStudyTopicSeeder::class,
            ItaStudySubTopicSeeder::class,
            ItaStudyQuestionSeeder::class,
        ]);
    }
}
