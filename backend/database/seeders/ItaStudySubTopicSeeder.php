<?php

namespace Database\Seeders;

use App\Models\StudyTopic;
use App\Models\StudySubTopic;
use Illuminate\Database\Seeder;

class ItaStudySubTopicSeeder extends Seeder
{
    public function run(): void
    {
        $topics = StudyTopic::all()->keyBy('slug');

        $subTopics = [
            // MATEMÁTICA - Conjuntos numéricos e intervalos
            'conjuntos-numericos' => [
                'Naturais, inteiros, racionais, irracionais e reais',
                'Representação na reta real',
                'Operações e propriedades dos números reais',
                'Intervalos: tipos, união, interseção e complemento',
            ],
            // MATEMÁTICA - Potenciação e radiciação
            'potenciacao-radiciacao' => [
                'Propriedades de potências (expoente inteiro, racional, real)',
                'Raízes n-ésimas e radicais: simplificação e operações',
                'Racionalização de denominadores',
                'Relação entre potências e logaritmos (visão introdutória)',
            ],
            // MATEMÁTICA - Produtos notáveis e fatoração
            'produtos-notaveis-fatoracao' => [
                'Quadrado e cubo da soma/diferença',
                'Diferença de quadrados e soma/diferença de cubos',
                'Fatoração por agrupamento e trinômio do 2º grau',
                'Completamento de quadrados',
            ],
            // MATEMÁTICA - Equações e inequações do 1º e 2º graus
            'equacoes-inequacoes-1-2-graus' => [
                'Resolução e interpretação gráfica',
                'Discriminante, soma e produto das raízes',
                'Estudo do sinal do trinômio e inequações',
                'Sistemas simples (substituição, adição)',
            ],
            // MATEMÁTICA - Função afim e função quadrática
            'funcao-afim-quadratica' => [
                'Coeficientes, raiz e crescimento da afim',
                'Forma canônica da quadrática, vértice e eixo de simetria',
                'Interceptos, gráfico e máximo/mínimo',
                'Inequações quadráticas e intervalos de sinal',
            ],
            // MATEMÁTICA - Razão, proporção, regra de três e porcentagem
            'razao-proporcao-regra3-porcentagem' => [
                'Grandezas direta e inversamente proporcionais',
                'Regra de três simples e composta',
                'Porcentagem, variação percentual e juros simples',
                'Escalas e problemas de mistura introdutórios',
            ],
            // MATEMÁTICA - Geometria plana — fundamentos
            'geometria-plana-fundamentos' => [
                'Ângulos, retas paralelas e transversal',
                'Classificação de triângulos',
                'Congruência (LLL, LAL, ALA, LAAo)',
                'Teorema de Pitágoras e aplicações',
                'Áreas: quadrado, retângulo, triângulo, círculo e setor circular',
            ],
            // MATEMÁTICA - Trigonometria no triângulo retângulo
            'trigonometria-triangulo-retangulo' => [
                'Seno, cosseno e tangente',
                'Relações entre razões e triângulos especiais',
                'Resolução de triângulos retângulos',
                'Aplicações a geometria e problemas contextualizados',
            ],
            // MATEMÁTICA - Lógica matemática e teoria dos conjuntos
            'logica-matematica-teoria-conjuntos' => [
                'Proposições, conectivos, tabelas-verdade',
                'Quantificadores universal e existencial',
                'Operações com conjuntos e diagramas de Venn',
                'Relações de inclusão e igualdade',
            ],
            // MATEMÁTICA - Funções reais: propriedades e gráficos
            'funcoes-reais-propriedades-graficos' => [
                'Domínio, imagem, injeção, sobrejeção, bijeção',
                'Composição e função inversa',
                'Funções pares, ímpares, periódicas e convexas',
                'Translações e reflexões de gráficos',
            ],
            // MATEMÁTICA - Funções polinomiais e equações
            'funcoes-polinomiais-equacoes' => [
                'Divisão polinomial, Briot-Ruffini, teorema do resto',
                'Relações de Girard entre raízes e coeficientes',
                'Teorema das raízes racionais (coeficientes inteiros)',
                'Multiplicidade de raízes e localização (teorema de Bolzano)',
                'Polinômios simétricos e transformações de raízes',
                'Equações biquadráticas e módulo',
            ],
            // MATEMÁTICA - Funções exponenciais e logarítmicas
            'funcoes-exponenciais-logaritmicas' => [
                'Propriedades e gráficos',
                'Equações e inequações exp/log',
                'Mudança de base e logaritmo natural',
                'Crescimento e decaimento exponencial',
            ],
            // MATEMÁTICA - Trigonometria completa
            'trigonometria-completa' => [
                'Ciclo trigonométrico e relações fundamentais',
                'Fórmulas de adição, subtração, arco duplo e metade',
                'Equações e inequações trigonométricas',
                'Transformações entre produto e soma',
                'Funções inversas: arcsen, arccos, arctg',
            ],
            // MATEMÁTICA - Números complexos
            'numeros-complexos' => [
                'Forma algébrica: operações e conjugado',
                'Forma polar e trigonométrica',
                'Fórmula de De Moivre e raízes n-ésimas',
                'Raízes de polinômios com coef. reais',
                'Interpretação geométrica no plano de Argand',
            ],
            // MATEMÁTICA - Geometria plana analítica
            'geometria-plana-analitica' => [
                'Retas: equações, distâncias, ângulo entre retas',
                'Cônicas: parábola, elipse, hipérbole (equações canônicas e transladadas)',
                'Círculo: equações, tangência, potência de ponto',
                'Transformações geométricas no plano (translação, rotação, reflexão, homotetia)',
                'Lugar geométrico clássico',
            ],
            // MATEMÁTICA - Vetores no plano e no espaço
            'vetores-plano-espaco' => [
                'Operações vetoriais e combinação linear',
                'Produto escalar: ângulo, projeção, ortogonalidade',
                'Produto vetorial: módulo, direção, área de paralelogramo',
                'Produto misto: volume de paralelepípedo',
                'Equações de reta e plano no espaço',
            ],
            // MATEMÁTICA - Geometria espacial
            'geometria-espacial' => [
                'Posições relativas de retas e planos',
                'Prismas, pirâmides, cilindros, cones e esferas: áreas e volumes',
                'Poliedros convexos e relação de Euler',
                'Seções e planificações',
                'Inscrição e circunscrição de sólidos',
            ],
            // MATEMÁTICA - Matrizes, determinantes e sistemas lineares
            'matrizes-determinantes-sistemas' => [
                'Operações com matrizes e propriedades',
                'Determinante (Sarrus, Laplace, por operações)',
                'Sistemas lineares: regra de Cramer, escalonamento de Gauss',
                'Posto e discussão de sistemas',
                'Inversão de matrizes',
            ],
            // MATEMÁTICA - Sequências, progressões e recursão
            'sequencias-progressoes-recursao' => [
                'PA e PG: termo geral e soma',
                'Sequências definidas por recorrência linear',
                'Sequências mistas e encaixadas',
                'Somas telescópicas e técnicas de partição',
                'Séries numéricas elementares (soma de séries geométricas infinitas)',
                'Limites de sequências (noção intuitiva)',
            ],
            // MATEMÁTICA - Análise combinatória e probabilidade
            'analise-combinatoria-probabilidade' => [
                'Princípio multiplicativo e aditivo',
                'Arranjos, permutações (simples e c/ repetição), combinações',
                'Binômio de Newton e coeficientes binomiais',
                'Probabilidade clássica e condicional',
                'Teorema de Bayes',
                'Variáveis aleatórias e distribuição binomial',
                'Esperança e variância',
            ],
            // MATEMÁTICA - Teoria dos números
            'teoria-dos-numeros' => [
                'Divisibilidade, MDC e MMC (algoritmo de Euclides)',
                'Congruências módulo n e pequeno teorema de Fermat',
                'Teorema de Euler (φ(n)) e função totiente',
                'Representação em diferentes bases (binário, octal, hexadecimal)',
                'Números primos: crivo de Eratóstenes, infinidade dos primos',
                'Equações diofantinas lineares (ax+by=c) e existência de soluções',
            ],
            // MATEMÁTICA - Indução matemática e raciocínio dedutivo
            'inducao-matematica-raciocinio' => [
                'Princípio da indução fraca e forte',
                'Demonstrações por absurdo e contraposição',
                'Aplicações em somas, divisibilidade e desigualdades',
            ],
            // MATEMÁTICA - Funções implícitas e paramétricas
            'funcoes-implicitas-parametricas' => [
                'Curvas paramétricas no plano',
                'Passagem entre formas paramétrica e explícita',
                'Elipse e hipérbole em forma paramétrica',
            ],
            // MATEMÁTICA - Geometria plana sintética avançada
            'geometria-plana-sintetica-avancada' => [
                'Semelhança de triângulos e teoremas de Tales',
                'Relações métricas: lei dos senos e cossenos',
                'Áreas por diferentes métodos',
                'Pontos notáveis do triângulo (centróide, ortocentro, incentro, circuncentro)',
                'Potência de ponto e propriedades do círculo',
                'Polígonos inscritos e circunscritos',
            ],
            // MATEMÁTICA - Desigualdades clássicas
            'desigualdades-classicas' => [
                'AM-GM e suas generalizações',
                'Desigualdade de Cauchy-Schwarz',
                'Desigualdade triangular',
                'Desigualdades com módulo (|a+b| ≤ |a|+|b|)',
                'Desigualdade de Jensen (introdução e aplicações convexas)',
                'Manipulação algébrica de inequações',
                'Aplicações em otimização sem cálculo',
            ],
            // MATEMÁTICA - Cálculo diferencial e integral
            'calculo-diferencial-integral' => [
                'Limites (definição intuitiva, limites notáveis, L\'Hôpital)',
                'Assíntotas horizontais, verticais e oblíquas',
                'Crescimento assintótico e comparação de funções',
                'Derivadas: regras, cadeia, derivadas de ord. superior',
                'Aplicações: máx/mín, taxas relacionadas, esboço de curvas',
                'Primitivas e integrais imediatas',
                'Integral definida e Teorema Fundamental do Cálculo',
                'Área entre curvas, volume de sólidos de revolução (básico)',
                'Equações diferenciais de 1ª ordem separáveis',
            ],
            // FÍSICA - Grandezas físicas e SI
            'grandezas-fisicas-si' => [
                'Unidades fundamentais e derivadas',
                'Conversão de unidades e fatores de conversão',
                'Grandezas escalares e vetoriais (noção)',
                'Ordem de magnitude e estimativas rápidas',
            ],
            // FÍSICA - Notação científica e algarismos significativos
            'notacao-cientifica-algarismos' => [
                'Operações com potências de 10',
                'Critérios de algarismos significativos em medições',
                'Arredondamento e propagação em operações',
                'Representação compacta de números muito grandes/pequenos',
            ],
            // FÍSICA - Análise dimensional
            'analise-dimensional' => [
                'Dimensões de grandezas físicas',
                'Verificação de consistência de fórmulas',
                'Previsão de dependências por dimensão',
                'Sistemas de unidades coerentes',
            ],
            // FÍSICA - Vetores na Física
            'vetores-na-fisica' => [
                'Decomposição em componentes cartesianas',
                'Soma e subtração vetorial e resultante',
                'Vetor unitário e projeção ortogonal',
                'Aplicação a forças, velocidades e deslocamentos',
            ],
            // FÍSICA - Hidrostática conceitos iniciais
            'hidrostatica-conceitos-iniciais' => [
                'Densidade, massa específica e peso específico',
                'Pressão em fluidos e pressão atmosférica',
                'Experiência de Torricelli e barômetro',
                'Noção de empuxo (ponte para Arquimedes)',
            ],
            // FÍSICA - Fundamentos de óptica geométrica
            'fundamentos-optica-geometrica' => [
                'Propagação retilínea da luz',
                'Sombras, penumbra e eclipses',
                'Câmara escura e modelos simples',
                'Princípios de independência e reversibilidade da luz',
            ],
            // FÍSICA - Cinemática escalar e vetorial
            'cinematica-escalar-vetorial' => [
                'MU e MRUV: equações, gráficos e interpretação',
                'Lançamento oblíquo e composição de movimentos',
                'Movimento circular (velocidade angular, centripetal)',
                'Análise de gráficos x-t, v-t, a-t',
            ],
            // FÍSICA - Dinâmica: leis de Newton
            'dinamica-leis-newton' => [
                '1ª, 2ª e 3ª leis e aplicações',
                'Forças de atrito (estático e cinético)',
                'Plano inclinado e máquina de Atwood',
                'Força elástica (lei de Hooke)',
                'Força centrípeta e movimento circular dinâmico',
            ],
            // FÍSICA - Trabalho, energia e potência
            'trabalho-energia-potencia' => [
                'Teorema trabalho-energia cinética',
                'Energias potencial gravitacional e elástica',
                'Conservação de energia mecânica',
                'Trabalho de forças dissipativas',
                'Potência média e instantânea',
            ],
            // FÍSICA - Impulso, momento e colisões
            'impulso-momento-colisoes' => [
                'Conservação da quantidade de movimento',
                'Colisões elásticas, inelásticas e perfeitamente inelásticas',
                'Coeficiente de restituição',
                'Centro de massa: posição e movimento',
            ],
            // FÍSICA - Oscilações: MHS e ondas mecânicas
            'oscilacoes-mhs-ondas' => [
                'MHS: equações de x(t), v(t), E(t) — massa-mola e pêndulo',
                'Período, frequência, amplitude e fase',
                'Ondas: comprimento, velocidade, frequência, fase',
                'Reflexão, refração, interferência e difração',
                'Efeito Doppler (fonte e observador em mov.)',
                'Ondas estacionárias e ressonância em cordas/tubos',
            ],
            // FÍSICA - Termodinâmica completa
            'termodinamica-completa' => [
                'Temperatura, calor e capacidade calorífica',
                'Mudanças de fase e calor latente',
                'Dilatação térmica',
                'Leis dos gases ideais e teoria cinética',
                '1ª lei da termodinâmica (ΔU=Q−W)',
                'Processos: isovolumétrico, isobárico, isotérmico, adiabático',
                '2ª lei (Kelvin-Planck e Clausius), ciclo de Carnot',
                'Entropia (qualitativo)',
            ],
            // FÍSICA - Eletrostática
            'eletrostatica' => [
                'Lei de Coulomb e princípio da superposição',
                'Campo elétrico (uniforme e puntual)',
                'Potencial elétrico e diferença de potencial',
                'Condutores em equilíbrio eletrostático',
                'Capacitores planos: capacitância, associações, energia armazenada',
                'Efeitos de dielétricos (qualitativo)',
            ],
            // FÍSICA - Eletrodinâmica e circuitos
            'eletrodinamica-circuitos' => [
                'Corrente elétrica, densidade de corrente',
                'Resistência, resistividade e dependência térmica',
                'Lei de Ohm e curvas I-V',
                'Associações de resistores em série e paralelo',
                'Leis de Kirchhoff (malhas e nós) — circuitos multi-malha',
                'Potência dissipada e efeito Joule',
                'Circuitos com capacitores (regime estacionário)',
            ],
            // FÍSICA - Dinâmica de rotação
            'dinamica-de-rotacao' => [
                'Torque e equilíbrio estático (1ª e 2ª condições)',
                'Momento de inércia de corpos simples',
                'Equação fundamental da rotação (τ=Iα)',
                'Energia cinética de rotação',
                'Rolamento sem deslizamento',
                'Conservação do momento angular',
            ],
            // FÍSICA - Gravitação universal
            'gravitacao-universal' => [
                'Lei de gravitação de Newton',
                'Leis de Kepler (demonstração da 3ª para órbitas circulares)',
                'Campo e potencial gravitacional',
                'Velocidade de escape e orbital',
                'Energia em órbitas circulares e elípticas',
            ],
            // FÍSICA - Hidrostática e hidrodinâmica
            'hidrostatica-hidrodinamica' => [
                'Pressão hidrostática e princípio de Pascal',
                'Princípio de Arquimedes e empuxo',
                'Equação da continuidade (A·v = cte)',
                'Equação de Bernoulli e aplicações (Torricelli, tubo de Venturi)',
                'Tensão superficial e capilaridade (qualitativo)',
            ],
            // FÍSICA - Eletromagnetismo
            'eletromagnetismo' => [
                'Campo magnético de fio, espira e solenoide',
                'Força de Lorentz sobre carga e sobre condutor',
                'Força entre fios paralelos',
                'Fluxo magnético e lei de Faraday (indução)',
                'Lei de Lenz e sentido da corrente induzida',
                'Autoindutor: energia e fem auto-induzida',
                'Transformadores e transmissão de energia',
                'Leis de Maxwell (interpretação qualitativa)',
                'Ondas eletromagnéticas: espectro e velocidade',
            ],
            // FÍSICA - Óptica geométrica e física
            'optica-geometrica-fisica' => [
                'Reflexão: espelhos planos e esféricos (eq. de Gauss)',
                'Refração: lei de Snell, índice de refração',
                'Reflexão total interna',
                'Lentes delgadas (eq. dos fabricantes, ampliação)',
                'Prismas e dispersão da luz',
                'Interferência (Young): franjas e condições',
                'Difração: fenda simples e rede de difração',
                'Polarização: lei de Malus',
            ],
            // FÍSICA - Física moderna e relatividade
            'fisica-moderna-relatividade' => [
                'Efeito fotoelétrico e hipótese de Planck',
                'Dualidade onda-partícula e relação de de Broglie',
                'Princípio de incerteza de Heisenberg (qualitativo)',
                'Modelos atômicos até Bohr: espectros de emissão e absorção',
                'Radioatividade: α, β, γ; leis de conservação em decaimentos',
                'Fissão, fusão e energia de ligação nuclear',
                'Dilatação do tempo e contração do espaço (Relatividade Especial)',
                'Equivalência massa-energia: E=mc²',
                'Composição relativística de velocidades (básico)',
            ],
            // QUÍMICA - Matéria e suas transformações
            'materia-transformacoes' => [
                'Fenômenos físicos vs. químicos',
                'Substâncias puras (simples e compostas) e misturas homogêneas/heterogêneas',
                'Curvas de aquecimento e resfriamento',
                'Diagrama de fases básico (sólido, líquido, vapor)',
            ],
            // QUÍMICA - Separação de misturas
            'separacao-misturas' => [
                'Filtração, decantação e centrifugação',
                'Destilação simples e fracionada',
                'Cromatografia (princípio e aplicações qualitativas)',
                'Cristalização e métodos complementares',
            ],
            // QUÍMICA - Leis ponderais
            'leis-ponderais' => [
                'Lei de Lavoisier (conservação das massas)',
                'Lei de Proust (proporções definidas)',
                'Lei de Dalton (proporções múltiplas)',
                'Relação com fórmulas e composição centesimal',
            ],
            // QUÍMICA - Funções inorgânicas
            'funcoes-inorganicas' => [
                'Ácidos, bases, sais e óxidos: classificação',
                'Nomenclatura IUPAC e usual',
                'Propriedades e reações típicas (neutralização, óxidos ácidos/básicos)',
                'Previsão de produtos em reações inorgânicas simples',
            ],
            // QUÍMICA - Classificação das reações químicas
            'classificacao-reacoes' => [
                'Síntese, decomposição, simples troca e dupla troca',
                'Introdução a oxirredução e número de oxidação (Nox)',
                'Identificação de agente oxidante e redutor',
                'Ponte para balanceamento avançado',
            ],
            // QUÍMICA - Estrutura atômica e tabela periódica
            'estrutura-atomica-tabela-periodica' => [
                'Modelos atômicos históricos',
                'Números quânticos e configuração eletrônica (Aufbau, Hund, Pauli)',
                'Propriedades periódicas: raio, eletronegatividade, EA, IE',
                'Elementos de transição e transição interna',
            ],
            // QUÍMICA - Estequiometria avançada
            'estequiometria-avancada' => [
                'Balanceamento por oxirredução (método de mudança de N.O. e íon-elétron)',
                'Cálculos em soluções e titulações',
                'Reagente limitante e rendimento',
                'Fórmulas mínima, molecular e estrutural',
                'Mistura de gases: lei de Dalton e pressão parcial',
            ],
            // QUÍMICA - Soluções e propriedades coligativas
            'solucoes-propriedades-coligativas' => [
                'Concentrações: mol/L, g/L, % massa, fração molar, ppm',
                'Solubilidade e curvas de solubilidade',
                'Abaixamento da pressão de vapor (Raoult)',
                'Ebulioscopia e crioscopia',
                'Pressão osmótica',
                'Efeito de eletrólitos (fator de van\'t Hoff)',
            ],
            // QUÍMICA - Química orgânica — estrutura e nomenclatura
            'quimica-organica-estrutura' => [
                'Hibridização e isomeria constitucional (cadeia, posição, função)',
                'Isomeria geométrica (cis-trans) e óptica (quiralidade, enantiômeros)',
                'Nomenclatura IUPAC de todas as funções orgânicas',
                'Efeitos indutivo e mesomério (ativação/desativação em aromáticos)',
            ],
            // QUÍMICA - Ligações químicas e geometria molecular
            'ligacoes-geometria-molecular' => [
                'Ligação iônica: estrutura cristalina e energia de rede',
                'Ligação covalente: Lewis, ligações σ e π, ressonância',
                'Hibridização sp, sp², sp³ e geometria VSEPR',
                'Ligação metálica e propriedades dos metais',
                'Forças intermoleculares: dipolo-dipolo, London, ligação de H',
                'Polaridade de moléculas e solventes',
            ],
            // QUÍMICA - Termoquímica
            'termoquimica' => [
                'Entalpia de formação e reação (ΔHf°)',
                'Lei de Hess e ciclos termodinâmicos',
                'Energia de ligação e cálculo de ΔH',
                'Entalpia de combustão e de neutralização',
                'Entropia e energia de Gibbs (G=H−TS): espontaneidade',
            ],
            // QUÍMICA - Cinética química
            'cinetica-quimica' => [
                'Expressão da velocidade de reação',
                'Ordem de reação e lei da velocidade',
                'Determinação de ordem a partir de dados experimentais',
                'Energia de ativação e equação de Arrhenius',
                'Mecanismo de reação e etapa determinante',
                'Catálise homogênea, heterogênea e enzimática',
            ],
            // QUÍMICA - Equilíbrio químico e iônico
            'equilibrio-quimico-ionico' => [
                'Constantes Kc e Kp: cálculo e relação',
                'Princípio de Le Chatelier (T, P, concentração)',
                'Hidrólise salina e soluções tampão',
                'pH, pOH, Ka, Kb, Kw: cálculos e espécies dominantes',
                'Equilíbrio de solubilidade (Kps) e efeito do íon comum',
                'Complexos de coordenação (básico)',
            ],
            // QUÍMICA - Eletroquímica
            'eletroquimica' => [
                'Células galvânicas: notação, espontaneidade',
                'Potencial padrão de eletrodo e FEM',
                'Equação de Nernst',
                'Eletrólise: cálculos com lei de Faraday',
                'Aplicações: baterias, eletrodeposição, proteção catódica',
            ],
            // QUÍMICA - Bioquímica e polímeros
            'bioquimica-polimeros' => [
                'Aminoácidos, peptídeos e proteínas (estruturas primária a quaternária)',
                'Carboidratos: monossacarídeos, dissacarídeos, polissacarídeos',
                'Lipídeos: ácidos graxos, fosfolipídeos, esteróis',
                'DNA e RNA: estrutura, replicação e transcrição (qualitativo)',
                'Enzimas: cinética de Michaelis-Menten (qualitativo)',
            ],
            // QUÍMICA - Química orgânica — reações e mecanismos
            'quimica-organica-reacoes' => [
                'Substituição nucleofílica: SN1 e SN2 (estereoquímica e cinética)',
                'Eliminação: E1 e E2, regra de Zaitsev',
                'Adição eletrofílica em alcenos e alcinos',
                'Adição nucleofílica em carbonila (aldeídos, cetonas)',
                'Substituição eletrofílica aromática',
                'Reações de condensação: esterificação, amidação, transesterificação',
                'Polímeros de adição e condensação',
                'Testes de identificação e análise qualitativa de grupos funcionais',
            ],
            // PORTUGUÊS - Ortografia e acentuação gráfica
            'ortografia-acentuacao' => [
                'Regras do Acordo Ortográfico vigente',
                'Acentuação de oxítonas, paroxítonas e proparoxítonas',
                'Hiatos, ditongos e triptongos',
                'Emprego do hífen e composição',
                'Letras problemáticas (s/z, x/ch, g/j, h)',
            ],
            // PORTUGUÊS - Pontuação
            'pontuacao' => [
                'Uso da vírgula (enumeração, aposto, vocativo, orações)',
                'Ponto e vírgula, dois-pontos e travessão',
                'Aspas e parênteses: funções e hierarquia',
                'Efeitos de sentido e ambiguidade criados pela pontuação',
            ],
            // PORTUGUÊS - Classes de palavras
            'classes-palavras' => [
                'Substantivo, adjetivo, artigo e numeral',
                'Pronome, verbo, advérbio',
                'Preposição, conjunção e interjeição',
                'Identificação em contexto e flexões essenciais',
            ],
            // PORTUGUÊS - Gêneros literários
            'generos-literarios' => [
                'Épico, lírico e dramático: traços distintivos',
                'Formas poéticas (soneto, ode, elegia, etc.)',
                'Relação gênero × período × leitura de prova',
                'Apoio à interpretação e à análise literária',
            ],
            // PORTUGUÊS - Interpretação e análise crítica de textos
            'interpretacao-analise-critica' => [
                'Textos filosóficos, científicos e literários de alta complexidade',
                'Pressupostos, subentendidos e implícitos',
                'Argumentação: teses, estratégias e falácias',
                'Intertextualidade, ironia, paródia, pastiche',
                'Gêneros textuais: dissertação, crônica, conto, ensaio',
            ],
            // PORTUGUÊS - Literatura: estilos e períodos
            'literatura-estilos-periodos' => [
                'Trovadorismo, Humanismo e Quinhentismo',
                'Barroco (Vieira, Gregório de Matos)',
                'Arcadismo e Neoclassicismo',
                'Romantismo (todas as gerações em prosa e poesia)',
                'Realismo, Naturalismo e Parnasianismo',
                'Simbolismo e Pré-Modernismo',
                'Modernismo: 1ª fase (22), 2ª fase (prosa e poesia), 3ª fase',
                'Literatura contemporânea relevante para o ITA',
            ],
            // PORTUGUÊS - Gramática normativa avançada
            'gramatica-normativa-avancada' => [
                'Morfossintaxe completa: funções e classificações',
                'Concordância verbal e nominal — casos especiais',
                'Regência verbal e nominal: verbos de regência dupla',
                'Crase: todos os casos e exceções',
                'Colocação pronominal (ênclise, próclise, mesóclise)',
                'Período composto: coordenação e subordinação (todos os tipos)',
            ],
            // PORTUGUÊS - Semântica, estilística e variação linguística
            'semantica-estilistica-variacao' => [
                'Denotação, conotação, polissemia e ambiguidade',
                'Campos semânticos e relações de sinonímia/antonímia',
                'Variação diatópica, diastrática e diafásica',
                'Norma culta vs. variedades populares',
                'Funções da linguagem (Jakobson) e seus efeitos',
            ],
            // PORTUGUÊS - Análise literária aprofundada
            'analise-literaria-aprofundada' => [
                'Figuras de linguagem (identificação e efeito estético)',
                'Versificação: métrica, rima, ritmo, estrofes',
                'Estilística: escolhas lexicais e sintáticas como recurso expressivo',
                'Obras canônicas do ITA (Machado, Drummond, Rosa, Clarice, Pessoa)',
                'Leitura de poemas herméticos e análise de ironia',
            ],
            // PORTUGUÊS - Redação dissertativo-argumentativa
            'redacao-dissertativo-argumentativa' => [
                'Elaboração de tese clara e defensável',
                'Argumentos: dados, exemplos, autoridade, analogia, refutação',
                'Repertório cultural diversificado (filosofia, ciência, história, artes)',
                'Coesão textual (coesão referencial e sequencial)',
                'Proposta de intervenção fundamentada e detalhada',
                'Vícios de linguagem, clichês e imprecisão a evitar',
                'Estudo de redações nota máxima (ITA e FUVEST)',
            ],
            // INGLÊS - Gramática fundamental
            'gramatica-fundamental' => [
                'Artigos definido e indefinido (the, a/an)',
                'Substantivos: contáveis e incontáveis, plural regular e irregular',
                'Quantifiers: some, any, much, many, a lot of, few, little',
                'Preposições de tempo, lugar e movimento',
                'Simple present, present continuous, simple past, past continuous',
                'Future com will e going to (noções e contrastes)',
            ],
            // INGLÊS - Vocabulário básico
            'vocabulario-basico' => [
                'Família, rotina e hábitos diários',
                'Clima, alimentação e vestuário',
                'Saúde, corpo e sintomas básicos',
                'Transportes, cidade e direções',
                'Base para inferência em textos e produção formal',
            ],
            // INGLÊS - Compreensão de textos
            'compreensao-textos' => [
                'Textos científicos, filosóficos e literários em inglês',
                'Inferência contextual e vocabulário por contexto',
                'Estrutura argumentativa e coesão',
                'Referenciação (anáfora, catáfora, elipse)',
                'Leitura crítica: viés, tom, propósito do autor',
            ],
            // INGLÊS - Gramática avançada
            'gramatica-avancada' => [
                'Tempos verbais: todos os tenses',
                'Modais e nuances de probabilidade, obrigação, permissão',
                'Conditionals: 0, 1ª, 2ª, 3ª e mistas',
                'Reported speech (discurso indireto) e mudanças de tempo',
                'Relative clauses: defining e non-defining',
                'Inversão e ênfase (fronting, cleft sentences)',
                'Gerund vs. infinitive (lista de verbos e regras)',
                'Passive voice em todos os tempos',
                'Subjunctive e wish/would rather constructions',
            ],
            // INGLÊS - Vocabulário técnico-científico
            'vocabulario-tecnico' => [
                'Prefixos e sufixos gregos e latinos',
                'Faux amis e falsos cognatos (lista crítica)',
                'Campos semânticos de ciência, tecnologia e filosofia',
                'Phrasal verbs de uso acadêmico',
                'Expressões idiomáticas de registro formal',
            ],
            // INGLÊS - Produção escrita formal
            'producao-escrita-formal' => [
                'Organização de parágrafo acadêmico (topic sentence, support, conclusion)',
                'Coesão e coerência em inglês (linking words por função)',
                'Pontuação em inglês (diferenças do português)',
                'Precisão léxica e variedade sintática',
            ],
            // INGLÊS - Análise literária em inglês
            'analise-literaria-ingles' => [
                'Poesia em inglês: métrica, aliteração, assonância, enjambment',
                'Figuras de linguagem em inglês (metaphor, irony, hyperbole)',
                'Textos de Shakespeare e literatura clássica',
                'Análise de prosa literária: ponto de vista, voz narrativa, estilo',
            ],
            // HABILIDADES - Interpretar gráficos
            'interpretar-graficos' => [
                'Leitura de gráficos de funções (domínio, imagem, zeros, sinais)',
                'Identificação de assíntotas, máximos e mínimos visualmente',
                'Esboço rápido de curvas a partir de características qualitativas',
            ],
            // HABILIDADES - Traduzir texto em equações
            'traduzir-texto-equacoes' => [
                'Interpretação de enunciados de problemas e extração das relações matemáticas',
                'Problemas de mistura, taxa, geometria e combinatória descritos em texto',
                'Checagem de consistência dimensional e verificação da solução',
            ],
            // HABILIDADES - Explicar a resolução
            'explicar-resolucao' => [
                'Técnica Feynman: ensinar para detectar lacunas',
                'Articulação verbal ou escrita do raciocínio passo a passo',
                'Identificação de onde a explicação trava = onde está a dúvida real',
            ],
            // HABILIDADES - Análise de erro
            'analise-de-erro' => [
                'Caderno de erros: registrar o erro, a causa e a solução correta',
                'Distinção entre erro de conta, erro de conceito e erro de interpretação',
                'Revisão periódica do caderno de erros para fechar lacunas recorrentes',
            ],
            // HABILIDADES - Múltiplas soluções
            'multiplas-solucoes' => [
                'Abordagens algébrica, geométrica e analítica para o mesmo problema',
                'Verificação cruzada de resultados por métodos distintos',
                'Flexibilidade para trocar de estratégia quando a primeira trava',
            ],
            // HABILIDADES - Exercícios de olimpíadas
            'exercicios-olimpiadas' => [
                'OBMEP nível 1 e 2 como termômetro de raciocínio',
                'Problemas de contagem, teoria dos números e geometria olímpica',
                'Desenvolvimento da criatividade na busca de soluções elegantes',
            ],
            // HABILIDADES - Resolver sem consultar fórmulas
            'resolver-sem-formulas' => [
                'Domínio total das fórmulas fundamentais: trigonometria, álgebra, geometria',
                'Prática deliberada até automatização das principais identidades',
                'Simulados sem cola para calibrar o nível real',
            ],
            // HABILIDADES - Demonstrar resultados matemáticos
            'demonstrar-resultados' => [
                'Demonstrações diretas e por absurdo',
                'Indução matemática fraca e forte',
                'Demonstrações de fórmulas clássicas (soma de PA, PG, identidades trig.)',
            ],
            // HABILIDADES - Exercícios temporizados
            'exercicios-temporizados' => [
                'Simulados com controle de tempo (ITA: ~30 min por questão)',
                'Estratégia de priorização: fazer o que sabe primeiro',
                'Gestão de ansiedade e consistência sob pressão',
            ],
            // HABILIDADES - Questões discursivas
            'questoes-discursivas' => [
                'Clareza e organização da argumentação escrita',
                'Justificativa de cada passo (formato ITA e FUVEST)',
                'Uso correto de notação matemática em provas dissertativas',
            ],
        ];

        foreach ($subTopics as $topicSlug => $subTopicList) {
            $topic = $topics[$topicSlug] ?? null;
            if (!$topic) {
                continue;
            }

            foreach ($subTopicList as $index => $subTopicName) {
                $slug = \Illuminate\Support\Str::slug($subTopicName);
                StudySubTopic::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'topic_id' => $topic->id,
                        'name' => $subTopicName,
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }
}
