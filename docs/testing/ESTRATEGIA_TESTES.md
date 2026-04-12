# Estrategia Completa de Testes — StudyTrack Pro

## Sumario

1. [Visao geral da estrategia](#1-visao-geral-da-estrategia)
2. [Mapeamento das areas do sistema](#2-mapeamento-das-areas-do-sistema)
3. [Estrategia de testes por camada](#3-estrategia-de-testes-por-camada)
4. [Cobertura funcional detalhada](#4-cobertura-funcional-detalhada)
5. [Cobertura de seguranca](#5-cobertura-de-seguranca)
6. [Cobertura de integracao e consistencia](#6-cobertura-de-integracao-e-consistencia)
7. [Cobertura de qualidade e regressao](#7-cobertura-de-qualidade-e-regressao)
8. [Matriz de cobertura por modulo](#8-matriz-de-cobertura-por-modulo)
9. [Priorizacao](#9-priorizacao)
10. [Sugestao pratica de implementacao](#10-sugestao-pratica-de-implementacao)
11. [Plano de automacao](#11-plano-de-automacao)
12. [Plano de execucao no CI/CD](#12-plano-de-execucao-no-cicd)
13. [Riscos nao cobertos](#13-riscos-nao-cobertos)
14. [Recomendacoes finais](#14-recomendacoes-finais)

---

## 1. Visao geral da estrategia

### 1.1 Principios

A estrategia de testes do StudyTrack Pro segue a piramide de testes adaptada para uma aplicacao full-stack event-driven:

```
              /  E2E  \             <- poucos, lentos, alto valor de confianca
             / Contract \           <- validam fronteira API/frontend
            / Integracao  \         <- fluxos de negocio ponta a ponta no backend
           /   Unitarios    \       <- base larga, rapidos, isolados
```

O foco central e **garantir que os fluxos criticos do usuario final funcionem de forma confiavel**, ao mesmo tempo que se detectam regressoes rapido e se protegem fronteiras de seguranca.

### 1.2 Escopo

| Dimensao | Cobertura |
|---|---|
| Backend (Laravel) | Unitarios, integracao, contrato, seguranca, resiliencia |
| Frontend (Vue) | Unitarios, componente, store, composable, snapshot |
| Integracao full-stack | E2E, contrato de API, consistencia de tipos |
| Banco de dados | Migrations, triggers, constraints, schema analytics |
| Cache (Redis) | Invalidacao, TTL, stampede, tags |
| Filas/Jobs | Execucao, retry, idempotencia, dedup Lua |
| WebSocket/Realtime | Autorizacao de canal, broadcast, fallback |
| Infraestrutura | Health check, Docker, Nginx routing |
| UX critica | Fluxos de login, sessao de estudo, dashboard |

### 1.3 Meta de cobertura

| Camada | Meta minima | Meta ideal |
|---|---|---|
| Backend — services | 90% | 95% |
| Backend — controllers (feature) | 85% | 90% |
| Backend — jobs/listeners | 80% | 90% |
| Frontend — stores | 85% | 90% |
| Frontend — composables | 80% | 90% |
| Frontend — componentes criticos | 70% | 80% |
| E2E — fluxos criticos | 100% dos 8 fluxos principais | — |

> **Nota:** As metas da tabela acima sao **objetivos de engenharia** (roadmap de qualidade). O **CI atual do repositorio nao falha** pull requests por cobertura minima nem exige MSW/Playwright; ver a secção **1.4** abaixo.

### 1.4 Estado atual do CI (repositorio)

Ficheiros reais: [.github/workflows/backend-ci.yml](../../.github/workflows/backend-ci.yml) e [.github/workflows/frontend-ci.yml](../../.github/workflows/frontend-ci.yml).

**Backend (job `backend`, Ubuntu):**

- Servicos: PostgreSQL 16, Redis 7 (portas no runner).
- PHP **8.4** no runner (o `composer.json` exige `^8.2`; localmente pode usar 8.2+).
- `composer install` em `backend/`, `cp .env.example .env`, `key:generate`, `migrate --force`.
- `php artisan test --coverage-clover=coverage.xml` — gera cobertura e **falha se testes falharem**; **nao** ha gate configurado para minimo percentual.
- `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse`.

**Frontend (job `frontend`, Ubuntu):**

- Node **20.19**, `npm ci` em `frontend/`.
- `npm run type-check`, `npm run test:run`, `npm run lint`, `npm run build`.
- **Sem** relatorio de cobertura obrigatorio no CI nem limiar percentual.

**Nao presentes no CI hoje (planeamento / secao 12 conceitual):** MSW, Playwright/Cypress, job dedicado de contrato, E2E contra Docker Compose, falha automatica por cobertura < 80%/70%.

---

## 2. Mapeamento das areas do sistema

### 2.1 Frontend

| Area | Arquivos-chave | Risco |
|---|---|---|
| Auth store + API | `auth.store.ts`, `auth.api.ts`, `LoginForm.vue`, `RegisterForm.vue` | Perda de acesso, loop 401 |
| Router + Guards | `guards.ts`, `router/index.ts`, 10 arquivos de rotas | Acesso indevido, redirect infinito |
| Sessions (CRUD + timer) | `sessions.store.ts`, `sessions.api.ts`, `SessionsView.vue`, `SessionFocusView.vue` | Timer incorreto, sessao nao encerra |
| Technologies | `technologies.store.ts`, `technologies.api.ts`, `TechnologiesView.vue` | CRUD quebrado, dessinc |
| Analytics/Dashboard | `analytics.store.ts`, `analytics.api.ts`, `DashboardView.vue`, charts/* | Dashboard vazio, dados errados |
| Goals (local-only) | `goals.store.ts`, `goals.api.ts` (localStorage) | Perda de dados, sem sync |
| WebSocket | `useWebSocket.ts`, `websocket.types.ts` | Dashboard nao atualiza |
| HTTP client | `client.ts` | Interceptors quebrados, 401 loop |
| UI components | 32 componentes em `components/ui/` | Regressao visual |
| Charts | 5 componentes em `components/charts/` | Grafico vazio, config errada |

### 2.2 Backend

| Area | Arquivos-chave | Risco |
|---|---|---|
| Auth (register/login/logout/password/tokens) | `AuthController`, `AuthService`, `TokenService` | Acesso indevido, token leak |
| Study Sessions (CRUD + start/end/active) | `StudySessionController`, `StudySessionService`, repository | Sessao duplicada, dados perdidos |
| Technologies (CRUD + search + deactivate) | `TechnologyController`, `TechnologyService`, repository | CRUD inconsistente |
| Analytics (dashboard/metrics/heatmap/export/recalc) | `AnalyticsController`, `AnalyticsService`, repository, `MetricsAggregator` | Metricas erradas, cache stale |
| Events/Listeners | 7 eventos, 7 listeners | Pipeline quebrado, metricas desatualizadas |
| Jobs | `RecalculateMetricsJob`, `GenerateWeeklySummaryJob` | Recalc falha, metricas stale |
| Middleware | `EnsureJsonResponse`, `SetUserTimezone`, `LogApiRequests`, `SlidingWindowRateLimit` | Rate limit falho, timezone errada |
| Exception Handler | `Handler.php` | Resposta inconsistente, leak de stack trace |
| Health Check | `HealthController` | Falso positivo, servico degradado |
| Lua Scripts | `sliding_window.lua`, `streak_update.lua`, `job_dedup.lua` | Rate limit bypass, streak incorreto |

### 2.3 Banco de dados

| Area | Risco |
|---|---|
| Schema `public` — tabelas transacionais | Constraint violada, migration falha |
| Schema `analytics` — tabelas derivadas | Dessinc com dados transacionais |
| Triggers (sessao ativa unica) | Bypass de concorrencia |
| Funcoes e indices (GIN, composite) | Performance degradada |
| UUIDs como PK | Colisao teorica, formato invalido |

### 2.4 Cache (Redis)

| Area | Risco |
|---|---|
| Cache taggeado (`analytics`, `sessions`, `user:{id}`) | Tags nao invalidadas |
| Lock de dashboard (stampede prevention) | Deadlock, lock eternizado |
| TTLs (5min dashboard, 15min time-series, 1h heatmap) | Dados stale prolongado |

### 2.5 Filas/Jobs

| Area | Risco |
|---|---|
| Queue `metrics` | Job nao processado |
| `ShouldBeUnique` (RecalculateMetricsJob) | Dedup falha, recalc duplicado |
| Retry com backoff (30s, 60s, 120s) | Job abandonado, retry infinito |
| Dedup via Lua (`job_dedup.lua`) | Lua indisponivel, fail-open sem controle |

### 2.6 WebSocket/Realtime

| Area | Risco |
|---|---|
| Canal privado `dashboard.{userId}` | Leak cross-user |
| Eventos: `.metrics.updated`, `.metrics.recalculating`, `.session.started`, `.session.ended` | Evento nao chega, payload incorreto |
| Fallback timer (45s) | Spinner eterno se WebSocket cai |
| Reconexao | Dashboard congela |

### 2.7 Infraestrutura

| Area | Risco |
|---|---|
| Docker Compose (8 servicos) | Container nao sobe, porta conflitante |
| Nginx routing (API, frontend, WebSocket, Horizon) | Rota errada, 502 |
| Health endpoint | Degraded nao detectado |
| Migrations em 3 diretorios | Migration executada fora de ordem |

---

## 3. Estrategia de testes por camada

### 3.1 Testes unitarios

**Objetivo:** Validar unidades isoladas de logica sem dependencias externas.

**Backend (PHPUnit):**

| O que testar | Justificativa |
|---|---|
| `AuthService.register()` — hash de senha, criacao via repository | Logica de registro sem HTTP |
| `AuthService.login()` — credenciais validas/invalidas, revogacao de tokens antigos | Fluxo critico de autenticacao |
| `AuthService.changePassword()` — hash check, revogacao, retorno false | Seguranca de troca de senha |
| `StudySessionService.findForUser()` — ownership check, ModelNotFoundException | Autorizacao a nivel de servico |
| `StudySessionService.create()` — DTO mapping, evento disparado | Corretude de criacao |
| `StudySessionService.delete()` — evento com dados corretos antes do delete | Ordem de operacoes |
| `TechnologyService.deactivate()` — soft delete, nao hard delete | Preservacao de historico |
| `AnalyticsService.buildDashboardData()` — composicao do payload | Estrutura de resposta |
| `MetricsAggregator.recalculateUserMetrics()` — calculo de totais, streaks | Corretude matematica |
| `MetricsAggregator.recalculateTechnologyMetrics()` — agrupamento por tech | Agregacao correta |
| `MetricsAggregator.recalculateDailyMinutes()` — timezone, agrupamento diario | Timezone afeta resultado |
| `DispatchMetricsRecalculation.handle()` — filtro de campos relevantes em update | Evita recalc desnecessario |
| `InvalidateSessionCache.handle()` — flush correto de tags | Cache limpo apos CRUD |
| `RecalculateMetricsJob` — transacao, flush, broadcast | Pipeline completo do job |
| `TokenService.revoke()` / `revokeMany()` — contagem, cleanup | Gestao de tokens |
| DTOs — construcao, defaults, toArray | Contrato de dados interno |
| `StudySessionFilterDTO.fromArray()` — parsing de query params | Filtros da listagem |

**Frontend (Vitest):**

| O que testar | Justificativa |
|---|---|
| `auth.store` — login persiste token em localStorage, logout limpa, clearSessionLocally | Estado de autenticacao |
| `auth.store` — register persiste user + token | Registro consistente |
| `sessions.store` — setActiveSession, clearActiveSession | Timer depende disso |
| `analytics.store` — updateFromWebSocket, setRecalculating | Dashboard reativo |
| `technologies.store` — CRUD + TTL local | Lista de tecnologias |
| `goals.store` — persistencia em localStorage, CRUD local | Feature frontend-only |
| `useSessionTimer` — start, tick, stop, elapsed | Timer e feature critica |
| `useFormValidation` — regras de email, required, min length | Validacao de formularios |
| `useDebounce` — delay, cancel | Search depende disso |
| `usePagination` — page, perPage, total, hasNext | Listagem paginada |
| `useSort` — asc/desc, multi-campo | Ordenacao |
| `useWebSocket` — connect, disconnect, isConnected | Ciclo de vida WS |
| `useAsync` — loading, error, data, execute | Wrapper de async |
| `getApiErrorMessage()` — formatos variados de erro | Tratamento de erro |
| `apiClient` interceptors — injeta token, nao injeta sem token | Headers corretos |
| `dateUtils` — formatacao, parse, diffInMinutes | Calculo de tempo |
| `formatters` — duracao, porcentagem, numeros | Exibicao no dashboard |
| `validators.extended` — regras customizadas | Formularios |

### 3.2 Testes de integracao

**Objetivo:** Validar fluxos de negocio completos envolvendo multiplas camadas (controller -> service -> repository -> banco).

**Backend (PHPUnit com RefreshDatabase):**

| Cenario | Camadas envolvidas |
|---|---|
| Registro + login + me + logout | HTTP -> AuthController -> AuthService -> Sanctum -> DB |
| CRUD completo de tecnologia (create, list, show, update, deactivate) | HTTP -> TechnologyController -> TechnologyService -> Repository -> DB |
| CRUD completo de sessao (store manual, list, show, update, delete) | HTTP -> StudySessionController -> StudySessionService -> Repository -> DB -> Events |
| Start sessao -> active -> end | HTTP -> Controller -> Service -> DB -> Events (criacao + atualizacao) |
| Start 2a sessao -> 409 Concurrent | HTTP -> Controller -> Service -> Exception -> Handler -> 409 |
| Store sessao -> evento -> listener -> despacho de job | HTTP -> Service -> Event -> DispatchMetricsRecalculation -> Queue assertion |
| Delete sessao -> evento -> invalidacao de cache + recalc | HTTP -> Service -> Event -> InvalidateSessionCache + DispatchMetricsRecalculation |
| Dashboard com cache miss -> hit -> invalidacao -> miss | HTTP -> AnalyticsService -> Cache -> Repository |
| Export com range valido / invalido | HTTP -> AnalyticsController -> AnalyticsService -> Repository |
| Recalculate -> 202 + job dispatched | HTTP -> AnalyticsController -> AnalyticsService -> Queue |
| Health check com todos servicos OK | HTTP -> HealthController -> DB::connection + Redis::ping |
| Health check com Redis down | HTTP -> HealthController -> 503 degraded |
| Change password -> revoga tokens | HTTP -> AuthController -> AuthService -> DB |
| Revoke all tokens | HTTP -> AuthController -> TokenService -> DB |
| Listagem de sessoes com filtros (technology_id, periodo, paginacao) | HTTP -> Controller -> Service -> Repository -> DB queries |
| Search de tecnologias com query e limit | HTTP -> Controller -> Service -> Repository -> DB (GIN index) |

**Frontend (Vitest + vue-test-utils):**

| Cenario | Camadas envolvidas |
|---|---|
| LoginForm submete -> auth.store.login -> mock API -> redirect | Componente -> Store -> API mock -> Router |
| RegisterForm submete -> store.register -> persiste -> redirect | Componente -> Store -> API mock -> Router |
| SessionsView monta -> carrega lista -> exibe -> paginacao | Componente -> Vue Query -> API mock -> Renderizacao |
| DashboardView monta -> carrega analytics -> renderiza KPIs | Componente -> Store -> API mock -> Charts |
| TechnologiesView -> cria tecnologia -> lista atualiza | Componente -> Store -> API mock -> Reatividade |
| GoalsView -> cria goal -> persiste localStorage -> exibe | Componente -> Store -> localStorage |
| Guard de rota: sem token -> redirect login | Router -> Guard -> AuthStore |
| Guard de rota: com token, sem user -> fetchMe -> next | Router -> Guard -> AuthStore -> API mock |
| Guard de rota: guest com token -> redirect dashboard | Router -> Guard -> AuthStore |
| 401 interceptor -> clearSessionLocally -> redirect login | apiClient -> interceptor -> AuthStore -> Router |
| 429 interceptor -> toast exibido | apiClient -> interceptor -> toast callback |

### 3.3 Testes end-to-end

**Objetivo:** Validar fluxos completos do ponto de vista do usuario em ambiente real (Cypress ou Playwright contra Docker Compose).

| Fluxo E2E | Passos | Verificacao |
|---|---|---|
| **F1: Registro completo** | Abrir /register -> preencher nome, email, senha -> submeter -> redirect dashboard | Dashboard visivel, user logado |
| **F2: Login + navegacao** | Abrir /login -> credenciais validas -> dashboard -> sidebar -> navegar sessoes -> voltar | Todas telas carregam |
| **F3: Sessao de estudo completa** | Login -> criar tecnologia -> iniciar sessao foco -> timer roda -> encerrar -> dashboard atualiza | Timer funciona, sessao aparece na lista, metricas mudam |
| **F4: Log manual de sessao** | Login -> sessoes -> nova sessao manual (com started_at, ended_at, notas, mood) -> submeter | Sessao na lista com dados corretos |
| **F5: CRUD de tecnologia** | Login -> tecnologias -> criar -> editar nome -> desativar -> verificar que sumiu da lista | Tecnologia persiste e desativa |
| **F6: Dashboard reativo** | Login -> dashboard -> verificar KPIs -> criar sessao em outra aba -> dashboard atualiza (WebSocket ou polling) | Numeros mudam |
| **F7: Logout + guard** | Login -> logout -> tentar acessar /dashboard -> redirect /login | Nao acessa area protegida |
| **F8: Tratamento de erros** | Login -> provocar 422 (sessao sem technology_id) -> mensagem de erro | Toast/mensagem visivel |

### 3.4 Testes de contrato de API

**Objetivo:** Garantir que o frontend e o backend concordam sobre a estrutura de requests e responses.

**Implementacao:** Usar schemas JSON exportados do backend (ou derivados dos API Resources) e validar contra os `types/*.ts` do frontend.

| Contrato | Backend (Resource/Response) | Frontend (Type) | Campos criticos |
|---|---|---|---|
| Login response | `{ success, data: { user: UserResource, token, token_type } }` | `auth.api.ts` response | `user.id`, `user.email`, `token` |
| Register response | `{ success, data: { user, token, token_type } }` | `auth.api.ts` response | Mesma estrutura do login |
| Me response | `{ success, data: UserResource }` | `User` em `domain.types.ts` | `id`, `name`, `email`, `timezone` |
| Study session response | `StudySessionResource` | `StudySession` em types | `id`, `technology_id`, `started_at`, `ended_at`, `duration_min`, `notes`, `mood`, `focus_score` |
| Session list response | `{ success, data: [...], meta: { current_page, last_page, per_page, total } }` | API response + meta | Paginacao |
| Active session response | `{ ...session, elapsed_seconds }` | `ActiveSessionResponse` | `elapsed_seconds` |
| Technology response | `TechnologyResource` | `Technology` em types | `id`, `name`, `slug`, `color`, `is_active` |
| Dashboard response | `DashboardResource` | analytics types | `user_metrics`, `technology_metrics`, `time_series_30d`, `top_technologies` |
| Error response (422) | `{ success: false, error: { code, message, details } }` | `getApiErrorMessage()` | `error.code`, `error.message` |
| Error response (401) | `{ success: false, error: { code: 'UNAUTHENTICATED' } }` | interceptor | `error.code` |
| Error response (409) | `{ success: false, error: { code: 'CONCURRENT_SESSION' } }` | tipo de erro | `error.code` |
| Error response (429) | `{ success: false, error: { code: 'RATE_LIMITED', retry_after } }` | interceptor | `retry_after` numerico |
| Export response | `{ success, data: { exported_at, period, data } }` | export types | Estrutura do array data |
| Heatmap response | `{ success, data: [...] }` | chart types | Formato compativel com HeatmapChart |
| Time series response | `{ success, data: [...] }` | chart types | Formato compativel com LineChart |
| Health response | `{ status, version, timestamp, ?services }` | Nao consumido pelo frontend, mas por infra | `status` in ['healthy', 'degraded'] |

**Como implementar:**

1. No backend, criar teste que serializa cada Resource e asserta a estrutura JSON.
2. No frontend, criar esquemas Zod que refletem a tipagem TS e validar responses mockadas.
3. Em CI, gerar snapshot do contrato no backend e validar no frontend (ou usar ferramenta como Pact).

### 3.5 Testes de regressao

**Objetivo:** Detectar regressoes em funcionalidades existentes apos mudancas.

| Cenario de regressao | Gatilho provavel | Teste |
|---|---|---|
| Login para de funcionar apos mudanca em AuthService | Refatoracao de auth | Feature test: login com credenciais validas retorna 200 + token |
| Sessao ativa nao e detectada apos mudanca no repository | Query alterada | Feature test: start -> active retorna sessao |
| Dashboard retorna dados vazios apos mudanca no aggregator | Alteracao de query analytics | Feature test: dashboard com sessoes existentes retorna dados |
| Timer no frontend congela apos refatoracao de useSessionTimer | Mudanca em composable | Unit test: timer incrementa corretamente |
| Guard de rota permite acesso sem token | Mudanca em guards.ts | Unit test: rota protegida sem token -> redirect login |
| Cache nao invalida apos CRUD de sessao | Listener alterado | Integration test: criar sessao -> cache de dashboard fica stale -> verificar que listener limpou |
| Evento nao e broadcastado apos encerrar sessao | Mudanca no listener | Unit test: SessionEnded -> BroadcastSessionEnded chamado |
| Recalculo de metricas nao dispara | Listener DispatchMetricsRecalculation alterado | Unit test: evento -> job dispatched |
| 401 nao redireciona para login | Interceptor alterado | Unit test: apiClient com 401 -> clearSessionLocally |
| Paginacao quebrada na lista de sessoes | Mudanca no controller/repository | Feature test: listagem com paginacao retorna meta correto |
| Search de tecnologia nao retorna resultados | Query GIN alterada | Feature test: search com termo -> resultados |
| Export retorna dados incorretos | Repository alterado | Unit test: getExportData com range -> formato correto |
| Goals desaparecem apos refresh | Store alterado | Unit test: goals persistem em localStorage e recarregam |

### 3.6 Testes de seguranca

Detalhados na secao 5.

### 3.7 Testes de carga/performance basicos

**Objetivo:** Identificar gargalos obvios e garantir que o sistema suporta uso normal.

| Teste | Ferramenta | Metrica | Limite aceitavel |
|---|---|---|---|
| Login sob carga (50 usuarios simultaneos) | k6 / Artillery | p95 response time | < 500ms |
| Dashboard sob carga (100 req/s) | k6 | p95 response time | < 1s |
| Listagem de sessoes (1000 sessoes por usuario) | PHPUnit + factory | Tempo de resposta | < 300ms |
| RecalculateMetricsJob com 5000 sessoes | PHPUnit + factory | Tempo de execucao | < 10s |
| Search de tecnologia com 100 techs e busca parcial | PHPUnit + factory | Tempo de resposta | < 200ms |
| Cache hit rate apos warm-up | Redis MONITOR + metricas | Hit rate | > 80% |
| Concurrent session start (race condition) | PHPUnit com paralelismo ou k6 | 0 sessoes duplicadas | Exatamente 1 criada |
| Bundle frontend | `vite build` + `vite-plugin-inspect` | Tamanho total | < 500KB gzipped |

### 3.8 Testes de resiliencia/falha

**Objetivo:** Validar comportamento do sistema quando dependencias falham.

| Cenario de falha | Expectativa | Teste |
|---|---|---|
| Redis indisponivel — requisicao de dashboard | Bypass cache, busca direto do DB (ou 503) | Feature test com Redis mock lançando excecao |
| Redis indisponivel — sliding window rate limit | Fail-open (request passa) | Unit test do middleware com Lua lançando excecao |
| Redis indisponivel — job dedup | Fail-open (job dispara) | Unit test do listener com Lua lançando excecao |
| PostgreSQL lento — dashboard | Timeout controlado, nao hang eterno | Feature test com query lenta mockada |
| Reverb/WebSocket down — frontend | Fallback timer de 45s libera spinner | Unit test do useWebSocket com conexao falhada |
| RecalculateMetricsJob falha 3x | Log de erro, nao retenta infinitamente | Unit test: job com excecao -> tries esgotados -> failed() chamado |
| Broadcast de MetricsRecalculated falha | Dashboard nao atualiza por WS, mas polling ou refresh manual recupera | E2E: matar Reverb -> criar sessao -> refresh manual do dashboard |
| Container PHP-FPM reinicia durante request | Nginx retorna 502/504, frontend exibe erro | E2E: matar php-fpm durante request -> verificar que frontend mostra mensagem |
| Token expirado durante uso | 401 -> redirect login, sem loop | Unit test: apiClient com 401 em rota protegida |

### 3.9 Testes de usabilidade/fluxo critico do usuario

**Objetivo:** Garantir que a experiencia do usuario final e coerente e livre de frustracoes.

| Fluxo critico | Criterios de aceitacao |
|---|---|
| Primeiro acesso: registro -> primeiro login -> dashboard vazio | Mensagem de boas-vindas ou estado vazio amigavel, sem erro |
| Criar primeira tecnologia | Formulario claro, feedback de sucesso, tecnologia aparece na lista |
| Iniciar primeira sessao de estudo | Escolher tecnologia -> timer inicia -> feedback visual claro |
| Encerrar sessao | Botao de encerrar -> timer para -> sessao aparece como concluida com duracao |
| Voltar no dia seguinte e ver dashboard | Dados do dia anterior refletidos, heatmap com 1 dia preenchido |
| Tentar iniciar sessao com outra ativa | Mensagem clara de que ja existe sessao ativa, com opcao de encerrar |
| Navegar entre telas com sessao ativa | Timer visivel em algum lugar, sessao nao se perde |
| Perder conexao e reconectar | Dashboard eventualmente atualiza, sem estado corrompido |
| Trocar tema (dark/light) | Todos componentes, graficos e icones adaptam sem quebra visual |
| Exportar dados | Feedback de loading, download funciona, dados corretos |

---

## 4. Cobertura funcional detalhada

### 4.1 Autenticacao (Login, Registro, Logout, Troca de Senha, Gestao de Tokens)

#### 4.1.1 Registro

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| AUTH-R01 | Registro com dados validos | name, email, password, password_confirmation | 201, user + token | Feature |
| AUTH-R02 | Registro com email duplicado | email ja existente | 422 VALIDATION_ERROR | Feature |
| AUTH-R03 | Registro sem password_confirmation | password sem confirmacao | 422 VALIDATION_ERROR | Feature |
| AUTH-R04 | Registro com senha curta | password < 8 chars | 422 VALIDATION_ERROR | Feature |
| AUTH-R05 | Registro com email invalido | email: "naoeum@email" | 422 VALIDATION_ERROR | Feature |
| AUTH-R06 | Registro com timezone customizado | timezone: "America/Sao_Paulo" | 201, user.timezone = "America/Sao_Paulo" | Feature |
| AUTH-R07 | Registro sem timezone usa default UTC | sem campo timezone | 201, user.timezone = "UTC" | Feature |
| AUTH-R08 | Token retornado e funcional | usar token para GET /auth/me | 200, dados do usuario | Feature |
| AUTH-R09 | Rate limit de registro | > N registros em 1 minuto | 429 RATE_LIMITED | Feature |

#### 4.1.2 Login

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| AUTH-L01 | Login com credenciais validas | email + password corretos | 200, user + token + token_type Bearer | Feature |
| AUTH-L02 | Login com senha incorreta | password errado | 401 UNAUTHENTICATED | Feature |
| AUTH-L03 | Login com email inexistente | email nao cadastrado | 401 UNAUTHENTICATED | Feature |
| AUTH-L04 | Login revoga tokens anteriores | login -> login novamente | Primeiro token invalido | Feature |
| AUTH-L05 | Login com campos vazios | email: "", password: "" | 422 VALIDATION_ERROR | Feature |
| AUTH-L06 | Rate limit de login | > N logins em 1 minuto | 429 RATE_LIMITED | Feature |
| AUTH-L07 | Login retorna estrutura consistente | credenciais validas | data.user contem id, name, email; data.token string | Contrato |

#### 4.1.3 Logout

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| AUTH-O01 | Logout revoga token atual | POST /auth/logout com token valido | 200, token invalidado | Feature |
| AUTH-O02 | Logout com token invalido | Bearer com token expirado | 401 | Feature |
| AUTH-O03 | Apos logout, me retorna 401 | logout -> GET /auth/me | 401 | Feature |

#### 4.1.4 Troca de senha

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| AUTH-P01 | Troca com senha atual correta | current_password correto, password novo | 200, sucesso | Feature |
| AUTH-P02 | Troca com senha atual incorreta | current_password errado | 422 | Feature |
| AUTH-P03 | Troca revoga todos os tokens | trocar senha -> usar token antigo | 401 | Feature |
| AUTH-P04 | Nova senha muito curta | password < 8 chars | 422 | Feature |
| AUTH-P05 | Rate limit (endpoint sensivel) | > N trocas em 1 minuto | 429 | Feature |

#### 4.1.5 Gestao de tokens

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| AUTH-T01 | Listar tokens retorna array | GET /auth/tokens | 200, array com id, name, created_at, last_used_at | Feature |
| AUTH-T02 | Revogar todos os tokens | DELETE /auth/tokens | 200, revoked_count >= 1 | Feature |
| AUTH-T03 | Apos revogar todos, nenhum token funciona | revokeAll -> qualquer request | 401 | Feature |

### 4.2 Guards de rota e persistencia de sessao

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| GUARD-01 | Rota protegida sem token -> redirect /login | next({ name: 'login' }) | Unit (guard) |
| GUARD-02 | Rota guest com token -> redirect /dashboard | next({ name: 'dashboard' }) | Unit (guard) |
| GUARD-03 | Rota protegida com token, sem user -> fetchMe -> next | user preenchido, next() | Unit (guard) |
| GUARD-04 | Rota protegida com token, user presente -> background refresh | fetchMe em background, next() imediato | Unit (guard) |
| GUARD-05 | fetchMe falha com 401 -> redirect login | token limpo, redirect | Unit (guard) |
| GUARD-06 | Refresh da pagina: token em localStorage -> restaura sessao | user recarregado, dashboard acessivel | E2E |
| GUARD-07 | fetchMe ja em andamento -> nao duplica chamada | fetchMePromise reutilizado | Unit (guard) |

### 4.3 CRUD de tecnologias

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| TECH-01 | Listar tecnologias do usuario | GET /technologies | 200, array de TechnologyResource | Feature |
| TECH-02 | Criar tecnologia | name, slug, color | 201, tecnologia criada | Feature |
| TECH-03 | Criar com nome duplicado | nome ja existente | 422 | Feature |
| TECH-04 | Criar sem campos obrigatorios | payload vazio | 422 | Feature |
| TECH-05 | Mostrar tecnologia por ID | GET /technologies/{id} | 200, tecnologia | Feature |
| TECH-06 | Mostrar tecnologia de outro usuario | ID de tech de outro user | 403 ou 404 | Feature/Seguranca |
| TECH-07 | Atualizar tecnologia | PUT com novos dados | 200, dados atualizados | Feature |
| TECH-08 | Desativar tecnologia | DELETE /technologies/{id} | 200, tecnologia desativada (soft delete) | Feature |
| TECH-09 | Tecnologia desativada nao aparece no index | apos desativar | GET index nao contem a tech | Feature |
| TECH-10 | Sessoes da tech desativada permanecem intactas | desativar tech com sessoes | Sessoes ainda existem | Feature |
| TECH-11 | Search com query parcial | GET /search?q=vue&limit=5 | Resultados filtrados | Feature |
| TECH-12 | Search com query vazia | GET /search?q= | Retorno vazio ou todos | Feature |
| TECH-13 | Rate limit de search | > N buscas em 1 minuto | 429 | Feature |

### 4.4 Sessoes de estudo

#### 4.4.1 Criacao e log manual

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| SESS-C01 | Criar sessao manual com todos campos | technology_id, started_at, ended_at, notes, mood, focus_score | 201, sessao criada com todos campos | Feature |
| SESS-C02 | Criar sessao sem ended_at (aberta) | apenas started_at | 201, sessao com ended_at null | Feature |
| SESS-C03 | Criar com technology_id de outro usuario | tech de outro user | 403 | Feature/Seguranca |
| SESS-C04 | Criar sem technology_id | payload sem tech | 422 | Feature |
| SESS-C05 | Criar com ended_at antes de started_at | ended_at < started_at | 422 | Feature |
| SESS-C06 | Criar dispara StudySessionCreated | qualquer criacao valida | Evento disparado | Unit (service) |
| SESS-C07 | Sliding window rate limit em store | > 30 req/min | 429 | Feature |

#### 4.4.2 Modo foco (start/end)

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| SESS-F01 | Start com technology_id | POST /start com tech | 201, sessao ativa | Feature |
| SESS-F02 | Start sem technology_id (usa primeira tech) | POST /start sem tech | 201, usa primeira tech do usuario | Feature |
| SESS-F03 | Start com sessao ja ativa | segunda chamada a /start | 409 CONCURRENT_SESSION | Feature |
| SESS-F04 | End de sessao ativa | PATCH /end | 200, ended_at preenchido, duration_min calculado | Feature |
| SESS-F05 | End de sessao ja finalizada | PATCH /end em sessao com ended_at | 422 "Sessao ja finalizada" | Feature |
| SESS-F06 | Active retorna sessao em andamento | GET /active com sessao ativa | 200, sessao + elapsed_seconds | Feature |
| SESS-F07 | Active retorna null sem sessao ativa | GET /active sem sessao | 200, data: null | Feature |

#### 4.4.3 Listagem, detalhe, atualizacao, exclusao

| ID | Cenario | Input | Expected | Tipo |
|---|---|---|---|---|
| SESS-L01 | Listar com paginacao | GET /study-sessions?page=1&per_page=10 | 200, meta com current_page, last_page, per_page, total | Feature |
| SESS-L02 | Listar filtrado por technology_id | GET /study-sessions?technology_id=X | Apenas sessoes daquela tech | Feature |
| SESS-L03 | Listar filtrado por periodo | GET /study-sessions?start_date=X&end_date=Y | Apenas sessoes no periodo | Feature |
| SESS-D01 | Mostrar sessao do proprio usuario | GET /study-sessions/{id} | 200, dados da sessao | Feature |
| SESS-D02 | Mostrar sessao de outro usuario | GET com id de outro user | 403 | Feature/Seguranca |
| SESS-D03 | Mostrar sessao inexistente | UUID invalido | 404 | Feature |
| SESS-U01 | Atualizar notas e mood | PATCH com notes + mood | 200, campos atualizados | Feature |
| SESS-U02 | Atualizar de outro usuario | PATCH em sessao de outro | 403 | Feature/Seguranca |
| SESS-X01 | Deletar sessao propria | DELETE /study-sessions/{id} | 200, sessao removida | Feature |
| SESS-X02 | Deletar sessao de outro usuario | DELETE em sessao de outro | 403 | Feature/Seguranca |
| SESS-X03 | Deletar dispara StudySessionDeleted com dados corretos | qualquer delete valido | Evento com userId, sessionId, duration_min, started_at | Unit (service) |

### 4.5 Prevencao de sessoes simultaneas

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| CONC-01 | Start -> Start (mesmo usuario) | 409 na segunda | Feature |
| CONC-02 | Start -> End -> Start | Sucesso nas tres operacoes | Feature |
| CONC-03 | Race condition: 2 starts simultaneos | Exatamente 1 criada (trigger/constraint) | Feature (paralelismo) |
| CONC-04 | Start usuario A, Start usuario B | Ambos criam (independentes) | Feature |
| CONC-05 | Trigger de banco rejeita insert de sessao ativa duplicada | INSERT direto no banco com 2 sessoes ativas | Migration/DB test |

### 4.6 Dashboard e analytics

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| DASH-01 | Dashboard com usuario sem sessoes | Payload com zeros ou vazio | Feature |
| DASH-02 | Dashboard com sessoes recentes | user_metrics, technology_metrics, time_series_30d, top_technologies preenchidos | Feature |
| DASH-03 | Dashboard usa cache (segunda chamada sem recalc) | Response identica, sem hit no DB | Feature (assertar cache hit) |
| DASH-04 | Dashboard apos criar sessao (cache invalidado) | Dados atualizados na proxima chamada | Feature |
| DASH-05 | User metrics retorna streaks e totais | Campos de streak, total_minutes, total_sessions | Feature |
| DASH-06 | Tech stats retorna metricas por tecnologia | Array com name, total_minutes, session_count por tech | Feature |
| DASH-07 | Time series com 30 dias | Array de objetos { date, minutes } com 30 entradas | Feature |
| DASH-08 | Time series com parametro custom (7 dias) | 7 entradas | Feature |
| DASH-09 | Weekly retorna comparacao semanal | Dados de semana atual vs anterior | Feature |

### 4.7 Heatmap, series temporais, agregacoes e exportacao

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| HEAT-01 | Heatmap do ano corrente | Dados de atividade por dia | Feature |
| HEAT-02 | Heatmap de ano especifico | Dados daquele ano | Feature |
| HEAT-03 | Heatmap de usuario sem sessoes | Array vazio ou zerado | Feature |
| EXP-01 | Export com range valido | 200 com exported_at, period, data array | Feature |
| EXP-02 | Export com range invertido (start > end) | 422 ou array vazio | Feature |
| EXP-03 | Export com range muito grande | Funciona (mas verificar performance) | Feature/Performance |
| EXP-04 | Export sem parametros | 422 (start e end obrigatorios) | Feature |
| EXP-05 | Export rate limit | > N exports em 1 minuto | 429 | Feature |

### 4.8 Fallback quando WebSocket falha

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| WS-F01 | Reverb indisponivel -> dashboard carrega normalmente | Dashboard busca via HTTP sem erro | E2E |
| WS-F02 | Conexao WS cai durante uso -> isConnected = false | Estado reflete desconexao | Unit (composable) |
| WS-F03 | Recalculating sem metrics.updated em 45s -> spinner libera | setRecalculating(false) apos timeout | Unit (composable) |
| WS-F04 | WS reconecta apos queda | isConnected volta para true | E2E |

### 4.9 Sincronizacao entre eventos do backend e frontend

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| SYNC-01 | Criar sessao -> evento -> recalc -> broadcast -> frontend atualiza dashboard | Dashboard exibe metricas novas | Integration/E2E |
| SYNC-02 | Encerrar sessao -> SessionEnded broadcast -> frontend limpa sessao ativa | Timer para, sessao ativa some | Integration/E2E |
| SYNC-03 | Iniciar sessao -> SessionStarted broadcast -> frontend exibe sessao ativa | Timer inicia automaticamente | Integration/E2E |
| SYNC-04 | MetricsRecalculating broadcast -> frontend mostra spinner | analyticsStore.isRecalculating = true | Unit (composable) |
| SYNC-05 | MetricsRecalculated broadcast -> frontend recebe dashboard completo | analyticsStore atualiza, spinner some | Unit (composable) |

### 4.10 Goals frontend-only

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| GOAL-01 | Criar goal -> persiste em localStorage | goal aparece na lista, sobrevive refresh | Unit (store) |
| GOAL-02 | Editar goal | Goal atualizado em localStorage | Unit (store) |
| GOAL-03 | Deletar goal | Removido de localStorage e lista | Unit (store) |
| GOAL-04 | Limpar localStorage -> goals somem | Sem goals apos clear | Unit (store) |
| GOAL-05 | Goal com progresso calculado | useGoalProgress retorna % baseado em sessoes | Unit (composable) |
| GOAL-06 | Goals de um usuario nao vazam para outro (multi-tab) | Isolamento por chave | Unit (store) |

### 4.11 Tratamento de erros HTTP

| ID | Status | Cenario | Frontend esperado | Backend esperado | Tipo |
|---|---|---|---|---|---|
| ERR-401 | 401 | Token expirado em qualquer request | clearSessionLocally + redirect /login | `{ success: false, error: { code: 'UNAUTHENTICATED' } }` | Unit + Feature |
| ERR-403 | 403 | Acessar recurso de outro usuario | Mensagem "Acesso negado" | `{ success: false, error: { code: 'FORBIDDEN' } }` | Feature |
| ERR-404 | 404 | Recurso inexistente | Mensagem "Nao encontrado" | `{ success: false, error: { code: 'NOT_FOUND' } }` | Feature |
| ERR-422 | 422 | Validacao falhou | Exibir detalhes do campo | `{ success: false, error: { code: 'VALIDATION_ERROR', details } }` | Feature |
| ERR-429 | 429 | Rate limit excedido | Toast "Muitas requisicoes" | `{ success: false, error: { code: 'RATE_LIMITED', retry_after } }` | Feature |
| ERR-500 | 500 | Erro interno | Mensagem generica | `{ success: false, error: { code: 'INTERNAL_ERROR' } }` (sem stack trace em prod) | Feature |
| ERR-409 | 409 | Sessao concorrente | Mensagem "Sessao ativa existente" | `{ success: false, error: { code: 'CONCURRENT_SESSION' } }` | Feature |

### 4.12 Health checks

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| HC-01 | Todos servicos OK | 200, status: "healthy" | Feature |
| HC-02 | DB down | 503, status: "degraded" | Feature |
| HC-03 | Redis down | 503, status: "degraded" | Feature |
| HC-04 | Reverb down | status pode ser healthy (WS e nao-critico) ou degraded | Feature |
| HC-05 | Em ambiente local/testing, services aparecem | services: { database, redis, queue, websocket } | Feature |
| HC-06 | Em producao, services nao aparecem | Sem chave services | Feature |
| HC-07 | Rate limit no health | > N checks em 1 minuto | 429 | Feature |

---

## 5. Cobertura de seguranca

### 5.1 Autenticacao com token

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| SEC-A01 | Request sem Authorization header em rota protegida | 401 | Feature |
| SEC-A02 | Request com token invalido (lixo) | 401 | Feature |
| SEC-A03 | Request com token expirado/revogado | 401 | Feature |
| SEC-A04 | Token de usuario A nao funciona como usuario B | Dados retornados sao do dono do token | Feature |
| SEC-A05 | Login revoga tokens anteriores (sessao unica) | Token antigo retorna 401 | Feature |
| SEC-A06 | Senha nao aparece em UserResource | GET /me nao contem campo password | Contrato |
| SEC-A07 | Token nao aparece em logs (dontFlash) | password/current_password nao logados | Unit (Handler) |

### 5.2 Autorizacao entre usuarios

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| SEC-Z01 | User B acessa sessao de User A via GET | 403 | Feature |
| SEC-Z02 | User B atualiza sessao de User A via PATCH | 403 | Feature |
| SEC-Z03 | User B deleta sessao de User A via DELETE | 403 | Feature |
| SEC-Z04 | User B acessa tecnologia de User A via GET | 403 ou 404 | Feature |
| SEC-Z05 | User B usa technology_id de User A para criar sessao | 403 | Feature |
| SEC-Z06 | User B acessa dashboard de User A | Impossivel (endpoint usa request->user()) | Feature |
| SEC-Z07 | User B tenta revogar tokens de User A | Impossivel (endpoint usa request->user()) | Feature |

### 5.3 Rate limit

| ID | Endpoint | Limite esperado | Tipo |
|---|---|---|---|
| SEC-RL01 | POST /api/v1/auth/login | throttle:login | Feature |
| SEC-RL02 | POST /api/v1/auth/register | throttle:register | Feature |
| SEC-RL03 | GET /api/v1/technologies/search | throttle:search | Feature |
| SEC-RL04 | POST /api/v1/auth/change-password | throttle:sensitive | Feature |
| SEC-RL05 | POST /api/v1/analytics/recalculate | throttle:recalculate | Feature |
| SEC-RL06 | GET /api/v1/analytics/export | throttle:export | Feature |
| SEC-RL07 | GET /api/health | throttle:health | Feature |
| SEC-RL07b | GET /health (web) | throttle:health | Feature |
| SEC-RL08 | POST /api/v1/study-sessions/start | throttle.sliding:10 | Feature |
| SEC-RL09 | POST /api/v1/study-sessions | throttle.sliding:30 | Feature |
| SEC-RL10 | PATCH /api/v1/study-sessions/{id}/end | throttle.sliding:10 | Feature |
| SEC-RL11 | PUT/PATCH /api/v1/study-sessions/{id} | throttle.sliding:30 | Feature |
| SEC-RL12 | DELETE /api/v1/study-sessions/{id} | throttle.sliding:30 | Feature |
| SEC-RL13 | Rotas de leitura autenticadas | throttle:60,1 | Feature |
| SEC-RL14 | Rotas de escrita autenticadas (grupo api.php) | throttle:30,1 | Feature |

### 5.4 Validacao de payloads

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| SEC-V01 | Registro com campos extras (ex: is_admin) | Campos extras ignorados | Feature |
| SEC-V02 | Sessao com duration_min no payload (campo calculado) | Campo ignorado ou rejeitado | Feature |
| SEC-V03 | Sessao com user_id no payload (tentar injetar) | Ignorado, usa request->user() | Feature |
| SEC-V04 | Technology com is_active: false no create | Ignorado ou rejeitado | Feature |
| SEC-V05 | SQL injection em campo de busca | Search nao executa SQL arbitrario | Feature/Seguranca |
| SEC-V06 | XSS em campo notes de sessao | Conteudo armazenado/retornado sem execucao | Feature/Seguranca |
| SEC-V07 | Payload JSON malformado | 422 ou 400 | Feature |

### 5.5 Vazamento de dados sensiveis

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| SEC-D01 | UserResource nao expoe password hash | Campo ausente na resposta | Contrato |
| SEC-D02 | Resposta de erro em producao nao expoe stack trace | error.message generico | Feature |
| SEC-D03 | Logs nao contem senhas (dontFlash) | password, current_password, password_confirmation ausentes | Unit (Handler) |
| SEC-D04 | Health check em producao nao expoe detalhes de servicos | Sem chave services | Feature |

### 5.6 Protecao de canais privados WebSocket

| ID | Cenario | Expected | Tipo |
|---|---|---|---|
| SEC-WS01 | User A subscreve dashboard.{userA.id} | Autorizado | Feature/Integration |
| SEC-WS02 | User A tenta subscrever dashboard.{userB.id} | Rejeitado (403 na auth de canal) | Feature/Integration |
| SEC-WS03 | Request de auth de canal sem token | 401 | Feature |

### 5.7 Consistencia entre 403 e 404

| ID | Cenario | Comportamento atual | Recomendacao |
|---|---|---|---|
| SEC-C01 | GET sessao de outro usuario | 403 (revela existencia) | Considerar 404 para nao vazar que o recurso existe |
| SEC-C02 | GET tecnologia de outro usuario | 403 ou 404 (verificar) | Padronizar |
| SEC-C03 | GET sessao inexistente | 404 | OK |

> **Ponto de atencao:** O servico `findForUser` lanca `ModelNotFoundException` para ID inexistente e `AuthorizationException` para ownership incorreto. Isso diferencia 404 de 403, o que pode vazar informacao de existencia de recurso. Documentar a decisao ou alinhar para 404 em ambos os casos.

---

## 6. Cobertura de integracao e consistencia

### 6.1 Consistencia frontend <-> backend

| ID | Cenario | Validacao | Tipo |
|---|---|---|---|
| INT-01 | Campos de `UserResource` vs `User` em `domain.types.ts` | Snapshot de estrutura | Contrato |
| INT-02 | Campos de `StudySessionResource` vs tipo no frontend | Snapshot de estrutura | Contrato |
| INT-03 | Campos de `TechnologyResource` vs tipo no frontend | Snapshot de estrutura | Contrato |
| INT-04 | Campos de `DashboardResource` vs tipo analytics no frontend | Snapshot de estrutura | Contrato |
| INT-05 | Meta de paginacao (current_page, last_page, per_page, total) | Frontend le os 4 campos | Contrato |
| INT-06 | Formato de data ISO 8601 em todas respostas | Frontend parse com Date ou dayjs | Contrato |
| INT-07 | Formato de erro `{ success: false, error: { code, message } }` | `getApiErrorMessage()` extrai corretamente | Contrato |

### 6.2 Impacto de sessoes sobre metricas

| ID | Cenario | Validacao | Tipo |
|---|---|---|---|
| INT-M01 | Criar sessao encerrada -> metricas incrementam | Dashboard: total_minutes aumenta, total_sessions + 1 | Integration |
| INT-M02 | Deletar sessao -> metricas decrementam | Dashboard: total_minutes diminui, total_sessions - 1 | Integration |
| INT-M03 | Atualizar ended_at de sessao -> duracao recalculada | duration_min reflete nova duracao, metricas ajustadas | Integration |
| INT-M04 | Trocar technology_id de sessao -> metricas por tech ajustam | Tech antiga perde minutos, tech nova ganha | Integration |
| INT-M05 | Criar sessao com tecnologia nova -> tech aparece em tech_stats | Nova tech no array | Integration |

### 6.3 Invalidacao de cache

| ID | Cenario | Validacao | Tipo |
|---|---|---|---|
| INT-C01 | Criar sessao -> cache `sessions:user:{id}` flushed | Proxima listagem busca do DB | Integration |
| INT-C02 | Criar sessao -> RecalculateMetricsJob -> cache `analytics:user:{id}` flushed | Dashboard busca novo | Integration |
| INT-C03 | Deletar sessao -> ambos caches flushed | Listagem e dashboard atualizados | Integration |
| INT-C04 | Atualizar campo nao-relevante (notes) -> analytics nao recalcula | Cache analytics intacto (otimizacao) | Integration |
| INT-C05 | Atualizar ended_at -> analytics recalcula | Cache analytics flushed | Integration |
| INT-C06 | Disparo manual de recalculate -> cache flushed + dados recalculados | Dashboard atualizado | Integration |

### 6.4 Execucao de listeners e jobs

| ID | Cenario | Validacao | Tipo |
|---|---|---|---|
| INT-LJ01 | StudySessionCreated -> InvalidateSessionCache + DispatchMetricsRecalculation | Ambos listeners executam | Integration |
| INT-LJ02 | StudySessionUpdated com ended_at -> DispatchMetricsRecalculation com fullRecalc | Job dispatched com fullRecalc = true | Unit (listener) |
| INT-LJ03 | StudySessionUpdated com notes -> DispatchMetricsRecalculation nao despacha fullRecalc | fullRecalc = false (campo nao relevante) | Unit (listener) |
| INT-LJ04 | StudySessionDeleted -> DispatchMetricsRecalculation com fullRecalc | fullRecalc = true | Unit (listener) |
| INT-LJ05 | RecalculateMetricsJob executa em transacao | Metricas consistentes ou rollback completo | Unit (job) |
| INT-LJ06 | RecalculateMetricsJob falha -> failed() loga | Log contem userId e mensagem de erro | Unit (job) |
| INT-LJ07 | RecalculateMetricsJob unico por usuario (ShouldBeUnique) | Segundo dispatch no mesmo intervalo nao cria job duplicado | Integration |
| INT-LJ08 | Job dedup via Lua impede dispatch repetido dentro da janela | shouldDispatch retorna 0 na segunda chamada | Unit (listener + Lua) |
| INT-LJ09 | Lua indisponivel -> fail-open -> job dispara normalmente | Log warning, job executado | Unit (listener) |

### 6.5 Broadcast e atualizacao do frontend

| ID | Cenario | Validacao | Tipo |
|---|---|---|---|
| INT-B01 | MetricsRecalculated broadcast -> canal correto (dashboard.{userId}) | broadcastOn retorna PrivateChannel correto | Unit (event) |
| INT-B02 | MetricsRecalculated -> broadcastAs retorna '.metrics.updated' | Frontend escuta o evento correto | Unit (event) |
| INT-B03 | MetricsRecalculated -> broadcastWith retorna { dashboard } | Payload contem dashboardData completo | Unit (event) |
| INT-B04 | MetricsRecalculating broadcast -> frontend mostra loading | analyticsStore.isRecalculating = true | Unit (composable) |
| INT-B05 | SessionStarted broadcast -> frontend atualiza sessao ativa | sessionsStore.activeSession preenchido | Unit (composable) |
| INT-B06 | SessionEnded broadcast -> frontend limpa sessao ativa | sessionsStore.activeSession = null | Unit (composable) |

### 6.6 Compatibilidade stores <-> Vue Query <-> backend

| ID | Cenario | Validacao | Tipo |
|---|---|---|---|
| INT-SQ01 | Vue Query para dashboard invalida apos mutacao de sessao | queryClient.invalidateQueries apos POST/PATCH/DELETE | Integration (frontend) |
| INT-SQ02 | Store de sessoes e Vue Query nao conflitam em dados | Fonte unica de verdade por dominio | Revisao de codigo |
| INT-SQ03 | Pinia e Vue Query com dados do mesmo endpoint | Verificar que nao ha divergencia | Revisao de codigo |

### 6.7 Consistencia entre schema transacional e analytics

| ID | Cenario | Validacao | Tipo |
|---|---|---|---|
| INT-SA01 | Criar sessao -> recalc -> user_metrics.total_sessions = COUNT(*) no public | Numeros batem | Integration |
| INT-SA02 | Deletar sessao -> recalc -> user_metrics.total_minutes -= session.duration_min | Numeros batem | Integration |
| INT-SA03 | daily_minutes soma com sessions do dia | SUM(duration_min) por dia bate | Integration |
| INT-SA04 | technology_metrics soma com sessions por tech | SUM(duration_min) por tech bate | Integration |

---

## 7. Cobertura de qualidade e regressao

### 7.1 Suite de regressao — funcionalidades antigas

| ID | Area | Teste | Frequencia |
|---|---|---|---|
| REG-01 | Auth | Login + Me + Logout funciona | Toda PR |
| REG-02 | Auth | Registro cria usuario e retorna token | Toda PR |
| REG-03 | Sessions | CRUD completo (store, show, update, delete) | Toda PR |
| REG-04 | Sessions | Start + End funciona | Toda PR |
| REG-05 | Sessions | Concurrent session bloqueada | Toda PR |
| REG-06 | Technologies | CRUD completo (create, list, update, deactivate) | Toda PR |
| REG-07 | Analytics | Dashboard retorna dados | Toda PR |
| REG-08 | Analytics | Export retorna dados | Toda PR |
| REG-09 | Frontend | Login form submete e redireciona | Toda PR |
| REG-10 | Frontend | Guard de rota bloqueia sem token | Toda PR |
| REG-11 | Frontend | 401 interceptor limpa sessao | Toda PR |
| REG-12 | Frontend | Stores carregam estado do localStorage | Toda PR |

### 7.2 Deteccao de inconsistencias documentadas

| ID | Inconsistencia | Teste proposto |
|---|---|---|
| DOC-01 | Rate limits no README divergem da implementacao | Snapshot test que extrai limites do codigo e compara com docs |
| DOC-02 | Goals descritos como feature full-stack, mas sao local-only | Teste verifica que goals.api.ts nao faz requests HTTP |
| DOC-03 | Docs mencionam pasta `docs/` que pode nao existir | Script de CI que verifica links internos |

### 7.3 Estabilidade em producao

| ID | Cenario | Tipo |
|---|---|---|
| STAB-01 | Smoke test pos-deploy: login + dashboard + criar sessao | E2E smoke |
| STAB-02 | Health check retorna 200 apos deploy | HTTP check |
| STAB-03 | Horizon processando filas (queue nao parada) | Verificacao de metrica |
| STAB-04 | Redis respondendo | Health check |
| STAB-05 | Frontend build sem erros de tipo | vue-tsc no CI |

---

## 8. Matriz de cobertura por modulo

| Modulo | Unit | Feature/Integration | E2E | Contrato | Seguranca | Performance | Resiliencia |
|---|---|---|---|---|---|---|---|
| **Auth (backend)** | AuthService, TokenService | Login, Register, Logout, Me, ChangePassword, Tokens | F1, F2, F7 | Login/Register/Me response | SEC-A*, SEC-Z06-Z07 | Login sob carga | Token expirado |
| **Sessions (backend)** | StudySessionService, DTOs | CRUD, Start, End, Active, Concurrent | F3, F4 | Session response, list meta | SEC-Z01-Z05 | Listagem 1000 | — |
| **Technologies (backend)** | TechnologyService | CRUD, Search, Deactivate | F5 | Tech response | SEC-Z04-Z05 | Search 100 techs | — |
| **Analytics (backend)** | AnalyticsService, MetricsAggregator | Dashboard, Metrics, TimeSeries, Heatmap, Export, Recalc | F6 | Dashboard response | SEC-Z06 | Dashboard sob carga | Redis down |
| **Events/Listeners** | Cada listener isolado | Pipeline: CRUD -> evento -> listener -> job | — | — | — | — | Lua indisponivel |
| **Jobs** | RecalculateMetricsJob, GenerateWeeklySummary | Execucao completa, retry, unique | — | — | — | 5000 sessions | Job falha 3x |
| **Middleware** | SlidingWindow, EnsureJson, SetTimezone, LogApi | Rate limit aplicado | — | — | SEC-RL* | — | Lua indisponivel |
| **Handler** | Todos os match cases | Cada status code | — | Formato de erro | SEC-D02 | — | — |
| **Health** | — | HC-01 a HC-07 | — | Health response | — | — | DB/Redis down |
| **Auth (frontend)** | auth.store, auth.api | LoginForm, RegisterForm | F1, F2, F7 | Login/Register types | — | — | 401 loop |
| **Sessions (frontend)** | sessions.store, composables | SessionsView, SessionFocusView | F3, F4 | Session types | — | — | — |
| **Technologies (frontend)** | technologies.store | TechnologiesView | F5 | Tech types | — | — | — |
| **Analytics (frontend)** | analytics.store | DashboardView, charts | F6 | Dashboard types | — | — | WS down |
| **Goals (frontend)** | goals.store | GoalsView | — | — | — | — | localStorage cheio |
| **WebSocket (frontend)** | useWebSocket | — | F6 | WS event types | SEC-WS* | — | Reverb down |
| **HTTP Client (frontend)** | apiClient, interceptors | — | — | Error format | — | — | 401, 429 |
| **Router/Guards (frontend)** | guards.ts | — | F7 | — | — | — | fetchMe falha |
| **Lua Scripts** | Cada script isolado | SlidingWindow, Streak, JobDedup | — | — | — | — | Redis down |
| **DB migrations** | — | Migrations up + down | — | — | Triggers, constraints | — | — |
| **Infra (Docker)** | — | — | Containers sobem | — | — | — | Container restart |

---

## 9. Priorizacao

### Prioridade CRITICA

| ID | O que testar | Tipo | Risco coberto | Justificativa |
|---|---|---|---|---|
| P-C01 | Login com credenciais validas/invalidas | Feature | Acesso ao sistema | Gate de entrada de todo o sistema |
| P-C02 | Registro de usuario | Feature | Aquisicao de usuarios | Sem registro, nenhum fluxo funciona |
| P-C03 | Start/End de sessao de estudo | Feature | Core do produto | Principal funcionalidade |
| P-C04 | Prevencao de sessao concorrente | Feature | Integridade de dados | Sessoes duplicadas corrompem metricas |
| P-C05 | Autorizacao cross-user (sessoes) | Feature/Seguranca | Vazamento de dados | Dado sensivel de outro usuario |
| P-C06 | Dashboard retorna dados corretos | Feature | Valor do produto | Principal tela de visualizacao |
| P-C07 | 401 interceptor no frontend | Unit | UX + seguranca | Loop infinito ou sessao fantasma |
| P-C08 | Guard de rota protegida | Unit | Seguranca frontend | Acesso a telas sem autenticacao |
| P-C09 | RecalculateMetricsJob executa corretamente | Unit/Integration | Integridade de metricas | Metricas sao derivadas — erro aqui propaga para todo analytics |
| P-C10 | Invalidacao de cache apos CRUD de sessao | Integration | Dados stale | Usuario ve dados antigos no dashboard |

### Prioridade ALTA

| ID | O que testar | Tipo | Risco coberto | Justificativa |
|---|---|---|---|---|
| P-A01 | CRUD de tecnologia | Feature | Funcionalidade de apoio | Sessoes dependem de tecnologias |
| P-A02 | Listagem de sessoes com paginacao/filtros | Feature | Navegacao de dados | Uso diario |
| P-A03 | Troca de senha com revogacao de tokens | Feature | Seguranca | Tokens antigos devem parar de funcionar |
| P-A04 | Logout revoga token | Feature | Seguranca | Token nao pode permanecer valido |
| P-A05 | Pipeline de eventos: CRUD -> listener -> job dispatch | Integration | Integridade do pipeline | Se quebrar, metricas param de atualizar |
| P-A06 | WebSocket: metrics.updated atualiza dashboard | Unit/E2E | UX em tempo real | Dashboard parece congelado |
| P-A07 | Rate limit em login e register | Feature | Seguranca | Brute force |
| P-A08 | Handler retorna formatos corretos para todos status | Feature | Contrato de API | Frontend nao parse erro corretamente |
| P-A09 | Time series e heatmap retornam dados | Feature | Visualizacao | Graficos vazios |
| P-A10 | Export retorna dados no range | Feature | Funcionalidade | Dados incorretos exportados |
| P-A11 | Auth store persiste token em localStorage | Unit | Persistencia de sessao | Refresh perde login |
| P-A12 | Canal privado WS autorizado apenas para o dono | Feature | Seguranca realtime | Cross-user em WS |

### Prioridade MEDIA

| ID | O que testar | Tipo | Risco coberto | Justificativa |
|---|---|---|---|---|
| P-M01 | Search de tecnologia com GIN index | Feature | Performance de busca | Autocomplete lento |
| P-M02 | MetricsAggregator calcula streaks | Unit | Corretude de streak | Streak e metrica de gamificacao |
| P-M03 | SetUserTimezone middleware | Unit | Corretude de horarios | Sessoes com hora errada |
| P-M04 | Goals frontend-only persiste em localStorage | Unit | Feature local | Goals somem |
| P-M05 | Fallback timer de 45s no WebSocket | Unit | UX | Spinner eterno |
| P-M06 | Job dedup via Lua | Unit | Eficiencia | Recalc redundante |
| P-M07 | SlidingWindowRateLimit middleware | Unit | Seguranca | Rate limit mais preciso |
| P-M08 | Health check com servicos degradados | Feature | Observabilidade | Falso positivo em monitoring |
| P-M09 | Validacao de payloads (campos extras ignorados) | Feature | Seguranca | Mass assignment |
| P-M10 | Consistencia de tipos frontend <-> backend | Contrato | Dessinc | Type error em runtime |
| P-M11 | useSessionTimer tick correto | Unit | UX | Timer mostra tempo errado |
| P-M12 | Weekly comparison | Feature | Visualizacao | Dados errados na comparacao |

### Prioridade BAIXA

| ID | O que testar | Tipo | Risco coberto | Justificativa |
|---|---|---|---|---|
| P-B01 | Bundle size do frontend | CI check | Performance | Bundle grande = carregamento lento |
| P-B02 | LogApiRequests middleware | Unit | Observabilidade | Log incompleto |
| P-B03 | EnsureJsonResponse middleware | Unit | Contrato | HTML em vez de JSON |
| P-B04 | GenerateWeeklySummaryJob | Unit | Feature secundaria | Resumo semanal |
| P-B05 | Tema dark/light em todos componentes | Visual/Snapshot | UX | Componente quebra no tema |
| P-B06 | Componentes UI base (BaseButton, BaseInput, etc.) | Snapshot | Regressao visual | Componente muda aparencia |
| P-B07 | Formatters e dateUtils | Unit | Exibicao | Numero formatado errado |
| P-B08 | Notifications store (in-memory) | Unit | UX | Notificacao nao aparece |
| P-B09 | Profile update | Feature | UX | Dados nao salvos |
| P-B10 | Heatmap de ano especifico | Feature | Edge case | Dados de anos anteriores |

---

## 10. Sugestao pratica de implementacao

### 10.1 Ferramentas — Backend

| Ferramenta | Uso | Ja presente? |
|---|---|---|
| **PHPUnit 11** | Testes unitarios e feature | Sim |
| **Larastan/PHPStan** | Analise estatica | Sim |
| **Laravel Pint** | Formatacao | Sim |
| **Pest** (recomendacao) | Alternativa a PHPUnit com sintaxe fluente | Nao — migrar gradualmente |
| **Laravel Dusk** (opcional) | Browser testing serverside | Nao |

### 10.2 Ferramentas — Frontend

| Ferramenta | Uso | Ja presente? |
|---|---|---|
| **Vitest** | Testes unitarios e de integracao | Sim |
| **@vue/test-utils** | Mount de componentes Vue | Sim |
| **happy-dom** | DOM simulado para testes | Sim |
| **Cypress** ou **Playwright** (recomendacao) | Testes E2E | Nao |
| **MSW (Mock Service Worker)** (recomendacao) | Mock de API para testes de componente | Nao |
| **@testing-library/vue** (recomendacao) | Testes centrados em acessibilidade | Nao |

### 10.3 Ferramentas — Contrato e Performance

| Ferramenta | Uso |
|---|---|
| **Pact** ou **schema snapshot** | Testes de contrato API |
| **k6** ou **Artillery** | Testes de carga basicos |
| **Lighthouse CI** | Performance e acessibilidade do frontend |

### 10.4 Testes que devem rodar no CI

| Suite | Trigger | Tempo estimado | Bloqueante? |
|---|---|---|---|
| Backend unit + feature (PHPUnit) | Toda PR | ~2min | Sim |
| Backend Larastan | Toda PR | ~30s | Sim |
| Backend Pint (dry-run) | Toda PR | ~10s | Sim |
| Frontend unit + component (Vitest) | Toda PR | ~1min | Sim |
| Frontend type-check (vue-tsc) | Toda PR | ~30s | Sim |
| Frontend lint (ESLint) | Toda PR | ~15s | Sim |
| Contrato de API | Toda PR | ~30s | Sim |
| E2E smoke (Cypress/Playwright) | Merge na main | ~5min | Sim para deploy |
| Performance (k6) | Semanal ou pre-release | ~10min | Nao (alerta) |

### 10.5 Testes que podem rodar localmente

| Suite | Comando |
|---|---|
| Backend unit + feature | `make test-back` |
| Frontend unit + component | `make test-front` ou `cd frontend && npm run test:run` |
| Frontend coverage | `cd frontend && npm run test:coverage` |
| E2E (contra Docker Compose) | `npx cypress run` ou `npx playwright test` |
| Contrato | Script customizado em CI ou local |

### 10.6 Mocks, fixtures, factories e seeds necessarios

#### Backend — Factories (ja existentes, expandir)

| Factory | Campos obrigatorios | Estados (states) necessarios |
|---|---|---|
| `UserFactory` | name, email, password, timezone | `withTimezone('America/Sao_Paulo')` |
| `TechnologyFactory` | user_id, name, slug, color, is_active | `inactive()` |
| `StudySessionFactory` | user_id, technology_id, started_at, ended_at | `active()` (sem ended_at), `withNotes()`, `withMood()`, `longSession()` (>2h) |

#### Backend — Seeders (ja existentes, expandir)

| Seeder | Proposito |
|---|---|
| `DemoDataSeeder` | Dados realistas para desenvolvimento e E2E |
| `TestSeeder` (criar) | Dados minimos e deterministicos para testes automatizados |
| `PerformanceSeeder` (criar) | 5000 sessoes + 100 techs para testes de carga |

#### Frontend — Mocks

| Mock | Proposito |
|---|---|
| `msw/handlers/auth.ts` | Mock de login, register, me, logout |
| `msw/handlers/sessions.ts` | Mock de CRUD de sessoes |
| `msw/handlers/technologies.ts` | Mock de CRUD de tecnologias |
| `msw/handlers/analytics.ts` | Mock de dashboard, metrics, export |
| `fixtures/user.ts` | Objeto User deterministico |
| `fixtures/session.ts` | Objetos StudySession em varios estados |
| `fixtures/technology.ts` | Objetos Technology |
| `fixtures/dashboard.ts` | Payload completo de dashboard |

### 10.7 Estrutura de pastas de teste

```
backend/
  tests/
    Unit/
      Modules/
        Auth/
          AuthServiceTest.php
          TokenServiceTest.php
        StudySessions/
          StudySessionServiceTest.php
          StudySessionFilterDTOTest.php
        Technologies/
          TechnologyServiceTest.php
        Analytics/
          AnalyticsServiceTest.php
          MetricsAggregatorTest.php         # ja existe
      Jobs/
        RecalculateMetricsJobTest.php       # ja existe
        GenerateWeeklySummaryJobTest.php
      Listeners/
        DispatchMetricsRecalculationTest.php
        InvalidateSessionCacheTest.php
        BroadcastMetricsRecalculatingTest.php  # ja existe
        BroadcastSessionStartedTest.php
        BroadcastSessionEndedTest.php
      Events/
        MetricsRecalculatedTest.php
        MetricsRecalculatingTest.php
      Middleware/
        SlidingWindowRateLimitTest.php
        EnsureJsonResponseTest.php
        SetUserTimezoneTest.php
      Exceptions/
        HandlerTest.php
      Resources/
        UserResourceTest.php
        StudySessionResourceTest.php
        TechnologyResourceTest.php
        DashboardResourceTest.php
    Feature/
      Auth/
        AuthTest.php                         # ja existe
        ChangePasswordTest.php
        TokenManagementTest.php
      StudySessions/
        StudySessionCrudTest.php             # ja existe
        StudySessionStartEndTest.php
        StudySessionFiltersTest.php
      Technologies/
        TechnologyCrudTest.php               # ja existe
        TechnologySearchTest.php
      Analytics/
        AnalyticsDashboardTest.php
        AnalyticsTimeSeriesTest.php
        AnalyticsHeatmapTest.php
        AnalyticsExportTest.php              # ja existe
        AnalyticsCacheTest.php               # ja existe
        AnalyticsRecalculateTest.php
      Security/
        AuthorizationTest.php                # ja existe (expandir)
        RateLimitTest.php
        PayloadInjectionTest.php
      Concurrent/
        StudySessionConcurrentTest.php       # ja existe
      Health/
        HealthCheckTest.php
      LuaScripts/
        SlidingWindowTest.php                # ja existe
        StreakTest.php                        # ja existe
        JobDedupTest.php                     # ja existe
    Contract/
      UserContractTest.php
      StudySessionContractTest.php
      TechnologyContractTest.php
      DashboardContractTest.php
      ErrorContractTest.php

frontend/
  src/
    __tests__/                               # testes de integracao de alto nivel
      e2e/                                   # se usar Cypress/Playwright embarcado
        auth.spec.ts
        sessions.spec.ts
        dashboard.spec.ts
    api/
      __tests__/
        client.spec.ts                       # ja existe
        auth.api.spec.ts
        sessions.api.spec.ts
    stores/
      __tests__/
        auth.store.spec.ts                   # ja existe
        analytics.store.spec.ts              # ja existe
        sessions.store.spec.ts
        technologies.store.spec.ts           # ja existe
        goals.store.spec.ts                  # ja existe
        notifications.store.spec.ts
    composables/
      __tests__/
        useWebSocket.spec.ts                 # ja existe (expandir)
        useSessionTimer.spec.ts              # ja existe
        useFormValidation.spec.ts            # ja existe
        useDebounce.spec.ts                  # ja existe
        usePagination.spec.ts                # ja existe
        useSort.spec.ts                      # ja existe
        useAsync.spec.ts                     # ja existe
        useMetrics.spec.ts
        useDashboard.spec.ts
    features/
      auth/
        __tests__/
          LoginForm.spec.ts
          RegisterForm.spec.ts
      sessions/
        components/
          __tests__/
            SessionCard.spec.ts              # ja existe (expandir)
            SessionForm.spec.ts
            SessionTimer.spec.ts
      dashboard/
        __tests__/
          DashboardView.spec.ts
      technologies/
        __tests__/
          TechnologyForm.spec.ts
    utils/
      __tests__/
        dateUtils.spec.ts                    # ja existe
        formatters.spec.ts                   # ja existe
        validators.extended.spec.ts          # ja existe
    router/
      __tests__/
        guards.spec.ts
    mocks/                                   # MSW handlers + fixtures
      handlers/
        auth.ts
        sessions.ts
        technologies.ts
        analytics.ts
      fixtures/
        user.ts
        session.ts
        technology.ts
        dashboard.ts
      server.ts                              # MSW setup
```

---

## 11. Plano de automacao

### 11.1 Fase 1 — Fundacao (Semanas 1-2)

**Meta:** Cobrir os cenarios de prioridade CRITICA.

| Tarefa | Estimativa | Resultado |
|---|---|---|
| Expandir AuthTest.php com change-password e tokens | 4h | AUTH-P*, AUTH-T* |
| Criar StudySessionStartEndTest.php | 4h | SESS-F01 a F07 |
| Expandir AuthorizationTest.php com tecnologias e delete | 3h | SEC-Z01 a Z07 |
| Criar HandlerTest.php (todos status codes) | 3h | ERR-* |
| Expandir auth.store.spec.ts e adicionar guards.spec.ts | 4h | GUARD-01 a 07 |
| Configurar MSW no frontend | 3h | Infraestrutura de mocks |
| Criar fixtures de user, session, technology, dashboard | 2h | Dados deterministicos |
| Criar RecalculateMetricsJob testes completos | 3h | P-C09 |
| Criar testes de invalidacao de cache | 3h | INT-C01 a C06 |

**Total estimado: ~29h**

### 11.2 Fase 2 — Cobertura funcional (Semanas 3-4)

**Meta:** Cobrir cenarios de prioridade ALTA.

| Tarefa | Estimativa | Resultado |
|---|---|---|
| Criar TechnologySearchTest.php | 2h | TECH-11 a 13 |
| Criar StudySessionFiltersTest.php | 3h | SESS-L01 a L03 |
| Criar RateLimitTest.php (todos endpoints) | 4h | SEC-RL* |
| Criar AnalyticsDashboardTest.php e RecalculateTest.php | 4h | DASH-* |
| Criar testes de listeners (todos 7) | 4h | INT-LJ* |
| Criar testes de eventos (broadcast) | 3h | INT-B* |
| Criar LoginForm.spec.ts e RegisterForm.spec.ts | 4h | P-A11 |
| Expandir useWebSocket.spec.ts | 3h | WS-F*, SYNC-* |
| Criar sessions.store.spec.ts | 2h | Sessoes no frontend |

**Total estimado: ~29h**

### 11.3 Fase 3 — Contrato e E2E (Semanas 5-6)

**Meta:** Garantir consistencia entre camadas e fluxos de usuario.

| Tarefa | Estimativa | Resultado |
|---|---|---|
| Criar testes de contrato no backend (5 resources) | 6h | INT-01 a 07 |
| Configurar Cypress ou Playwright | 4h | Infraestrutura E2E |
| Implementar 8 fluxos E2E | 12h | F1 a F8 |
| Criar HealthCheckTest.php | 2h | HC-01 a 07 |
| Criar PayloadInjectionTest.php | 3h | SEC-V* |

**Total estimado: ~27h**

### 11.4 Fase 4 — Resiliencia e Performance (Semanas 7-8)

**Meta:** Cobrir cenarios de falha e metricas basicas de performance.

| Tarefa | Estimativa | Resultado |
|---|---|---|
| Criar testes de resiliencia (Redis/Reverb down) | 6h | Todos cenarios de resiliencia |
| Configurar k6 com 3 cenarios | 4h | Performance basica |
| Criar PerformanceSeeder | 2h | Dados para carga |
| Testes de middleware (SlidingWindow, EnsureJson, Timezone) | 4h | P-M03, P-M07 |
| Snapshot tests de componentes UI | 4h | P-B06 |

**Total estimado: ~20h**

---

## 12. Plano de execucao no CI/CD

O YAML desta seccao e **exemplo alvo / conceitual**. O pipeline **real** esta descrito na secção **1.4** e em `.github/workflows/backend-ci.yml` e `frontend-ci.yml`.

### 12.1 Pipeline de PR (toda pull request)

```yaml
# .github/workflows/test.yml (conceitual — nao e o ficheiro real do repo)
name: Test Suite
on: [pull_request]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: studytrack_test
          POSTGRES_USER: studytrack
          POSTGRES_PASSWORD: test
      redis:
        image: redis:7
    steps:
      - checkout
      - setup php 8.2
      - composer install
      - cp .env.testing .env
      - php artisan migrate --seed
      - php artisan test --parallel    # PHPUnit (unit + feature)
      - vendor/bin/pint --test         # Formatacao
      - vendor/bin/phpstan analyse     # Analise estatica

  frontend-tests:
    runs-on: ubuntu-latest
    steps:
      - checkout
      - setup node 20
      - npm ci (frontend/)
      - npm run type-check             # vue-tsc
      - npm run lint -- --max-warnings=0
      - npm run test:run               # Vitest
      - npm run test:coverage          # Coverage report

  contract-tests:
    needs: [backend-tests]
    runs-on: ubuntu-latest
    steps:
      - checkout
      - Gerar snapshots de contrato do backend
      - Validar contra tipos do frontend
```

### 12.2 Pipeline de merge na main

```yaml
name: E2E + Deploy
on:
  push:
    branches: [main]

jobs:
  e2e:
    runs-on: ubuntu-latest
    steps:
      - docker compose up -d
      - Aguardar health check OK
      - npx playwright test            # ou cypress run
      - docker compose down

  deploy:
    needs: [e2e]
    # ... deploy steps
```

### 12.3 Pipeline semanal

```yaml
name: Performance + Extended
on:
  schedule:
    - cron: '0 3 * * 1'  # Segunda 3h

jobs:
  performance:
    steps:
      - docker compose up -d
      - Seed de performance (5000 sessoes)
      - k6 run load-test.js
      - Coletar e comparar metricas
```

---

## 13. Riscos nao cobertos

### 13.1 Riscos aceitos ou parcialmente cobertos

| Risco | Motivo | Mitigacao alternativa |
|---|---|---|
| Testes de acessibilidade (a11y) completos | Nao priorizados neste plano | Lighthouse CI + revisao manual |
| Testes visuais de regressao (screenshots) | Ferramentas como Percy/Chromatic tem custo | Snapshot tests de componentes como proxy |
| Performance real do WebSocket sob carga | Dificil simular com k6 | Monitoramento em producao |
| Integracao com email real (Mailpit) | Apenas ambiente dev | Mock em testes, verificacao manual |
| Multi-browser E2E (Safari, Firefox) | Custo de CI | Playwright com matrix de browsers |
| Mobile responsiveness | Nao priorizados neste plano | Media queries + Lighthouse |
| Upgrade de dependencias (breaking changes) | Nao e teste funcional | Dependabot + CI catch |
| Observabilidade de filas em producao | Horizon UI, nao testavel via automacao | Alertas de fila parada |

### 13.2 Lacunas do projeto que dificultam testes

| Lacuna | Impacto nos testes | Recomendacao |
|---|---|---|
| Goals sem backend | Impossivel testar sincronizacao | Criar API de goals ou documentar como local-only permanente |
| Autorizacao nao centralizada em Policies | Ownership verificado de formas diferentes em cada modulo | Centralizar em Policy para padronizar semantica 403/404 |
| Coexistencia Pinia + Vue Query para mesmos dados | Dificil saber qual e a fonte de verdade em testes | Definir e documentar responsabilidade por dominio |
| Rate limits devem seguir `AppServiceProvider` + `api.php` | Testes de rate limit podem falhar se a doc estiver velha | Manter README/checklists alinhados ao codigo |
| `findForUser` retorna 403 para ownership (nao 404) | Facilita IDOR scan (atacante sabe que recurso existe) | Considerar retornar 404 para todos os casos |
| Ausencia de tipos Zod para todas as responses | Contrato nao e validado em runtime no frontend | Expandir uso de Zod para parse de todas responses criticas |
| Graficos em ApexCharts com varios wrappers em `components/charts` | Regressoes de tema/config | Cobrir com testes de componente nos graficos criticos e tema unificado |

---

## 14. Recomendacoes finais

### 14.1 Acoes imediatas (esta sprint)

1. **Expandir os testes de Feature existentes** com os cenarios CRITICOS listados na secao 9.
2. **Criar `HandlerTest.php`** para garantir que todos os status codes retornam o formato correto.
3. **Manter e estender `frontend/src/router/__tests__/guards.spec.ts`** conforme novas rotas ou regras de auth.
4. **Configurar MSW** (roadmap) para testes de componente mais realistas no frontend — **nao** integrado no CI atual.
5. **Opcional — gate de cobertura no CI:** exigir 80% backend / 70% frontend **so apos** definir baseline e job que falhe o PR (hoje o CI **nao** aplica este limiar).

### 14.2 Acoes de medio prazo (proximo mes)

1. **Implementar testes de contrato** usando snapshots de JSON dos Resources.
2. **Configurar Playwright** para os 8 fluxos E2E criticos.
3. **Criar suites de resiliencia** para Redis down, Reverb down, job failure.
4. **Centralizar autorizacao** em Policies do Laravel para padronizar comportamento e facilitar teste.
5. **Alinhar documentacao de rate limits** com a implementacao e criar teste snapshot.

### 14.3 Acoes de longo prazo (proximo trimestre)

1. **Migrar para Pest** gradualmente (sintaxe mais expressiva, melhor DX).
2. **Adicionar Lighthouse CI** para monitorar performance e acessibilidade.
3. **Implementar testes de carga** com k6 contra ambiente staging.
4. **Criar contrato formal de API** (OpenAPI/Swagger) derivado dos Resources e validar automaticamente.
5. **Considerar visual regression testing** com Percy ou Chromatic para componentes UI.
6. **Implementar mutation testing** (Infection PHP, Stryker JS) para validar qualidade dos testes.

### 14.4 Metricas de saude da suite de testes

Metas **desejadas** (nao todas aplicadas como gate no GitHub Actions atual):

| Metrica | Meta desejada | Ferramenta / nota |
|---|---|---|
| Cobertura de codigo backend | > 80% | PHPUnit `--coverage` (artefacto no CI; sem minimo obrigatorio) |
| Cobertura de codigo frontend | > 70% | Vitest `--coverage` (local/roadmap; CI so corre `test:run`) |
| Tempo total do CI (PR) | < 5min | GitHub Actions |
| Flaky test rate | < 1% | Cultura + monitorizacao |
| Testes E2E passando | 100% | Playwright/Cypress quando existir pipeline E2E |
| Testes de contrato passando | 100% | Passo dedicado quando existir |

### 14.5 Cultura de testes

- **Regra de ouro:** Nenhuma PR sem testes para a funcionalidade alterada.
- **Boy Scout Rule:** Ao tocar em codigo sem teste, adicionar pelo menos 1 teste.
- **Revisao de PR deve incluir:** Verificar se testes cobrem o cenario novo, edge cases e regressoes.
- **Flaky tests:** Manter zero tolerance — consertar ou quarentinar imediatamente.
- **Documentacao de testes:** Manter este documento atualizado conforme o projeto evolui.
