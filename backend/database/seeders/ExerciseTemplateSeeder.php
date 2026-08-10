<?php

namespace Database\Seeders;

use App\Models\ExerciseTemplate;
use Illuminate\Database\Seeder;

/**
 * Templates globais de exercícios de matemática (user_id null).
 * Servem como ponto de partida antes de o usuário criar os próprios.
 */
class ExerciseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'title' => 'Soma básica',
                'kind' => 'numeric',
                'prompt' => 'Quanto é {{a}} + {{b}}?',
                'parameters_spec' => [
                    'a' => ['type' => 'int', 'min' => 1, 'max' => 99],
                    'b' => ['type' => 'int', 'min' => 1, 'max' => 99],
                ],
                'answer_expression' => '{{a}} + {{b}}',
                'solution_latex' => '{{a}} + {{b}} = {{a}} + {{b}}',
                'difficulty' => 1,
            ],
            [
                'title' => 'Equação do 1º grau',
                'kind' => 'numeric',
                'prompt' => 'Resolva a equação: {{a}}x + {{b}} = 0',
                'parameters_spec' => [
                    'a' => ['type' => 'int', 'min' => 1, 'max' => 9],
                    // b positivo evita exibição "6x + -5 = 0" no prompt preenchido
                    'b' => ['type' => 'int', 'min' => 1, 'max' => 9],
                ],
                'answer_expression' => '-{{b}}/{{a}}',
                'solution_latex' => 'x = -\dfrac{{{b}}}{{{a}}}',
                'difficulty' => 2,
            ],
            [
                'title' => 'Produtos notáveis',
                'kind' => 'symbolic',
                'prompt' => 'Expanda o produto notável: $(x + {{a}})(x - {{a}})$',
                'parameters_spec' => [
                    'a' => ['type' => 'int', 'min' => 2, 'max' => 9],
                ],
                'answer_expression' => 'x^2 - {{a}}^2',
                'variables' => ['x'],
                'solution_latex' => 'x^2 - {{a}}^2',
                'difficulty' => 3,
            ],
        ];

        foreach ($templates as $template) {
            ExerciseTemplate::query()->updateOrCreate(
                ['title' => $template['title'], 'user_id' => null],
                $template,
            );
        }
    }
}
