<?php

namespace Database\Seeders;

use App\Models\StudySubject;
use Illuminate\Database\Seeder;

class ItaStudySubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Matemática', 'slug' => 'matematica', 'icon' => 'pi-calculator', 'color' => '#3B82F6', 'sort_order' => 1],
            ['name' => 'Física', 'slug' => 'fisica', 'icon' => 'pi-bolt', 'color' => '#10B981', 'sort_order' => 2],
            ['name' => 'Química', 'slug' => 'quimica', 'icon' => 'pi-flask', 'color' => '#F59E0B', 'sort_order' => 3],
            ['name' => 'Português', 'slug' => 'portugues', 'icon' => 'pi-book', 'color' => '#8B5CF6', 'sort_order' => 4],
            ['name' => 'Inglês', 'slug' => 'ingles', 'icon' => 'pi-globe', 'color' => '#EC4899', 'sort_order' => 5],
            ['name' => 'Habilidades', 'slug' => 'habilidades', 'icon' => 'pi-star', 'color' => '#F97316', 'sort_order' => 6],
        ];

        foreach ($subjects as $subject) {
            StudySubject::updateOrCreate(
                ['slug' => $subject['slug']],
                $subject
            );
        }
    }
}
