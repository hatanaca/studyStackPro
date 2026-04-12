# Documentacao Tecnica do StudyTrack Pro

## 1. Objetivo deste documento

Este documento consolida, em um unico lugar, a explicacao tecnica do projeto `StudyTrack Pro` com base no estado atual do repositorio. O foco aqui nao e marketing nem onboarding rapido: a proposta e descrever com profundidade como o sistema esta organizado, quais tecnologias utiliza, como os componentes se relacionam, quais fluxos operacionais existem, como a infraestrutura foi montada e quais pontos de atencao aparecem no codigo e na configuracao.

O material foi estruturado para servir a pelo menos quatro usos:

- entendimento arquitetural do sistema como um todo;
- aceleracao de onboarding tecnico;
- apoio a manutencao, refatoracao e auditoria;
- referencia para evolucoes futuras e alinhamento entre frontend, backend e infraestrutura.

## 2. Escopo e fonte das informacoes

As informacoes deste documento foram derivadas da leitura do proprio repositorio, especialmente dos seguintes arquivos e areas:

- `README.md`
- `docs/README.md` e `docs/technical/*`
- `Makefile`
- `docker-compose.yml`
- `backend/README.md`
- `backend/composer.json`
- `frontend/README.md`
- `frontend/package.json`
- estrutura de codigo em `backend/app`, `backend/routes`, `backend/config`, `backend/database`
- estrutura de codigo em `frontend/src`
- arquivos de CI em `.github/workflows`
- configuracoes de hooks em `.husky`
- configuracoes Docker em `docker/`

Quando houver divergencia entre documentacao existente e implementacao observada no codigo, este documento prioriza o comportamento aparente do codigo e registra a divergencia como ponto de atencao.

## 3. Visao geral do produto

`StudyTrack Pro` e uma plataforma full-stack para acompanhamento de estudos e produtividade. O sistema permite que usuarios registrem sessoes de estudo, associem essas sessoes a tecnologias especificas, acompanhem indicadores consolidados em um dashboard, visualizem distribuicoes por tecnologia, heatmaps de atividade e outros dados derivados.

Em termos de proposta tecnica, o projeto combina:

- uma SPA moderna em Vue 3 com TypeScript;
- uma API REST em Laravel 11;
- persistencia transacional em PostgreSQL;
- cache, filas e pub/sub em Redis;
- processamento assincrono de metricas via jobs;
- atualizacao quase em tempo real via WebSocket;
- empacotamento e execucao local por Docker Compose.

O repositorio tambem demonstra preocupacoes comuns de aplicacoes reais:

- separacao de responsabilidades entre camadas;
- uso de filas para trabalho pesado;
- ambiente de desenvolvimento conteinerizado;
- lint, testes e type-check automatizados;
- workflow de CI para frontend e backend;
- hooks de pre-commit para reduzir regressao de qualidade.

## 4. Estrutura macro do repositorio

O projeto nao usa workspaces Node, NX, Turborepo ou outra ferramenta de monorepo orientada a pacotes. Em vez disso, adota um repositorio unico que agrega tres grandes blocos:

### 4.1 Aplicacoes principais

- `backend/`: aplicacao Laravel 11 responsavel pela API, autenticacao, persistencia, filas, broadcasting e logica de negocio central.
- `frontend/`: aplicacao Vue 3 responsavel pela interface web, navegacao, gerenciamento de estado local, consumo da API e experiencia do usuario.

### 4.2 Infraestrutura e automacao

- `docker/`: Dockerfiles, OpenResty (proxy), Redis, Postgres (imagem com PL/Lua quando aplicavel) e imagens auxiliares.
- `docker-compose.yml`: sobe a stack principal.
- `docker-compose.dev.yml`: adiciona utilitarios de desenvolvimento, como pgAdmin e Mailpit.
- `redis-scripts/`: scripts Lua (`job_dedup`, `sliding_window`, `streak_update`) montados nos containers PHP/Horizon/Reverb conforme compose.
- `Makefile`: camada de automacao para setup, execucao, migracoes, testes e acesso aos containers.

### 4.3 Governanca e qualidade

- `.github/workflows/`: CI de backend, frontend e pipeline de imagens.
- `.husky/`: hooks de git.
- `commitlint.config.js`: padrao de mensagens de commit.
- `README.md`, `backend/README.md`, `frontend/README.md`, `docker/README.md`: documentacao existente.

## 5. Arquitetura de alto nivel

Do ponto de vista arquitetural, o sistema e um monolito full-stack separado por camadas e por contexto de dominio.

### 5.1 Visao resumida

1. O usuario interage com a SPA em `frontend/`.
2. A SPA consome a API em `backend/` por HTTP, em geral sob `/api/v1`.
3. O backend processa a requisicao, persiste dados no PostgreSQL e usa Redis para cache, filas e comunicacao de tempo real.
4. Alteracoes relevantes, como criacao, atualizacao ou encerramento de sessoes, disparam eventos.
5. Listeners e jobs atualizam metricas derivadas e limpam caches.
6. Eventos broadcastados notificam o frontend, que atualiza partes do dashboard sem recarga completa.

### 5.2 Estilo arquitetural do backend

O backend mistura caracteristicas de:

- API REST tradicional;
- modularizacao por dominio;
- padrao service layer;
- repositorios para encapsular acesso a dados;
- fluxo orientado a eventos para processos derivados.

Isso significa que o projeto nao concentra tudo em controllers ou models. Em vez disso:

- controllers recebem a requisicao e delegam;
- form requests validam entrada;
- services concentram regras de negocio;
- repositories isolam consultas e persistencia;
- events/listeners/jobs lidam com efeitos colaterais e processamento posterior.

### 5.3 Estilo arquitetural do frontend

O frontend usa uma SPA classica baseada em Vue 3, com:

- roteamento no cliente via `vue-router`;
- estado global via Pinia;
- cache e sincronizacao de dados de servidor via TanStack Vue Query;
- componentes reutilizaveis de UI e layout;
- composables para comportamento compartilhado;
- componentes por feature para regras de apresentacao de dominio.

## 6. Stack tecnologica

## 6.1 Frontend

- `Vue 3`: base da interface.
- `TypeScript`: tipagem estatica.
- `Vite 5`: build, dev server e pipeline de frontend.
- `vue-router`: roteamento.
- `Pinia`: estado global.
- `@tanstack/vue-query`: cache e sincronizacao de dados de servidor.
- `@tanstack/vue-virtual`: virtualizacao de listas longas onde aplicavel.
- `Axios`: cliente HTTP.
- `jspdf`: export PDF no cliente onde implementado.
- `PrimeVue`: biblioteca de componentes.
- `@primeuix/themes`: tema visual.
- `primeicons`: icones.
- `apexcharts` e `vue3-apexcharts`: graficos principais (inclui heatmap via ApexCharts).
- `laravel-echo` e `pusher-js`: integracao com broadcasting/Reverb.
- `Zod`: validacao de schemas em pontos do client.
- `Vitest`, `happy-dom`, `@vue/test-utils`: testes.
- `ESLint`, `Prettier`, `vue-tsc`: qualidade e verificacao estatica.

## 6.2 Backend

- `PHP ^8.2`
- `Laravel 11`
- `Laravel Sanctum`: tokens de autenticacao.
- `Laravel Reverb`: servidor e integracao de WebSocket.
- `Laravel Horizon`: supervisao e execucao de filas.
- `PostgreSQL 16`: persistencia principal.
- `Redis 7`: cache, filas, sessao e suporte a realtime.
- `PHPUnit 11`: testes.
- `Larastan/PHPStan`: analise estatica.
- `Laravel Pint`: formatacao.

## 6.3 Infraestrutura

- `Docker` e `Docker Compose`
- `Nginx`
- `pgAdmin` no ambiente de desenvolvimento estendido
- `Mailpit` no ambiente de desenvolvimento estendido
- `GitHub Actions` para CI/CD

## 7. Frontend em detalhe

## 7.1 Papel do frontend

O frontend e a camada de interacao do usuario. Ele nao replica toda a logica de negocio do backend, mas possui responsabilidades importantes:

- gestao da sessao autenticada no navegador;
- renderizacao do dashboard e das views de dominio;
- roteamento e controle de acesso na interface;
- consumo e cache dos dados retornados pela API;
- apresentacao de graficos e indicadores;
- interacoes em tempo real por WebSocket;
- persistencias locais especificas, como goals frontend-only.

## 7.2 Ponto de entrada

Os pontos mais importantes do bootstrap do frontend sao:

- `frontend/index.html`
- `frontend/src/main.ts`
- `frontend/src/App.vue`

Em `index.html`, a aplicacao define o documento HTML base, idioma `pt-BR`, carrega fontes e prepara o mount do app.

Em `main.ts`, a aplicacao:

- cria a instancia Vue;
- registra Pinia;
- registra Vue Query;
- registra o router;
- configura PrimeVue e servicos de UI, como toast e confirm dialog;
- aplica tema inicial com base em `localStorage`;
- importa estilos globais.

Em `App.vue`, a app monta:

- `RouterView`;
- sistema de toast;
- dialogo de confirmacao;
- inicializacao de integracao entre erros de API e notificacoes visuais.

## 7.3 Roteamento

O roteamento esta centralizado em `frontend/src/router/`.

Arquivos importantes:

- `router/index.ts`
- `router/guards.ts`
- `router/routes/*.routes.ts`

### 7.3.1 Estrategia de rotas

O frontend organiza rotas publicas e protegidas:

- publicas: login e registro;
- protegidas: dashboard, sessoes, tecnologias, goals, export, settings, reports, help, profile.

As rotas protegidas sao agrupadas sob o layout principal. Isso permite reaproveitar barra lateral, top bar, shell visual e integracoes globais.

### 7.3.2 Guard de autenticacao

O guard verifica:

- se a rota exige autenticacao;
- se existe token local;
- se o usuario ja foi carregado;
- se deve buscar `me` antes de entrar;
- se o usuario autenticado esta tentando acessar rota guest.

Ha uma preocupacao visivel com evitar chamadas redundantes de `fetchMe`, usando um mecanismo de serializacao para impedir rajadas de requisicoes iguais durante a navegacao inicial.

## 7.4 Gerenciamento de estado

O projeto combina dois mecanismos complementares:

- `Pinia` para estado global de interface e sessao;
- `TanStack Query` para dados remotos e invalidacao/cache.

### 7.4.1 Stores principais

- `auth.store.ts`: token, usuario, login, register, logout, `fetchMe`, persistencia em `localStorage`.
- `ui.store.ts`: tema, sidebar, configuracoes visuais e comportamento responsivo.
- `sessions.store.ts`: dados de sessoes e timer em execucao.
- `analytics.store.ts`: metricas, dashboard, series temporais, heatmap e sinalizacao de recalculo.
- `technologies.store.ts`: lista de tecnologias, busca e CRUD com TTL local.
- `goals.store.ts`: metas do usuario persistidas localmente.
- `notifications.store.ts`: notificacoes em memoria.

### 7.4.2 Uso de Vue Query

Vue Query aparece como camada de sincronizacao seletiva. Em vez de colocar todo o consumo da API dentro dele, o projeto o utiliza de forma focada em pontos de maior valor, como dashboard, lista de sessoes e tecnologias.

Essa estrategia produz um meio-termo:

- Pinia segue util para estado de sessao, UI e composicao local;
- Query assume cache, invalida e lifecycle de dados remotos especificos.

O efeito pratico e positivo, mas requer disciplina para evitar que um mesmo dominio tenha dados em mais de uma fonte e acabe dessincronizado.

## 7.5 Organizacao de componentes

O frontend separa componentes por camada de responsabilidade.

### 7.5.1 `components/layout`

Responsavel pelo shell da aplicacao:

- `AppLayout`
- `AppSidebar`
- `AppTopBar`
- wrappers de pagina

Essa camada cuida de estrutura, navegacao e integracoes globais, como inicializacao do WebSocket no contexto autenticado.

### 7.5.2 `components/ui`

Funciona como um design system local, com wrappers e componentes genericos:

- botoes;
- inputs;
- cards;
- modal;
- tabela base;
- estados vazios;
- skeletons;
- toggles de tema;
- componentes de erro.

Essa abordagem reduz repeticao e ajuda a padronizar interacoes visuais.

### 7.5.3 `components/charts`

Encapsula graficos de linha, barra, pizza, donut e heatmap. Essa camada existe para impedir que cada view precise conhecer detalhes de configuracao de bibliotecas de grafico.

### 7.5.4 `features/*`

As features agrupam componentes, composables e comportamentos ligados ao dominio:

- auth
- dashboard
- sessions
- technologies
- goals
- notifications

Essa estrutura aproxima a interface da linguagem do negocio e facilita evolucao por contexto funcional.

## 7.6 Views e modulos funcionais

As views em `frontend/src/views/` representam telas de navegacao. Entre os modulos visiveis estao:

- autenticacao;
- dashboard;
- sessoes;
- tecnologias;
- goals;
- exportacao;
- configuracoes;
- relatorios;
- perfil;
- ajuda.

O sistema de sessoes inclui, alem da listagem, modos voltados a foco e detalhe por tecnologia. Isso sugere que o frontend foi desenhado nao apenas para CRUD puro, mas para apoiar a rotina de estudo como experiencia de uso.

## 7.7 Cliente HTTP

O cliente central de API esta em `frontend/src/api/client.ts`.

### 7.7.1 Responsabilidades principais

- definir `baseURL` da API;
- injetar token Bearer nas requisicoes;
- tratar `401` globalmente;
- tratar `429` com feedback visual;
- centralizar interpretacao de mensagem de erro.

### 7.7.2 Comportamento em erro

Quando recebe `401`, o cliente:

- limpa a sessao local;
- evita loops em paginas de autenticacao;
- redireciona para login.

Quando recebe `429`, a interface pode exibir toast, melhorando o feedback de rate limit.

### 7.7.3 Organizacao dos endpoints

As rotas da API sao encapsuladas em:

- `api/endpoints.ts`
- `api/queryKeys.ts`
- `api/modules/auth.api.ts`
- `api/modules/sessions.api.ts`
- `api/modules/technologies.api.ts`
- `api/modules/analytics.api.ts`
- `api/modules/goals.api.ts`

Um detalhe importante e que `goals.api.ts` nao aponta para backend; ele implementa persistencia local. Isso confirma que a feature de metas, no estado atual do repositorio, e frontend-only.

## 7.8 Formularios e validacao

O frontend usa uma combinacao de estrategias:

- validacoes manuais em formularios;
- composables utilitarios de validacao;
- `BaseInput` com suporte a exibicao de erro;
- Zod para parse de algumas respostas da API.

Essa estrategia funciona, mas nao representa um modelo unificado para todos os formularios. Em outras palavras, o projeto possui uma base de validacao, porem sem um framework unico dominante para toda a experiencia de forms.

## 7.9 Estilizacao e design system

O frontend usa:

- CSS global em `assets/styles/main.css`;
- tokens em `assets/styles/variables.css`;
- PrimeVue como base de componentes;
- tema Aura com suporte a modo escuro;
- dataset `data-theme` para alternancia light/dark.

Essa combinacao indica um design system hibrido:

- parte vem do PrimeVue;
- parte vem de componentes internos;
- parte vem de tokens CSS locais.

Do ponto de vista de arquitetura visual, isso e positivo, porque o projeto nao depende apenas da aparencia padrao da biblioteca e consegue padronizar identidade visual.

## 7.10 Tempo real

O realtime do frontend e baseado em:

- `laravel-echo`
- `pusher-js`
- configuracao de Reverb
- canal privado por usuario

O composable `useWebSocket` conecta ao canal `dashboard.{userId}` e escuta eventos ligados a:

- atualizacao de metricas;
- inicio de recalc;
- inicio de sessao;
- encerramento de sessao.

Isso permite que o dashboard responda a mudancas originadas no backend sem polling constante como estrategia principal. O projeto, entretanto, preve fallback quando o WebSocket falha, o que melhora resiliencia.

## 7.11 Build, testes e qualidade no frontend

Os scripts principais do `frontend/package.json` cobrem:

- desenvolvimento: `npm run dev`
- build: `npm run build`
- preview: `npm run preview`
- testes: `npm run test`, `npm run test:run`, `npm run test:coverage`
- type-check: `npm run type-check`
- lint: `npm run lint`
- formatacao: `npm run format`

Pontos relevantes:

- o build usa `vue-tsc -b` antes do `vite build`, o que fortalece a verificacao de tipos;
- o lint esta configurado com `--fix`, inclusive no script principal, o que e conveniente localmente, mas menos ideal em CI;
- o projeto possui `vite.config.analyze.ts`, indicando preocupacao com bundle analysis.

## 7.12 Pontos tecnicos de atencao no frontend

- coexistencia de Pinia e Vue Query exige cuidado para evitar dupla fonte de verdade;
- goals continuam locais e nao sincronizados com servidor;
- notificacoes estao em memoria, sem backend dedicado;
- Zod valida apenas alguns fluxos;
- graficos concentram-se em ApexCharts; manter configuracoes de tema alinhadas evita inconsistencia visual;
- composables legados em `src/composables/` ainda expoem `@deprecated` apontando para equivalentes em `features/*`.

## 8. Backend em detalhe

## 8.1 Papel do backend

O backend concentra a logica central do sistema. Ele responde por:

- autenticacao e emissao de tokens;
- CRUD de tecnologias;
- CRUD e controle de sessoes de estudo;
- calculo e entrega de analytics;
- rate limiting;
- health checks;
- broadcasting para atualizacoes em tempo real;
- orquestracao de jobs e listeners;
- persistencia principal do dominio.

## 8.2 Entrada e bootstrap

Os pontos de bootstrap mais importantes sao:

- `backend/public/index.php`
- `backend/bootstrap/app.php`
- `backend/bootstrap/providers.php`

### 8.2.1 `public/index.php`

E o ponto de entrada HTTP da aplicacao Laravel. Ele carrega o framework, verifica estado de manutencao, inicializa o app e entrega a requisicao ao kernel moderno do Laravel 11.

### 8.2.2 `bootstrap/app.php`

Nesse projeto, esse arquivo concentra customizacoes importantes:

- registro de rotas web, api, console e channels;
- configuracao de middleware da API;
- preferencia por renderizacao JSON quando apropriado;
- ajustes de compatibilidade para sinais de processo.

Isso mostra que o bootstrap nao esta apenas no padrao minimo do framework; ele foi usado para consolidar comportamento transversal.

## 8.3 Modularizacao por dominio

A pasta `backend/app/Modules/` mostra um recorte claro por contexto:

- `Auth`
- `StudySessions`
- `Technologies`
- `Analytics`

Cada modulo possui subpastas como:

- `Services`
- `Repositories`
- `Contracts`
- `DTOs`

Essa divisao e importante porque evita que todo o sistema fique orientado apenas a controllers e models. O codigo expressa o dominio por modulos, o que ajuda escalabilidade de manutencao.

## 8.4 Controllers, requests e resources

As camadas HTTP principais ficam em `backend/app/Http/`.

### 8.4.1 Controllers

Os controllers versionados em `Controllers/V1` organizam a superficie publica da API. Eles sao finos, o que indica uma decisao consciente de evitar concentrar regra de negocio na camada HTTP.

Principais controllers em `Controllers/V1`:

- `AuthController`
- `StudySessionController`
- `TechnologyController`
- `AnalyticsController`

O `HealthController` fica em `App\Http\Controllers\` (fora de `V1`) e atende `GET /api/health` em `routes/api.php`. O Laravel tambem expoe `GET /up` (configurado em `bootstrap/app.php`) como health minimo do framework.

### 8.4.2 Form Requests

A validacao de entrada fica em `Http/Requests`, seguindo o padrao Laravel. O beneficio disso e separar:

- parse e validacao de input;
- regras de negocio;
- serializacao de resposta.

### 8.4.3 API Resources

Resources ajudam a padronizar a estrutura da resposta. Alem disso, o projeto usa um trait de resposta para formar contratos JSON consistentes de sucesso e erro.

## 8.5 Middleware

Middlewares customizados observados:

- `EnsureJsonResponse`
- `SetUserTimezone`
- `LogApiRequests`
- `SlidingWindowRateLimit` (alias `throttle.sliding` em `bootstrap/app.php`, usado em rotas de mutacao de sessoes; em falha do script Lua, comportamento controlado por `services.rate_limit.fail_open` — ver middleware e `DOCUMENTACAO_TECNICA_LUA.md`)

### 8.5.1 `EnsureJsonResponse`

Forca ou favorece comportamento de API JSON, reduzindo risco de respostas HTML inesperadas em fluxos de cliente SPA.

### 8.5.2 `SetUserTimezone`

Ajusta a timezone da aplicacao por usuario autenticado quando a informacao esta disponivel. Isso e uma preocupacao de UX relevante, pois evita inconsistencias de horarios em serializacao e leitura de dados.

### 8.5.3 `LogApiRequests`

Registra informacoes de requisicao e resposta, incluindo duracao. E um componente importante de observabilidade basica.

## 8.6 Rotas

As rotas principais ficam em:

- `backend/routes/api.php`
- `backend/routes/web.php`
- `backend/routes/channels.php`
- `backend/routes/console.php`

### 8.6.1 API versionada e health

As rotas de negocio estao sob `v1`, resultando em `GET/POST/... /api/v1/...` (prefixo `api` do Laravel + grupo `v1` em `routes/api.php`).

O endpoint de health da aplicacao esta **fora** do grupo `v1`: `GET /api/health` (`HealthController`).

Em `routes/web.php` existe tambem `GET /health` apontando para o mesmo controller (util quando o front da API responde na raiz do host). Alem disso, `GET /up` e o health check padrao registrado no bootstrap do Laravel 11.

Grupos funcionais em `/api/v1`:

- autenticacao;
- tecnologias;
- sessoes de estudo;
- analytics;

Canais de broadcasting autenticados permanecem em `routes/channels.php`.

### 8.6.2 Canais privados

O canal `dashboard.{userId}` garante que o usuario so possa assinar o proprio fluxo. Isso e coerente com o desenho do dashboard em tempo real por usuario.

### 8.6.3 Scheduler

`routes/console.php` define tarefas agendadas, incluindo geracao de resumo semanal e limpeza de filas antigas. Essa escolha centraliza automacoes programadas dentro do backend em vez de depender apenas de orquestracao externa.

## 8.7 Services

Os services representam o coracao da logica de negocio.

### 8.7.1 AuthService e TokenService

O `AuthService` concentra registro, login, usuario atual, atualizacao de perfil e troca de senha. A revogacao e blacklist de tokens Sanctum foram centralizadas no `TokenService` (`App\Modules\Auth\Services\TokenService`), usado em logout, revogacao em massa, login e troca de senha — inclusive alinhado a validacao de tokens no edge (OpenResty), conforme `DOCUMENTACAO_TECNICA_LUA.md`.

### 8.7.2 StudySessionService

Controla a vida util das sessoes:

- criacao;
- listagem;
- detalhe;
- atualizacao;
- encerramento;
- exclusao;
- verificacao de propriedade do recurso.

Tambem e o ponto em que sessoes disparam eventos que abastecem todo o pipeline de metricas e atualizacoes.

### 8.7.3 TechnologyService

Cuida do CRUD de tecnologias do usuario. Em vez de remover fisicamente, aparenta adotar uma logica de desativacao, o que preserva historico e reduz risco de quebrar referencias.

### 8.7.4 AnalyticsService

Entrega os dados consolidados para:

- dashboard;
- metricas agregadas;
- series temporais;
- comparativos semanais;
- heatmap;
- exportacao;
- recalc manual.

Esse modulo representa a camada analitica do sistema e trabalha em parceria com cache, repositorios, jobs e tabelas derivadas.

## 8.8 Repositories e contratos

O backend usa interfaces e implementacoes concretas para acesso a dados. A injecao das dependencias e centralizada em `RepositoryServiceProvider`.

Beneficios dessa abordagem:

- reduz acoplamento direto entre service e Eloquent;
- facilita substituicao de implementacao;
- melhora testabilidade;
- organiza queries em um lugar consistente.

O custo e maior numero de arquivos e uma camada a mais de abstracao, mas o projeto parece assumir esse custo em troca de clareza estrutural.

## 8.9 Models e camada de persistencia

Models principais:

- `User`
- `Technology`
- `StudySession`
- `BaseModel`

Caracteristicas observadas:

- uso de UUID como chave primaria;
- serializacao padronizada de datas;
- relacionamentos de usuario com tecnologias e sessoes;
- uso de traits de apoio.

Essas escolhas favorecem interoperabilidade e ajudam a evitar exposicao de ids incrementais.

## 8.10 Banco de dados

## 8.10.1 Tecnologia e estrategia

O sistema usa PostgreSQL 16 com uma ideia arquitetural importante: separacao por schema.

### 8.10.2 Schema `public`

Concentra dados transacionais principais, como:

- usuarios;
- tecnologias;
- sessoes de estudo;
- tokens pessoais;
- estruturas ligadas ao uso diario da aplicacao.

### 8.10.3 Schema `analytics`

Concentra dados derivados e agregados, como:

- metricas de usuario;
- metricas por tecnologia;
- minutos diarios;
- resumos semanais.

Essa separacao e valiosa porque isola melhor o que e transacional do que e analitico. Na pratica, isso aproxima o desenho de uma estrategia inspirada em CQRS, mesmo sem adotar CQRS formal completo.

### 8.10.4 Migrations

As migrations nao se resumem a um unico diretorio. O projeto carrega conjuntos separados, incluindo:

- migrations padrao;
- migrations transacionais;
- migrations de analytics.

Isso reforca a ideia de organizacao por tipo de dado e responsabilidade.

### 8.10.5 Regras de integridade

Pelo que o codigo indica, o banco tambem participa da garantia de consistencia, com funcoes, triggers, indices e restricoes. Um exemplo importante e a protecao contra mais de uma sessao ativa simultanea para o mesmo usuario.

## 8.11 Autenticacao e autorizacao

## 8.11.1 Autenticacao

O sistema usa `Laravel Sanctum` com tokens pessoais. As rotas protegidas exigem `auth:sanctum`.

Fluxos principais:

- registro;
- login;
- usuario atual;
- logout;
- revogacao de tokens;
- alteracao de senha.

## 8.11.2 Autorizacao

Nao ha evidencia forte de uso extensivo de `Policies` como mecanismo principal. Em vez disso, parte significativa da autorizacao ocorre de forma imperativa em services ou consultas filtradas por `user_id`.

Isso funciona, mas gera dois efeitos de manutencao:

- a logica de ownership fica espalhada;
- a semantica de erro pode variar entre modulos.

De fato, um ponto observado e a diferenca entre respostas `403` e `404` dependendo do tipo de recurso e do modulo.

## 8.12 Eventos, listeners e jobs

Este e um dos aspectos mais interessantes do projeto.

### 8.12.1 Eventos

Eventos representam mudancas de estado importantes, especialmente em sessoes de estudo e metricas.

### 8.12.2 Listeners

Listeners reagem a esses eventos para:

- invalidar cache;
- disparar recalc de metricas;
- acionar broadcast;
- acoplar fluxos secundarios sem poluir o service principal.

### 8.12.3 Jobs

Jobs observados incluem:

- `RecalculateMetricsJob`
- `GenerateWeeklySummaryJob`

O `RecalculateMetricsJob` e particularmente central, porque traduz alteracoes transacionais em atualizacao das tabelas analiticas. O uso de delay curto sugere tentativa de agrupar mudancas proximas e reduzir recalc em excesso.

### 8.12.4 Valor arquitetural dessa camada

Essa arquitetura produz beneficios claros:

- menor tempo de resposta para operacoes do usuario;
- desacoplamento entre write path e processamento derivado;
- possibilidade de evoluir analytics sem reescrever fluxo HTTP principal;
- melhor suporte a tempo real.

## 8.13 Cache

O projeto usa Redis com cache taggeado. Isso e relevante porque:

- dashboards e metricas costumam ser caros para recalcular;
- invalidacao por usuario e mais simples quando as chaves sao agrupadas por tags;
- a API pode responder com menor custo em leituras repetidas.

Esse desenho indica preocupacao com performance desde cedo, o que faz sentido em um dominio com agregacoes e visualizacoes frequentes.

## 8.14 Health checks e observabilidade

O `HealthController` testa dependencias como:

- banco;
- Redis;
- fila;
- endpoint ou conectividade de WebSocket.

Esse tipo de endpoint ajuda em:

- diagnostico operacional;
- healthchecks de container;
- integracao com proxy ou orquestradores.

Somado ao middleware de log e aos logs de erro em jobs, o projeto possui uma camada inicial de observabilidade operacional.

## 8.15 Tratamento de excecoes

O projeto possui um `Handler` customizado para respostas JSON. Casos tratados incluem:

- erro de validacao;
- autenticacao;
- autorizacao;
- model not found;
- concorrencia de sessao;
- API exception customizada;
- certos erros de banco;
- rate limit;
- fallback para erro interno.

Isso melhora a previsibilidade do contrato de resposta da API e ajuda o frontend a interpretar falhas de forma mais consistente.

## 8.16 Rate limiting

Limitadores nomeados definidos em `AppServiceProvider` (`RateLimiter::for`):

| Nome | Comportamento (referencia) |
|------|----------------------------|
| `login` | 3 req/min por IP |
| `register` | 5 req/min por IP |
| `sensitive` | 5 req/min por usuario autenticado (ou IP) |
| `search` | 120 req/min por usuario (ou IP) |
| `recalculate` | 2 req/min por usuario (ou IP) |
| `export` | 30 req/min por usuario (ou IP) |
| `health` | 300 req/min por IP |

Rotas autenticadas de leitura usam `throttle:60,1` (60 req/min). O grupo de escrita generico em `api.php` usa `throttle:30,1` (30 req/min) onde aplicavel.

Rotas de sessao de estudo (`start`, `end`, `store`, `update`, `destroy`) usam adicionalmente `throttle.sliding` (middleware `SlidingWindowRateLimit`) com limites por rota definidos em `routes/api.php`, apoiados em `redis-scripts/sliding_window.lua`.

A fonte de verdade e `backend/app/Providers/AppServiceProvider.php` e `backend/routes/api.php`.

## 8.17 Testes e qualidade no backend

O backend conta com:

- PHPUnit;
- Larastan/PHPStan;
- Pint.

Cobertura observada (estrutura em `backend/tests`):

- Feature: autenticacao, sessoes, analytics, seguranca (rate limit, injecao), contratos JSON (`Feature/Contract`), health, Lua (dedup, sliding window, streak), excecoes;
- Unit: eventos, listeners, jobs, middleware;
- PHPUnit + Larastan + Pint no CI (`backend-ci.yml`).

Essa distribuicao sugere foco em fluxos principais do dominio, embora nao seja possivel afirmar cobertura percentual total apenas pela estrutura.

## 8.18 Pontos tecnicos de atencao no backend

- autorizacao distribuida entre services/queries em vez de centralizada em policies;
- semantica inconsistente entre `403` e `404` para ownership;
- mais de um endpoint de health (`/up`, `/api/health`, `/health` em web) — util para probes distintos, mas exige clareza em runbooks;
- painel Horizon dependente de contexto `web`, o que pode exigir fluxo especifico de acesso;
- necessidade de garantir que arquivos sensiveis e artefatos locais nao sejam versionados indevidamente.

## 9. Infraestrutura e operacao

## 9.1 Docker Compose

O arquivo `docker-compose.yml` sobe a stack principal com os servicos:

- `nginx`
- `php-fpm`
- `reverb`
- `horizon`
- `scheduler`
- `node`
- `postgres`
- `redis`

### 9.1.1 Nginx

Atua como reverse proxy e ponto publico de entrada. Faz roteamento para:

- API Laravel;
- frontend estatico;
- WebSocket;
- Horizon;
- health endpoint.

Tambem define gzip e cabecalhos de seguranca, o que mostra preocupacao inicial com entrega e exposicao externa.

### 9.1.2 PHP-FPM

Container da aplicacao Laravel para atendimento HTTP via Nginx.

### 9.1.3 Reverb

Processo dedicado para o servidor de WebSocket.

### 9.1.4 Horizon

Container dedicado a processamento e supervisao de filas.

### 9.1.5 Scheduler

Executa `schedule:work`, permitindo que tarefas programadas do Laravel sejam processadas continuamente.

### 9.1.6 Node

Container de desenvolvimento do frontend. Ele expõe a porta 5173 e roda `npm install` seguido de `npm run dev`. E importante notar que esse container e claramente orientado a ambiente de desenvolvimento e nao a entrega final estavel do frontend em producao.

### 9.1.7 Postgres

Banco principal, sem exposicao de porta publica no compose base, o que melhora seguranca local por padrao.

### 9.1.8 Redis

Servico de cache e infraestrutura, tambem sem exposicao publica por padrao.

## 9.2 Ambiente dev expandido

`docker-compose.dev.yml` adiciona ferramentas auxiliares, como:

- `pgAdmin`
- `Mailpit`

Isso facilita depuracao e operacao em ambiente local.

## 9.3 Makefile

O `Makefile` encapsula tarefas comuns:

- `setup`
- `dev`
- `stop`
- `build`
- `shell-php`
- `shell-vue`
- `test`
- `migrate`
- `seed`
- `fresh`
- `horizon`
- `pint`
- `lint`
- `logs`

Isso e importante porque padroniza comandos e reduz dependencia de memoria operacional do desenvolvedor.

## 9.4 CI/CD

O projeto possui tres workflows principais:

### 9.4.1 `backend-ci.yml`

Executa instalacao, migracoes, testes, Pint e PHPStan.

### 9.4.2 `frontend-ci.yml`

Executa instalacao, type-check, testes, lint e build.

### 9.4.3 `deploy.yml`

Constrói imagens Docker para backend e frontend e as publica, com estrutura preparada para evolucao de deploy automatizado.

## 9.5 Hooks de git e padrao de commits

Em `.husky` e `commitlint.config.js`, o repositorio padroniza:

- validacao de mensagem de commit;
- execucao de checagens antes do commit.

No pre-commit, o fluxo mistura verificacao de backend e frontend. Isso ajuda a impedir commits com problemas triviais de formato ou consistencia.

## 10. Fluxos importantes do sistema

## 10.1 Fluxo de autenticacao

1. O usuario envia credenciais pela SPA.
2. O frontend chama o endpoint de login.
3. O backend valida e gera token Sanctum.
4. O frontend persiste token e dados basicos do usuario.
5. Guards passam a liberar rotas autenticadas.
6. O cliente HTTP adiciona Bearer token nas proximas requisicoes.

## 10.2 Fluxo de criacao/encerramento de sessao

1. O usuario cria ou inicia uma sessao pelo frontend.
2. O backend persiste a sessao.
3. O modulo de sessoes emite evento de dominio.
4. Listeners invalidam cache e agendam recalc de metricas.
5. O job recalcula agregados.
6. O backend publica evento de metrics atualizadas.
7. O frontend recebe o evento e atualiza dashboard/estado visivel.

## 10.3 Fluxo de analytics

1. O frontend solicita dashboard ou outros recortes analiticos.
2. O backend consulta cache ou repositorio analitico.
3. Se necessario, dados agregados sao lidos do schema `analytics`.
4. A resposta retorna com payload consolidado para consumo da UI.

## 10.4 Fluxo de exportacao

1. O usuario solicita export com periodo.
2. O backend consulta recortes analiticos e retorna JSON exportavel.
3. O frontend oferece a experiencia de download/consumo.

## 11. Modelo conceitual de dados

Mesmo sem listar todas as colunas e constraints do banco, o dominio central pode ser entendido por essas entidades:

### 11.1 Usuario

Representa o dono da conta e da linha do tempo de estudos.

Relacionamentos aparentes:

- um usuario possui varias tecnologias;
- um usuario possui varias sessoes;
- um usuario possui metricas derivadas;
- um usuario pode possuir varios tokens de acesso.

### 11.2 Tecnologia

Representa um eixo de categorizacao do estudo, como linguagem, framework, ferramenta ou tema. E usada tanto no CRUD do usuario quanto na composicao de metricas por tecnologia.

### 11.3 Sessao de estudo

Representa uma unidade de tempo estudado. Pode estar ativa ou encerrada, se associa a um usuario e a uma tecnologia, e serve como evento transacional base para analytics.

### 11.4 Metricas derivadas

Representam visoes consolidadas do uso:

- total por usuario;
- distribuicao por tecnologia;
- minutos diarios;
- resumo semanal;
- series temporais e heatmaps.

Essas estruturas nao substituem as sessoes; elas existem para acelerar leitura, dashboard e relatorios.

## 12. Seguranca, resiliencia e operacao

## 12.1 Pontos positivos observados

- autenticacao por token no backend;
- canais privados para realtime;
- Redis e Postgres nao expostos publicamente no compose base;
- tratamento padronizado de excecoes JSON;
- rate limiting por grupo funcional;
- logs de API e de jobs;
- healthcheck para componentes criticos;
- separacao de cargas sincrona e assincrona.

## 12.2 Pontos que merecem endurecimento ou revisao

- consolidar modelo de autorizacao, preferencialmente com semantica uniforme;
- revisar a estrategia de acesso ao Horizon em diferentes ambientes;
- avaliar se goals devem continuar frontend-only;
- conferir se links do README apontam para caminhos atuais (indice em `docs/README.md`).

## 13. Divergencias e lacunas documentais observadas

Pontos a manter sincronizados entre codigo e texto:

- goals como feature do produto com persistencia apenas no frontend (ver `docs/operations/GOALS-FRONTEND-ONLY.md`);
- documentacao dispersa em `docs/`, READMEs por pacote e `frontend/docs/` — o indice central e `docs/README.md`.

Este arquivo consolida a visao tecnica; detalhes de Lua, borda HTTP e Postgres estao em `DOCUMENTACAO_TECNICA_LUA.md` na mesma pasta.

## 14. Avaliacao tecnica geral do projeto

Em termos de maturidade estrutural, o projeto mostra um nivel acima do basico para um portfolio ou produto em crescimento. Os sinais mais fortes disso sao:

- separacao clara entre dominios;
- uso de camada de services e repositories;
- analytics desacoplado do write path principal;
- uso de cache com invalidacao orientada a usuario;
- comunicacao em tempo real por eventos;
- stack de desenvolvimento conteinerizada;
- CI para frontend e backend;
- preocupacao com tipagem, lint e testes.

Ao mesmo tempo, ainda ha sinais tipicos de um sistema em evolucao:

- inconsistencias documentais;
- coexistencia de abordagens em algumas camadas do frontend;
- autorizacao ainda nao totalmente centralizada;
- feature de goals ainda local;
- alguns componentes de infra e deploy com carater de base preparada, mas nao necessariamente finalizada para operacao produtiva completa.

## 15. Recomendacoes de evolucao documental

Como proximos passos de documentacao, faria sentido criar ou consolidar:

- um diagrama de arquitetura de contexto e containers;
- um documento de modelo de dados com tabelas e relacoes;
- um catalogo de eventos do sistema;
- um guia de deploy/operacao realmente sincronizado com o repositorio atual;
- uma matriz de ownership e autorizacao por endpoint;
- uma especificacao de contratos da API baseada no comportamento real.

## 16. Conclusao

`StudyTrack Pro` e um sistema full-stack bem estruturado, com separacao consistente entre interface, API, persistencia, jobs e realtime. O projeto foi desenhado para registrar estudo, derivar metricas e apresentar visualizacoes ricas, sustentando essa experiencia com uma base tecnica moderna: Vue 3 no frontend, Laravel 11 no backend, PostgreSQL para dados, Redis para infraestrutura de suporte, e Docker para padronizacao local.

O valor tecnico mais evidente do projeto esta na combinacao entre:

- dominio claro;
- backend modularizado;
- pipeline de analytics desacoplado;
- frontend organizado por features;
- suporte a atualizacao em tempo real;
- preocupacoes reais com qualidade, observabilidade e operacao.

Como documento de referencia, este arquivo deve ser mantido alinhado sempre que houver alteracao relevante em arquitetura, infra, modelo de dados, contratos de API ou estrategia de execucao.

## 17. Documento complementar da integracao Lua

As alteracoes recentes de Redis Lua, OpenResty e PL/Lua foram documentadas separadamente em:

- `docs/technical/DOCUMENTACAO_TECNICA_LUA.md`

Esse arquivo complementar detalha:

- scripts Lua adicionados;
- integracoes Laravel e Redis;
- comportamento do edge em OpenResty;
- trigger PL/Lua no PostgreSQL;
- ajustes de Docker e compose;
- testes e validacoes executadas;
- cuidados de seguranca e riscos residuais.
