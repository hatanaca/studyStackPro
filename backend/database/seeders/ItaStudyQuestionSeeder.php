<?php

namespace Database\Seeders;

use App\Models\StudyQuestion;
use App\Models\StudySubTopic;
use Illuminate\Database\Seeder;

class ItaStudyQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // MATEMÁTICA - Conjuntos numéricos
            'Naturais, inteiros, racionais, irracionais e reais' => [
                [
                    'kind' => 'multiple_choice',
                    'prompt' => 'Qual dos números abaixo é irracional?',
                    'parameters_spec' => [],
                    'answer_expression' => 'sqrt(2)',
                    'answer_type' => 'choice',
                    'choices_spec' => ['√2', '1/3', '0.5', '-7', '0'],
                    'solution_latex' => '\\sqrt{2} \\approx 1,414... \\text{ é irrational}',
                    'explanation' => 'Um número irracional não pode ser expresso como razão entre dois inteiros. √2 é o exemplo clássico.',
                    'difficulty' => 1,
                ],
                [
                    'kind' => 'numeric',
                    'prompt' => 'Qual é o resultado de {{a}} + {{b}} quando a = {{a}} e b = {{b}}?',
                    'parameters_spec' => ['a' => ['type' => 'int', 'min' => -10, 'max' => 10], 'b' => ['type' => 'int', 'min' => -10, 'max' => 10]],
                    'answer_expression' => 'a + b',
                    'answer_type' => 'numeric',
                    'solution_latex' => '{{a}} + {{b}} = {{answer}}',
                    'explanation' => 'Soma de dois números inteiros.',
                    'difficulty' => 1,
                ],
            ],
            'Representação na reta real' => [
                [
                    'kind' => 'multiple_choice',
                    'prompt' => 'Em que parte da reta real está localizado o número -3?',
                    'parameters_spec' => [],
                    'answer_expression' => 'left',
                    'answer_type' => 'choice',
                    'choices_spec' => ['À esquerda do zero', 'À direita do zero', 'No zero', 'Acima do zero', 'Não está na reta'],
                    'explanation' => 'Números negativos ficam à esquerda do zero na reta real.',
                    'difficulty' => 1,
                ],
            ],
            // MATEMÁTICA - Potenciação
            'Propriedades de potências (expoente inteiro, racional, real)' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Calcule: {{a}}^{{b}}',
                    'parameters_spec' => ['a' => ['type' => 'int', 'min' => 2, 'max' => 10], 'b' => ['type' => 'int', 'min' => 2, 'max' => 5]],
                    'answer_expression' => 'a**b',
                    'answer_type' => 'numeric',
                    'solution_latex' => '{{a}}^{{b}} = {{answer}}',
                    'explanation' => 'Potenciação: multiplicar a base por ela mesma exponente vezes.',
                    'difficulty' => 1,
                ],
            ],
            // MATEMÁTICA - Equações
            'Resolução e interpretação gráfica' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Resolva: {{a}}x + {{b}} = 0. Qual é o valor de x?',
                    'parameters_spec' => [
                        'a' => ['type' => 'int', 'min' => 1, 'max' => 10],
                        'b' => ['type' => 'int', 'min' => -20, 'max' => 20],
                    ],
                    'answer_expression' => '-b/a',
                    'answer_type' => 'numeric',
                    'solution_latex' => '{{a}}x + {{b}} = 0 \\Rightarrow x = \\frac{-{{b}}}{{{a}}} = {{answer}}',
                    'explanation' => 'Para equação do 1º grau ax + b = 0, a solução é x = -b/a.',
                    'difficulty' => 1,
                ],
            ],
            'Discriminante, soma e produto das raízes' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Para a equação x² + {{b}}x + {{c}} = 0, qual é o discriminante (Δ)?',
                    'parameters_spec' => [
                        'b' => ['type' => 'int', 'min' => -10, 'max' => 10],
                        'c' => ['type' => 'int', 'min' => -10, 'max' => 10],
                    ],
                    'answer_expression' => 'b**2 - 4*c',
                    'answer_type' => 'numeric',
                    'solution_latex' => '\\Delta = b^2 - 4ac = ({{b}})^2 - 4(1)({{c}}) = {{answer}}',
                    'explanation' => 'O discriminante Δ = b² - 4ac determina a natureza das raízes.',
                    'difficulty' => 2,
                ],
            ],
            // MATEMÁTICA - Função quadrática
            'Forma canônica da quadrática, vértice e eixo de simetria' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Qual é a coordenada x do vértice da parábola y = x² + {{b}}x + {{c}}?',
                    'parameters_spec' => [
                        'b' => ['type' => 'int', 'min' => -10, 'max' => 10],
                        'c' => ['type' => 'int', 'min' => -10, 'max' => 10],
                    ],
                    'answer_expression' => '-b/2',
                    'answer_type' => 'numeric',
                    'solution_latex' => 'x_v = \\frac{-b}{2a} = \\frac{-({{b}})}{2(1)} = {{answer}}',
                    'explanation' => 'O vértice de y = ax² + bx + c tem coordenada x = -b/(2a).',
                    'difficulty' => 2,
                ],
            ],
            // MATEMÁTICA - Trigonometria
            'Seno, cosseno e tangente' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Em um triângulo retângulo, o cateto oposto ao ângulo {{angulo}}° mede {{op}} e a hipotenusa mede {{hip}}. Qual é o seno desse ângulo? (Arredonde para 2 casas decimais)',
                    'parameters_spec' => [
                        'angulo' => ['type' => 'choice', 'values' => [30, 45, 60]],
                        'op' => ['type' => 'float', 'min' => 1, 'max' => 10, 'step' => 0.5],
                        'hip' => ['type' => 'float', 'min' => 5, 'max' => 20, 'step' => 0.5],
                    ],
                    'answer_expression' => 'round(op/hip, 2)',
                    'answer_type' => 'numeric',
                    'solution_latex' => '\\sin({{angulo}}°) = \\frac{\\text{cateto oposto}}{\\text{hipotenusa}} = \\frac{{{op}}}{{{hip}}} = {{answer}}',
                    'explanation' => 'Seno = cateto oposto / hipotenusa.',
                    'difficulty' => 2,
                    'has_graph' => true,
                    'visual_type' => 'geometric',
                ],
            ],
            // FÍSICA - Cinemática
            'MU e MRUV: equações, gráficos e interpretação' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Um corpo em MRUV parte do repouso e tem aceleração constante de {{a}} m/s². Qual é a velocidade após {{t}} segundos?',
                    'parameters_spec' => [
                        'a' => ['type' => 'float', 'min' => 1, 'max' => 20, 'step' => 0.5],
                        't' => ['type' => 'float', 'min' => 1, 'max' => 10, 'step' => 0.5],
                    ],
                    'answer_expression' => 'a*t',
                    'answer_type' => 'numeric',
                    'solution_latex' => 'v = v_0 + at = 0 + ({{a}})({{t}}) = {{answer}} m/s',
                    'explanation' => 'Em MRUV com v₀ = 0: v = at.',
                    'difficulty' => 2,
                    'has_graph' => true,
                    'visual_type' => 'function_plot',
                ],
                [
                    'kind' => 'numeric',
                    'prompt' => 'Um corpo em MRUV parte do repouso e percorre {{d}} m em {{t}} s. Qual é a aceleração?',
                    'parameters_spec' => [
                        'd' => ['type' => 'float', 'min' => 5, 'max' => 50, 'step' => 0.5],
                        't' => ['type' => 'float', 'min' => 1, 'max' => 10, 'step' => 0.5],
                    ],
                    'answer_expression' => '2*d/t**2',
                    'answer_type' => 'numeric',
                    'solution_latex' => 'd = \\frac{1}{2}at^2 \\Rightarrow a = \\frac{2d}{t^2} = \\frac{2 \\cdot {{d}}}{{{t}}^2} = {{answer}} m/s²',
                    'explanation' => 'Usando a equação de MRUV com v₀ = 0: d = ½at².',
                    'difficulty' => 2,
                ],
            ],
            // FÍSICA - Dinâmica
            '1ª, 2ª e 3ª leis e aplicações' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Um corpo de massa {{m}} kg é submetido a uma força resultante de {{F}} N. Qual é a aceleração?',
                    'parameters_spec' => [
                        'm' => ['type' => 'float', 'min' => 1, 'max' => 50, 'step' => 0.5],
                        'F' => ['type' => 'float', 'min' => 1, 'max' => 100, 'step' => 0.5],
                    ],
                    'answer_expression' => 'F/m',
                    'answer_type' => 'numeric',
                    'solution_latex' => 'F = ma \\Rightarrow a = \\frac{F}{m} = \\frac{{{F}}}{{{m}}} = {{answer}} m/s²',
                    'explanation' => 'Segunda lei de Newton: F = ma.',
                    'difficulty' => 2,
                ],
            ],
            // FÍSICA - Trabalho e energia
            'Teorema trabalho-energia cinética' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Uma força de {{F}} N atua sobre um corpo de massa {{m}} kg que percorre {{d}} m. O corpo parte do repouso. Qual é a velocidade final? (considere g = 10 m/s²)',
                    'parameters_spec' => [
                        'F' => ['type' => 'float', 'min' => 5, 'max' => 50, 'step' => 0.5],
                        'm' => ['type' => 'float', 'min' => 1, 'max' => 20, 'step' => 0.5],
                        'd' => ['type' => 'float', 'min' => 1, 'max' => 20, 'step' => 0.5],
                    ],
                    'answer_expression' => 'sqrt(2*F*d/m)',
                    'answer_type' => 'numeric',
                    'solution_latex' => 'W = \\Delta E_c \\Rightarrow F \\cdot d = \\frac{1}{2}mv^2 \\Rightarrow v = \\sqrt{\\frac{2Fd}{m}} = {{answer}} m/s',
                    'explanation' => 'Trabalho-energia: W = ΔEc.',
                    'difficulty' => 3,
                ],
            ],
            // QUÍMICA - Estequiometria
            'Balanceamento por oxirredução' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Quantos mols de {{produto}} são produzidos pela reação completa de {{mols}} mols de {{reagente}}? (coeficiente do reagente: {{coef_r}}, coeficiente do produto: {{coef_p}})',
                    'parameters_spec' => [
                        'produto' => ['type' => 'choice', 'values' => ['H2O', 'CO2', 'NaCl']],
                        'reagente' => ['type' => 'choice', 'values' => ['H2', 'C', 'Na']],
                        'mols' => ['type' => 'float', 'min' => 0.5, 'max' => 10, 'step' => 0.5],
                        'coef_r' => ['type' => 'int', 'min' => 1, 'max' => 4],
                        'coef_p' => ['type' => 'int', 'min' => 1, 'max' => 4],
                    ],
                    'answer_expression' => 'mols * coef_p / coef_r',
                    'answer_type' => 'numeric',
                    'solution_latex' => 'n_{produto} = n_{reagente} \\times \\frac{coef_{produto}}{coef_{reagente}} = {{mols}} \\times \\frac{{{coef_p}}}{{{coef_r}}} = {{answer}} mol',
                    'explanation' => 'Proporção estequiométrica: mols produto = mols reagente × (coef produto / coef reagente).',
                    'difficulty' => 2,
                ],
            ],
            // QUÍMICA - Soluções
            'Concentrações: mol/L, g/L, % massa, fração molar, ppm' => [
                [
                    'kind' => 'numeric',
                    'prompt' => 'Qual é a concentração molar de uma solução preparada dissolvendo {{massa}} g de {{substancia}} (MM = {{MM}} g/mol) em {{volume}} mL de solução?',
                    'parameters_spec' => [
                        'massa' => ['type' => 'float', 'min' => 1, 'max' => 100, 'step' => 0.5],
                        'substancia' => ['type' => 'choice', 'values' => ['NaCl', 'CuSO4', 'NaOH']],
                        'MM' => ['type' => 'float', 'min' => 10, 'max' => 200, 'step' => 0.1],
                        'volume' => ['type' => 'float', 'min' => 100, 'max' => 1000, 'step' => 50],
                    ],
                    'answer_expression' => 'round(massa / MM / (volume / 1000), 4)',
                    'answer_type' => 'numeric',
                    'solution_latex' => 'C = \\frac{n}{V} = \\frac{\\frac{massa}{MM}}{V} = \\frac{\\frac{{{massa}}}{{{MM}}}{{{volume}/1000}} = {{answer}} mol/L',
                    'explanation' => 'Concentração molar: C = n/V = (massa/MM) / V(L).',
                    'difficulty' => 2,
                ],
            ],
            // PORTUGUÊS - Ortografia
            'Regras do Acordo Ortográfico vigente' => [
                [
                    'kind' => 'multiple_choice',
                    'prompt' => 'Qual é a forma correta?',
                    'parameters_spec' => [],
                    'answer_expression' => 'b',
                    'answer_type' => 'choice',
                    'choices_spec' => ['Excessão', 'Exceção', 'Exceçao', 'Exceçõo', 'Excessõe'],
                    'explanation' => 'Exceção é a forma correta segundo o Acordo Ortográfico.',
                    'difficulty' => 1,
                ],
            ],
            // INGLÊS - Gramática fundamental
            'Simple present, present continuous, simple past, past continuous' => [
                [
                    'kind' => 'multiple_choice',
                    'prompt' => 'Choose the correct form: She ___ to school every day.',
                    'parameters_spec' => [],
                    'answer_expression' => 'goes',
                    'answer_type' => 'choice',
                    'choices_spec' => ['go', 'goes', 'is going', 'went', 'going'],
                    'explanation' => 'Simple present with third person singular: goes.',
                    'difficulty' => 1,
                ],
            ],
        ];

        foreach ($questions as $subTopicName => $questionList) {
            $subTopic = StudySubTopic::where('name', $subTopicName)->first();
            if (!$subTopic) {
                continue;
            }

            foreach ($questionList as $questionData) {
                StudyQuestion::create(array_merge($questionData, [
                    'sub_topic_id' => $subTopic->id,
                ]));
            }
        }
    }
}
