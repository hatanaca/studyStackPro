<?php

namespace Database\Seeders;

use App\Models\StudySubject;
use App\Models\StudySubTopic;
use App\Models\StudyTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudyContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBiologySubject();
        $this->seedSubTopicContent();
    }

    /**
     * Cria a disciplina de Biologia (faltava) com um tópico demonstrativo
     * de Biologia Celular e um sub-tópico com diagrama interativo.
     */
    private function seedBiologySubject(): void
    {
        $subject = StudySubject::updateOrCreate(
            ['slug' => 'biologia'],
            [
                'name' => 'Biologia',
                'icon' => 'pi-leaf',
                'color' => '#22C55E',
                'sort_order' => 7,
            ]
        );

        $topic = StudyTopic::updateOrCreate(
            ['slug' => 'biologia-celular'],
            [
                'subject_id' => $subject->id,
                'name' => 'Biologia Celular',
                'difficulty' => 'fundamental',
                'sort_order' => 1,
            ]
        );

        StudySubTopic::updateOrCreate(
            ['slug' => 'estrutura-celula'],
            [
                'topic_id' => $topic->id,
                'name' => 'Estrutura da Célula',
                'sort_order' => 1,
                'description' => 'Explore as organelas de uma célula animal e suas funções.',
                'content' => [
                    'blocks' => [
                        [
                            'type' => 'heading',
                            'level' => 2,
                            'text' => 'A célula: unidade fundamental da vida',
                        ],
                        [
                            'type' => 'paragraph',
                            'text' => 'Toda forma de vida é composta por células. A célula animal é eucarionte: possui núcleo delimitado por membrana e organelas especializadas que compartilham funções vitais como produção de energia, síntese de proteínas e digestão intracelular.',
                        ],
                        [
                            'type' => 'callout',
                            'variant' => 'info',
                            'title' => 'Organela-chave',
                            'text' => 'A mitocôndria é conhecida como a "usina de energia" da célula, pois realiza a respiração celular aeróbica, produzindo ATP.',
                        ],
                        [
                            'type' => 'heading',
                            'level' => 3,
                            'text' => 'Principais organelas',
                        ],
                        [
                            'type' => 'list',
                            'style' => 'bullet',
                            'items' => [
                                'Núcleo: armazena o DNA e controla as atividades celulares.',
                                'Mitocôndria: respiração celular e produção de ATP.',
                                'Ribossomos: síntese de proteínas.',
                                'Retículo endoplasmático: síntese de lipídios (liso) e proteínas (rugoso).',
                                'Complexo de Golgi: empacotamento e secreção de substâncias.',
                                'Lisossomos: digestão intracelular.',
                            ],
                        ],
                        [
                            'type' => 'heading',
                            'level' => 3,
                            'text' => 'Membrana plasmática',
                        ],
                        [
                            'type' => 'paragraph',
                            'text' => 'A membrana plasmática é formada por uma bicamada de fosfolipídios com proteínas inseridas (modelo do mosaico fluido). Ela controla o que entra e sai da célula, garantindo a homeostase.',
                        ],
                    ],
                ],
                'faqs' => [
                    [
                        'question' => 'Qual a diferença entre célula animal e vegetal?',
                        'answer' => 'A célula vegetal possui parede celular de celulose, vacúolo de suco celular de grande volume e plastos (como os cloroplastos). A célula animal não possui parede celular e apresenta centríolos e lisossomos em maior quantidade.',
                    ],
                    [
                        'question' => 'Por que a mitocôndria é importante?',
                        'answer' => 'A mitocôndria realiza a respiração celular aeróbica, processo que converte glicose e oxigênio em ATP — a principal moeda energética das células.',
                    ],
                    [
                        'question' => 'O que é o modelo do mosaico fluido?',
                        'answer' => 'É o modelo que descreve a membrana plasmática como uma bicamada de fosfolipídios fluida, com proteínas e outras moléculas inseridas, que se movem lateralmente.',
                    ],
                ],
                'learning_objectives' => [
                    'Identificar as principais organelas e suas funções',
                    'Comparar células animais e vegetais',
                    'Explicar o modelo do mosaico fluido da membrana',
                ],
                'simulation_config' => [
                    'type' => 'biology_svg',
                    'hotspots' => [
                        [
                            'id' => 'membrana',
                            'x' => 240,
                            'y' => 190,
                            'label' => 'Membrana Plasmática',
                            'description' => 'Bicamada de fosfolipídios que controla a entrada e saída de substâncias.',
                        ],
                        [
                            'id' => 'nucleo',
                            'x' => 145,
                            'y' => 130,
                            'label' => 'Núcleo',
                            'description' => 'Armazena o DNA e controla as atividades celulares.',
                        ],
                        [
                            'id' => 'mitocondria',
                            'x' => 315,
                            'y' => 120,
                            'label' => 'Mitocôndria',
                            'description' => 'Respiração celular e produção de ATP.',
                        ],
                        [
                            'id' => 'golgi',
                            'x' => 320,
                            'y' => 245,
                            'label' => 'Complexo de Golgi',
                            'description' => 'Empacotamento e secreção de proteínas e lipídios.',
                        ],
                        [
                            'id' => 'lisossomo',
                            'x' => 175,
                            'y' => 255,
                            'label' => 'Lisossomo',
                            'description' => 'Digestão intracelular de partículas e organelas velhas.',
                        ],
                        [
                            'id' => 'ribossomos',
                            'x' => 215,
                            'y' => 75,
                            'label' => 'Ribossomos',
                            'description' => 'Responsáveis pela síntese de proteínas.',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Para cada sub-tópico sem conteúdo, gera conteúdo genérico (visão geral +
     * objetivos + FAQ) e uma configuração de simulação conforme o tópico pai.
     */
    private function seedSubTopicContent(): void
    {
        StudySubTopic::with('topic')->chunkById(100, function ($subTopics) {
            foreach ($subTopics as $subTopic) {
                if ($subTopic->content !== null) {
                    continue;
                }

                $simulation = $this->simulationForTopic($subTopic->topic?->slug);

                $subTopic->update([
                    'description' => "Estude {$subTopic->name} de forma interativa.",
                    'content' => [
                        'blocks' => [
                            [
                                'type' => 'heading',
                                'level' => 2,
                                'text' => $subTopic->name,
                            ],
                            [
                                'type' => 'paragraph',
                                'text' => "Este tópico aborda {$subTopic->name}. Explore a animação interativa acima para visualizar o comportamento do conteúdo, depois leia a explicação e pratique com o gerador de exercícios.",
                            ],
                            [
                                'type' => 'callout',
                                'variant' => 'tip',
                                'title' => 'Como estudar',
                                'text' => 'Use a exploração interativa para testar hipóteses: altere os parâmetros e observe o resultado antes de ler a explicação.',
                            ],
                            [
                                'type' => 'heading',
                                'level' => 3,
                                'text' => 'Objetivos de aprendizado',
                            ],
                            [
                                'type' => 'list',
                                'style' => 'checklist',
                                'items' => $this->objectivesFor($subTopic->name),
                            ],
                            [
                                'type' => 'heading',
                                'level' => 3,
                                'text' => 'Resumo',
                            ],
                            [
                                'type' => 'paragraph',
                                'text' => 'Depois de revisar o conteúdo e responder às perguntas frequentes, siga para a seção "Pratique" para gerar exercícios personalizados e verificar seu domínio do assunto.',
                            ],
                        ],
                    ],
                    'faqs' => [
                        [
                            'question' => "O que é {$subTopic->name}?",
                            'answer' => "É um dos conceitos fundamentais do programa de estudos. Entender seus princípios é essencial para resolver problemas mais avançados no ITA.",
                        ],
                        [
                            'question' => 'Como devo estudar este tópico?',
                            'answer' => 'Primeiro explore a simulação interativa variando os parâmetros. Depois leia a explicação completa e, por fim, pratique com as questões geradas até atingir domínio.',
                        ],
                    ],
                    'learning_objectives' => $this->objectivesFor($subTopic->name),
                    'simulation_config' => $simulation,
                ]);
            }
        });
    }

    /** Define a simulação conforme o tópico pai do sub-tópico. */
    private function simulationForTopic(?string $topicSlug): ?array
    {
        return match ($topicSlug) {
            'funcao-afim-quadratica' => [
                'type' => 'function_plot',
                'functions' => ['a*x^2 + b*x + c'],
                'xDomain' => [-10, 10],
                'yDomain' => [-10, 10],
                'sliders' => [
                    ['name' => 'a', 'min' => -5, 'max' => 5, 'default' => 1, 'step' => 0.1, 'label' => 'Coeficiente a'],
                    ['name' => 'b', 'min' => -10, 'max' => 10, 'default' => 0, 'step' => 0.5, 'label' => 'Coeficiente b'],
                    ['name' => 'c', 'min' => -10, 'max' => 10, 'default' => 0, 'step' => 0.5, 'label' => 'Coeficiente c'],
                ],
            ],
            'funcoes-exponenciais-logaritmicas' => [
                'type' => 'function_plot',
                'functions' => ['a^x', 'log(x, a)'],
                'xDomain' => [-2, 6],
                'yDomain' => [-6, 10],
                'sliders' => [
                    ['name' => 'a', 'min' => 0.1, 'max' => 5, 'default' => 2, 'step' => 0.1, 'label' => 'Base a'],
                ],
            ],
            'funcoes-polinomiais-equacoes' => [
                'type' => 'function_plot',
                'functions' => ['x^3 + a*x^2 + b*x + c'],
                'xDomain' => [-5, 5],
                'yDomain' => [-10, 10],
                'sliders' => [
                    ['name' => 'a', 'min' => -5, 'max' => 5, 'default' => 0, 'step' => 0.5, 'label' => 'Coeficiente a'],
                    ['name' => 'b', 'min' => -5, 'max' => 5, 'default' => -3, 'step' => 0.5, 'label' => 'Coeficiente b'],
                    ['name' => 'c', 'min' => -5, 'max' => 5, 'default' => 1, 'step' => 0.5, 'label' => 'Coeficiente c'],
                ],
            ],
            'cinematica-escalar-vetorial' => [
                'type' => 'physics_sim',
                'simulation' => 'projectile',
                'initialVelocity' => 20,
                'gravity' => 9.8,
                'angle' => 45,
                'sliders' => [
                    ['name' => 'initialVelocity', 'min' => 5, 'max' => 60, 'default' => 20, 'step' => 1, 'label' => 'Velocidade inicial (m/s)'],
                    ['name' => 'angle', 'min' => 5, 'max' => 85, 'default' => 45, 'step' => 1, 'label' => 'Ângulo (°)'],
                    ['name' => 'gravity', 'min' => 1, 'max' => 25, 'default' => 9.8, 'step' => 0.1, 'label' => 'Gravidade (m/s²)'],
                ],
            ],
            'oscilacoes-mhs-ondas' => [
                'type' => 'physics_sim',
                'simulation' => 'pendulum',
                'length' => 1,
                'gravity' => 9.8,
                'amplitude' => 30,
                'sliders' => [
                    ['name' => 'length', 'min' => 0.5, 'max' => 3, 'default' => 1, 'step' => 0.1, 'label' => 'Comprimento (m)'],
                    ['name' => 'amplitude', 'min' => 5, 'max' => 60, 'default' => 30, 'step' => 1, 'label' => 'Amplitude (°)'],
                    ['name' => 'gravity', 'min' => 1, 'max' => 25, 'default' => 9.8, 'step' => 0.1, 'label' => 'Gravidade (m/s²)'],
                ],
            ],
            'geometria-plana-fundamentos' => [
                'type' => 'geometry',
                'shape' => 'triangle',
                'interactive' => true,
                'measurements' => ['angles', 'sides'],
            ],
            'trigonometria-triangulo-retangulo' => [
                'type' => 'function_plot',
                'functions' => ['sin(x)', 'cos(x)', 'tan(x)'],
                'xDomain' => [-M_PI, M_PI],
                'yDomain' => [-3, 3],
                'sliders' => [],
            ],
            default => null,
        };
    }

    /** Objetivos genéricos baseados no nome do sub-tópico. */
    private function objectivesFor(string $name): array
    {
        return [
            "Compreender os fundamentos de {$name}",
            "Aplicar os conceitos de {$name} em problemas do ITA",
            "Identificar erros comuns e armadilhas em {$name}",
            'Resolver questões temporizadas sobre o assunto',
        ];
    }
}
