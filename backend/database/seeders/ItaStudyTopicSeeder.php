<?php

namespace Database\Seeders;

use App\Models\StudySubject;
use App\Models\StudyTopic;
use Illuminate\Database\Seeder;

class ItaStudyTopicSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = StudySubject::all()->keyBy('slug');

        $topics = [
            // MATEMÁTICA
            'matematica' => [
                ['name' => 'Conjuntos numéricos e intervalos', 'slug' => 'conjuntos-numericos', 'difficulty' => 'fundamental', 'sort_order' => 1],
                ['name' => 'Potenciação e radiciação', 'slug' => 'potenciacao-radiciacao', 'difficulty' => 'fundamental', 'sort_order' => 2],
                ['name' => 'Produtos notáveis e fatoração', 'slug' => 'produtos-notaveis-fatoracao', 'difficulty' => 'fundamental', 'sort_order' => 3],
                ['name' => 'Equações e inequações do 1º e 2º graus', 'slug' => 'equacoes-inequacoes-1-2-graus', 'difficulty' => 'fundamental', 'sort_order' => 4],
                ['name' => 'Função afim e função quadrática', 'slug' => 'funcao-afim-quadratica', 'difficulty' => 'fundamental', 'sort_order' => 5],
                ['name' => 'Razão, proporção, regra de três e porcentagem', 'slug' => 'razao-proporcao-regra3-porcentagem', 'difficulty' => 'fundamental', 'sort_order' => 6],
                ['name' => 'Geometria plana — fundamentos', 'slug' => 'geometria-plana-fundamentos', 'difficulty' => 'fundamental', 'sort_order' => 7],
                ['name' => 'Trigonometria no triângulo retângulo', 'slug' => 'trigonometria-triangulo-retangulo', 'difficulty' => 'fundamental', 'sort_order' => 8],
                ['name' => 'Lógica matemática e teoria dos conjuntos', 'slug' => 'logica-matematica-teoria-conjuntos', 'difficulty' => 'fundamental', 'sort_order' => 9],
                ['name' => 'Funções reais: propriedades e gráficos', 'slug' => 'funcoes-reais-propriedades-graficos', 'difficulty' => 'intermediário', 'sort_order' => 10],
                ['name' => 'Funções polinomiais e equações', 'slug' => 'funcoes-polinomiais-equacoes', 'difficulty' => 'intermediário', 'sort_order' => 11],
                ['name' => 'Funções exponenciais e logarítmicas', 'slug' => 'funcoes-exponenciais-logaritmicas', 'difficulty' => 'intermediário', 'sort_order' => 12],
                ['name' => 'Trigonometria completa', 'slug' => 'trigonometria-completa', 'difficulty' => 'intermediário', 'sort_order' => 13],
                ['name' => 'Números complexos', 'slug' => 'numeros-complexos', 'difficulty' => 'intermediário', 'sort_order' => 14],
                ['name' => 'Geometria plana analítica', 'slug' => 'geometria-plana-analitica', 'difficulty' => 'intermediário', 'sort_order' => 15],
                ['name' => 'Vetores no plano e no espaço', 'slug' => 'vetores-plano-espaco', 'difficulty' => 'intermediário', 'sort_order' => 16],
                ['name' => 'Geometria espacial', 'slug' => 'geometria-espacial', 'difficulty' => 'intermediário', 'sort_order' => 17],
                ['name' => 'Matrizes, determinantes e sistemas lineares', 'slug' => 'matrizes-determinantes-sistemas', 'difficulty' => 'intermediário', 'sort_order' => 18],
                ['name' => 'Sequências, progressões e recursão', 'slug' => 'sequencias-progressoes-recursao', 'difficulty' => 'intermediário', 'sort_order' => 19],
                ['name' => 'Análise combinatória e probabilidade', 'slug' => 'analise-combinatoria-probabilidade', 'difficulty' => 'intermediário', 'sort_order' => 20],
                ['name' => 'Teoria dos números', 'slug' => 'teoria-dos-numeros', 'difficulty' => 'avançado', 'sort_order' => 21],
                ['name' => 'Indução matemática e raciocínio dedutivo', 'slug' => 'inducao-matematica-raciocinio', 'difficulty' => 'avançado', 'sort_order' => 22],
                ['name' => 'Funções implícitas e paramétricas', 'slug' => 'funcoes-implicitas-parametricas', 'difficulty' => 'avançado', 'sort_order' => 23],
                ['name' => 'Geometria plana sintética avançada', 'slug' => 'geometria-plana-sintetica-avancada', 'difficulty' => 'avançado', 'sort_order' => 24],
                ['name' => 'Desigualdades clássicas', 'slug' => 'desigualdades-classicas', 'difficulty' => 'eliminatório', 'sort_order' => 25],
                ['name' => 'Cálculo diferencial e integral', 'slug' => 'calculo-diferencial-integral', 'difficulty' => 'eliminatório', 'sort_order' => 26],
            ],
            // FÍSICA
            'fisica' => [
                ['name' => 'Grandezas físicas e Sistema Internacional (SI)', 'slug' => 'grandezas-fisicas-si', 'difficulty' => 'fundamental', 'sort_order' => 1],
                ['name' => 'Notação científica e algarismos significativos', 'slug' => 'notacao-cientifica-algarismos', 'difficulty' => 'fundamental', 'sort_order' => 2],
                ['name' => 'Análise dimensional', 'slug' => 'analise-dimensional', 'difficulty' => 'fundamental', 'sort_order' => 3],
                ['name' => 'Vetores na Física (uso operacional)', 'slug' => 'vetores-na-fisica', 'difficulty' => 'fundamental', 'sort_order' => 4],
                ['name' => 'Hidrostática — conceitos iniciais', 'slug' => 'hidrostatica-conceitos-iniciais', 'difficulty' => 'fundamental', 'sort_order' => 5],
                ['name' => 'Fundamentos de óptica geométrica', 'slug' => 'fundamentos-optica-geometrica', 'difficulty' => 'fundamental', 'sort_order' => 6],
                ['name' => 'Cinemática escalar e vetorial', 'slug' => 'cinematica-escalar-vetorial', 'difficulty' => 'intermediário', 'sort_order' => 7],
                ['name' => 'Dinâmica: leis de Newton', 'slug' => 'dinamica-leis-newton', 'difficulty' => 'intermediário', 'sort_order' => 8],
                ['name' => 'Trabalho, energia e potência', 'slug' => 'trabalho-energia-potencia', 'difficulty' => 'intermediário', 'sort_order' => 9],
                ['name' => 'Impulso, momento e colisões', 'slug' => 'impulso-momento-colisoes', 'difficulty' => 'intermediário', 'sort_order' => 10],
                ['name' => 'Oscilações: MHS e ondas mecânicas', 'slug' => 'oscilacoes-mhs-ondas', 'difficulty' => 'intermediário', 'sort_order' => 11],
                ['name' => 'Termodinâmica completa', 'slug' => 'termodinamica-completa', 'difficulty' => 'intermediário', 'sort_order' => 12],
                ['name' => 'Eletrostática', 'slug' => 'eletrostatica', 'difficulty' => 'intermediário', 'sort_order' => 13],
                ['name' => 'Eletrodinâmica e circuitos', 'slug' => 'eletrodinamica-circuitos', 'difficulty' => 'intermediário', 'sort_order' => 14],
                ['name' => 'Dinâmica de rotação', 'slug' => 'dinamica-de-rotacao', 'difficulty' => 'avançado', 'sort_order' => 15],
                ['name' => 'Gravitação universal', 'slug' => 'gravitacao-universal', 'difficulty' => 'avançado', 'sort_order' => 16],
                ['name' => 'Hidrostática e hidrodinâmica', 'slug' => 'hidrostatica-hidrodinamica', 'difficulty' => 'avançado', 'sort_order' => 17],
                ['name' => 'Eletromagnetismo', 'slug' => 'eletromagnetismo', 'difficulty' => 'avançado', 'sort_order' => 18],
                ['name' => 'Óptica geométrica e física', 'slug' => 'optica-geometrica-fisica', 'difficulty' => 'avançado', 'sort_order' => 19],
                ['name' => 'Física moderna e relatividade especial', 'slug' => 'fisica-moderna-relatividade', 'difficulty' => 'eliminatório', 'sort_order' => 20],
            ],
            // QUÍMICA
            'quimica' => [
                ['name' => 'Matéria e suas transformações', 'slug' => 'materia-transformacoes', 'difficulty' => 'fundamental', 'sort_order' => 1],
                ['name' => 'Separação de misturas', 'slug' => 'separacao-misturas', 'difficulty' => 'fundamental', 'sort_order' => 2],
                ['name' => 'Leis ponderais', 'slug' => 'leis-ponderais', 'difficulty' => 'fundamental', 'sort_order' => 3],
                ['name' => 'Funções inorgânicas', 'slug' => 'funcoes-inorganicas', 'difficulty' => 'fundamental', 'sort_order' => 4],
                ['name' => 'Classificação das reações químicas', 'slug' => 'classificacao-reacoes', 'difficulty' => 'fundamental', 'sort_order' => 5],
                ['name' => 'Estrutura atômica e tabela periódica', 'slug' => 'estrutura-atomica-tabela-periodica', 'difficulty' => 'intermediário', 'sort_order' => 6],
                ['name' => 'Estequiometria avançada', 'slug' => 'estequiometria-avancada', 'difficulty' => 'intermediário', 'sort_order' => 7],
                ['name' => 'Soluções e propriedades coligativas', 'slug' => 'solucoes-propriedades-coligativas', 'difficulty' => 'intermediário', 'sort_order' => 8],
                ['name' => 'Química orgânica — estrutura e nomenclatura', 'slug' => 'quimica-organica-estrutura', 'difficulty' => 'intermediário', 'sort_order' => 9],
                ['name' => 'Ligações químicas e geometria molecular', 'slug' => 'ligacoes-geometria-molecular', 'difficulty' => 'avançado', 'sort_order' => 10],
                ['name' => 'Termoquímica', 'slug' => 'termoquimica', 'difficulty' => 'avançado', 'sort_order' => 11],
                ['name' => 'Cinética química', 'slug' => 'cinetica-quimica', 'difficulty' => 'avançado', 'sort_order' => 12],
                ['name' => 'Equilíbrio químico e iônico', 'slug' => 'equilibrio-quimico-ionico', 'difficulty' => 'avançado', 'sort_order' => 13],
                ['name' => 'Eletroquímica', 'slug' => 'eletroquimica', 'difficulty' => 'avançado', 'sort_order' => 14],
                ['name' => 'Bioquímica e polímeros', 'slug' => 'bioquimica-polimeros', 'difficulty' => 'avançado', 'sort_order' => 15],
                ['name' => 'Química orgânica — reações e mecanismos', 'slug' => 'quimica-organica-reacoes', 'difficulty' => 'eliminatório', 'sort_order' => 16],
            ],
            // PORTUGUÊS
            'portugues' => [
                ['name' => 'Ortografia e acentuação gráfica', 'slug' => 'ortografia-acentuacao', 'difficulty' => 'fundamental', 'sort_order' => 1],
                ['name' => 'Pontuação', 'slug' => 'pontuacao', 'difficulty' => 'fundamental', 'sort_order' => 2],
                ['name' => 'Classes de palavras (morfologia básica)', 'slug' => 'classes-palavras', 'difficulty' => 'fundamental', 'sort_order' => 3],
                ['name' => 'Gêneros literários', 'slug' => 'generos-literarios', 'difficulty' => 'fundamental', 'sort_order' => 4],
                ['name' => 'Interpretação e análise crítica de textos', 'slug' => 'interpretacao-analise-critica', 'difficulty' => 'intermediário', 'sort_order' => 5],
                ['name' => 'Literatura: estilos e períodos', 'slug' => 'literatura-estilos-periodos', 'difficulty' => 'intermediário', 'sort_order' => 6],
                ['name' => 'Gramática normativa avançada', 'slug' => 'gramatica-normativa-avancada', 'difficulty' => 'intermediário', 'sort_order' => 7],
                ['name' => 'Semântica, estilística e variação linguística', 'slug' => 'semantica-estilistica-variacao', 'difficulty' => 'avançado', 'sort_order' => 8],
                ['name' => 'Análise literária aprofundada', 'slug' => 'analise-literaria-aprofundada', 'difficulty' => 'eliminatório', 'sort_order' => 9],
                ['name' => 'Redação dissertativo-argumentativa (nível ITA)', 'slug' => 'redacao-dissertativo-argumentativa', 'difficulty' => 'eliminatório', 'sort_order' => 10],
            ],
            // INGLÊS
            'ingles' => [
                ['name' => 'Gramática fundamental (bloco inicial)', 'slug' => 'gramatica-fundamental', 'difficulty' => 'fundamental', 'sort_order' => 1],
                ['name' => 'Vocabulário básico do cotidiano', 'slug' => 'vocabulario-basico', 'difficulty' => 'fundamental', 'sort_order' => 2],
                ['name' => 'Compreensão de textos complexos (reading)', 'slug' => 'compreensao-textos', 'difficulty' => 'intermediário', 'sort_order' => 3],
                ['name' => 'Gramática avançada', 'slug' => 'gramatica-avancada', 'difficulty' => 'intermediário', 'sort_order' => 4],
                ['name' => 'Vocabulário técnico-científico', 'slug' => 'vocabulario-tecnico', 'difficulty' => 'avançado', 'sort_order' => 5],
                ['name' => 'Produção escrita formal', 'slug' => 'producao-escrita-formal', 'difficulty' => 'avançado', 'sort_order' => 6],
                ['name' => 'Análise literária em inglês', 'slug' => 'analise-literaria-ingles', 'difficulty' => 'eliminatório', 'sort_order' => 7],
            ],
            // HABILIDADES
            'habilidades' => [
                ['name' => 'Interpretar gráficos rapidamente', 'slug' => 'interpretar-graficos', 'difficulty' => 'intermediário', 'sort_order' => 1],
                ['name' => 'Traduzir texto em equações', 'slug' => 'traduzir-texto-equacoes', 'difficulty' => 'intermediário', 'sort_order' => 2],
                ['name' => 'Explicar a resolução para outra pessoa', 'slug' => 'explicar-resolucao', 'difficulty' => 'intermediário', 'sort_order' => 3],
                ['name' => 'Análise de erro após exercícios', 'slug' => 'analise-de-erro', 'difficulty' => 'intermediário', 'sort_order' => 4],
                ['name' => 'Encontrar múltiplas soluções para um problema', 'slug' => 'multiplas-solucoes', 'difficulty' => 'avançado', 'sort_order' => 5],
                ['name' => 'Resolver exercícios de olimpíadas básicas', 'slug' => 'exercicios-olimpiadas', 'difficulty' => 'avançado', 'sort_order' => 6],
                ['name' => 'Resolver sem consultar fórmulas', 'slug' => 'resolver-sem-formulas', 'difficulty' => 'eliminatório', 'sort_order' => 7],
                ['name' => 'Demonstrar resultados matemáticos', 'slug' => 'demonstrar-resultados', 'difficulty' => 'eliminatório', 'sort_order' => 8],
                ['name' => 'Resolver exercícios temporizados', 'slug' => 'exercicios-temporizados', 'difficulty' => 'eliminatório', 'sort_order' => 9],
                ['name' => 'Resolver questões discursivas', 'slug' => 'questoes-discursivas', 'difficulty' => 'eliminatório', 'sort_order' => 10],
            ],
        ];

        foreach ($topics as $subjectSlug => $topicList) {
            $subject = $subjects[$subjectSlug] ?? null;
            if (!$subject) {
                continue;
            }

            foreach ($topicList as $topic) {
                StudyTopic::updateOrCreate(
                    ['slug' => $topic['slug']],
                    array_merge($topic, ['subject_id' => $subject->id])
                );
            }
        }
    }
}
