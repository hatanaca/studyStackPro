# Prompt para Corrigir Todos os Requisitos Faltantes

Use este prompt para guiar a implementação completa dos itens pendentes do StudyTrack Pro. Execute as correções por blocos, priorizando CRÍTICO → ALTO → MÉDIO → BAIXO.

---

## Instrução geral para o agente

Você está corrigindo o projeto StudyTrack Pro (Laravel + Vue 3 + Docker). O backend fica em `backend/`, o frontend em `frontend/`, configurações Docker em `docker/` e raiz. Siga exatamente o checklist abaixo, implementando cada item na ordem indicada. Após cada bloco, rode os testes e valide antes de prosseguir.

---

## BLOCO 1 — SEGURANÇA (CRÍTICO)

### 1.1 change-password revoga TODOS os tokens
- **Arquivo:** `backend/app/Modules/Auth/Services/AuthService.php`
- **Ação:** No método `changePassword`, após `updatePassword` retornar true, chamar `$user->tokens()->delete()` para revogar todos os tokens do usuário.
- **Critério:** Após trocar senha, token atual e demais tokens ficam inválidos; usuário precisa fazer login novamente.

### 1.2 Resposta 403 (não 404) em acesso cross-user
- **Arquivo:** `backend/app/Modules/StudySessions/Services/StudySessionService.php`
- **Ação:** No método `findForUser`, quando `$session->user_id !== $userId`, em vez de `abort(404)`, use `abort(403)` ou lance `AuthorizationException` para retornar 403.
- **Arquivo:** `backend/app/Modules/Technologies/Services/TechnologyService.php` e demais services que verificam ownership — garantir que cross-user retorne 403, não 404.
- **Critério:** UserB acessando recurso de UserA retorna 403 "Acesso negado", nunca 404.

---

## BLOCO 2 — RATE LIMITING E API

### 2.1 Rate limiting nas rotas
- **Arquivo:** `backend/routes/api.php`
- **Ação:**
  - Envolver `auth/register` e `auth/login` em `Route::middleware('throttle:5,1')` (5 req/min).
  - Envolver rotas de escrita (POST, PUT, PATCH, DELETE) em `throttle:30,1`.
  - Envolver rotas de leitura (GET) em `throttle:60,1`.
  - Usar `throttle` específico por grupo; criar middleware customizado se necessário.
- **Arquivo:** `backend/app/Providers/RouteServiceProvider.php` ou `bootstrap/app.php` — garantir que `ThrottleRequests` use driver Redis (não 'cache' em memória) quando `CACHE_STORE=redis`.
- **Critério:** Mais de 5 logins/min retorna 429; writes e reads respeitam limites.

### 2.2 EnsureJsonResponse aplicado globalmente
- **Arquivo:** `backend/bootstrap/app.php` ou `app/Http/Kernel.php`
- **Ação:** Adicionar `\App\Http\Middleware\EnsureJsonResponse::class` ao grupo de middleware da API, garantindo que erros retornem sempre `Content-Type: application/json`.
- **Critério:** Qualquer erro em rotas `/api/*` retorna JSON.

### 2.3 GET /health completo
- **Arquivo:** `backend/routes/api.php` (rota `health`)
- **Ação:** Expandir para verificar: PostgreSQL (DB::connection()->getPdo()), Redis (Redis::ping()), Queue worker (opcional: verificar se Horizon está rodando ou fila processável), Reverb (opcional: tentar conexão TCP na porta configurada).
- **Retorno:** `{ status, services: { database, redis, queue?, reverb? }, version, timestamp }`.
- **Critério:** Health retorna status de cada dependência.

---

## BLOCO 3 — INFRAESTRUTURA DOCKER

### 3.1 .env.example completo
- **Arquivos:** `.env.example` (raiz) e `backend/.env.example`
- **Ação:** Incluir TODAS as variáveis: `CACHE_DATABASE=1`, `REDIS_QUEUE_DATABASE=2`, `REDIS_SESSION_DATABASE=3`, `VITE_API_URL`, `VITE_REVERB_*`, e quaisquer outras usadas em `config/`.
- **Critério:** `cp .env.example .env` + preencher valores mínimos = app inicia sem erro.

### 3.2 .gitignore correto
- **Arquivo:** `.gitignore`
- **Ação:** Remover prefixo `projeto/` se não existir; garantir `.env`, `storage/logs`, `vendor`, `node_modules` (backend e frontend), `frontend/dist`.
- **Critério:** `git status` não mostra arquivos sensíveis ou gerados.

### 3.3 Nginx: gzip e cache de assets
- **Arquivo:** `docker/nginx/nginx.conf`
- **Ação:**
  - Habilitar `gzip on;`, `gzip_types text/css application/javascript application/json;`, etc.
  - Para `location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$` adicionar `expires 1y; add_header Cache-Control "public, immutable";`.
- **Critério:** Response headers mostram `Content-Encoding: gzip` e `Cache-Control` em assets.

### 3.4 Redis multi-database (Horizon DB4)
- **Arquivos:** `config/database.php`, `config/horizon.php`, `.env.example`
- **Ação:** Adicionar conexão Redis `horizon` com `database => env('REDIS_HORIZON_DATABASE', 4)`, e configurar Horizon para usar. Documentar na .env.example.
- **Critério:** Cache=1, Sessions=2, Queue=3, Horizon=4 (ajustar ordem conforme convenção Laravel se necessário).

---

## BLOCO 4 — WEBSOCKET (REVERB)

### 4.1 Eventos broadcast
- **Eventos a criar/alterar:**
  - `MetricsRecalculated` (existente): implementar `ShouldBroadcast`, `broadcastOn` retornando `new PrivateChannel('dashboard.'.$userId)`, `broadcastAs('.metrics.updated')`, `broadcastWith()` retornando `['dashboard' => $this->dashboardData]` (payload mínimo, sem model Eloquent).
  - Criar `MetricsRecalculating` (spinner): broadcast antes do job iniciar; listener em `StudySessionCreated/Updated/Deleted` pode emitir ou o próprio job no início.
  - Criar `SessionStarted` (opcional): broadcast quando sessão inicia, com `elapsed_seconds` calculado no backend.
- **Listeners:** Criar `BroadcastMetricsUpdate` que escuta `MetricsRecalculated` e faz broadcast (ou o evento já implementa ShouldBroadcast e basta dispatch).
- **Ordem:** `.metrics.recalculating` → job executa → `.metrics.updated`.

### 4.2 Frontend: Laravel Echo + Pusher
- **Arquivo:** `frontend/package.json`
- **Ação:** `npm install laravel-echo pusher-js` (ou equivalente para Reverb).

- **Arquivo:** `frontend/src/composables/useWebSocket.ts`
- **Ação:** Descomentar e implementar Echo real. Configurar `Echo({ broadcaster: 'reverb', key: import.meta.env.VITE_REVERB_APP_KEY, wsHost: ..., wssPort: 443, ... })`. Conectar a `private(`dashboard.${userId}`)`. Listener `.metrics.updated` chama `analyticsStore.updateFromWebSocket(e.dashboard)`.

- **Arquivo:** `frontend/src/main.ts` ou `App.vue`
- **Ação:** Chamar `useWebSocket().connect(userId)` quando usuário autenticado; garantir `disconnect` no logout e `onUnmounted`.

### 4.3 RealtimeBadge.vue
- **Arquivo:** `frontend/src/components/RealtimeBadge.vue`
- **Ação:** Componente que usa `isConnected` do useWebSocket. Verde quando conectado, vermelho quando desconectado. Texto opcional "Tempo real" / "Desconectado".

### 4.4 Polling de fallback
- **Arquivo:** `frontend/src/composables/useDashboard.ts` (criar)
- **Ação:** Quando WebSocket desconectado por mais de N segundos, iniciar polling a cada 2 minutos para `fetchDashboard()`. Parar polling quando reconectar.

---

## BLOCO 5 — FRONTEND VUE: STORE E COMPOSABLES

### 5.1 useDashboard composable
- **Arquivo:** `frontend/src/composables/useDashboard.ts`
- **Ação:** Encapsular init (fetch inicial), WebSocket (connect ao montar, disconnect ao desmontar), polling de fallback, e `document.visibilitychange` (refetch quando aba volta ao foco com dados stale). Exportar `fetchDashboard(force?: boolean)`.
- **Critério:** DashboardView usa apenas `useDashboard()` e não chama store diretamente.

### 5.2 analytics.store: isFresh e fetchDashboard(force)
- **Arquivo:** `frontend/src/stores/analytics.store.ts`
- **Ação:**
  - Adicionar `lastFetchAt: ref<Date | null>(null)`.
  - `isFresh` computed: `lastFetchAt` + TTL 5 min.
  - `fetchDashboard(force = false)`: se `!force && isFresh`, não fazer request; senão, fetch e atualizar `lastFetchAt`.
- **Critério:** Segundo acesso ao dashboard em 5 min não dispara request.

### 5.3 technologies.store: searchLocal
- **Arquivo:** `frontend/src/stores/technologies.store.ts`
- **Ação:** Método `searchLocal(query: string)` que filtra tecnologias já carregadas no store, sem chamada à API. Usado no autocomplete.

---

## BLOCO 6 — COMPONENTES E UX

### 6.1 Skeleton loaders
- **Arquivo:** `frontend/src/components/ui/SkeletonLoader.vue` (criar)
- **Ação:** Componente reutilizável com animação pulse para estados de loading.
- **Arquivo:** `frontend/src/views/Dashboard/DashboardView.vue` e widgets
- **Ação:** Substituir "Carregando..." por `<SkeletonLoader />` ou skeletons específicos (ex: retângulos para KPIs, linhas para gráficos).
- **Critério:** Nenhum widget mostra spinner centralizado; cada um tem skeleton próprio.

### 6.2 ErrorCard.vue
- **Arquivo:** `frontend/src/components/ui/ErrorCard.vue`
- **Ação:** Exibe mensagem de erro e botão "Tentar novamente" que chama callback (ex: refetch). Usado quando fetch falha.

### 6.3 Toast global
- **Arquivo:** `frontend/src/composables/useToast.ts` e `frontend/src/components/ui/BaseToast.vue`
- **Ação:** Sistema de toast (success, error). `useToast().success(msg)`, `useToast().error(msg)`. Integrar no `api/client.ts` para 429 e erros de validação.
- **Critério:** Operações CRUD mostram toast de sucesso/erro.

### 6.4 TechDistributionWidget
- **Arquivo:** `frontend/src/components/dashboard/TechDistributionWidget.vue`
- **Ação:** Gráfico de pizza com top 6 tecnologias + fatia "Outros". Chart.js. Click na fatia navega para `/sessions?technology_id=X`.

### 6.5 HeatmapWidget
- **Arquivo:** `frontend/src/components/dashboard/HeatmapWidget.vue`
- **Ação:** SVG manual 53×7 (semanas × dias). Células coloridas por intensidade (minutos). Tooltip ao hover. Seletor de ano.

### 6.6 Chart.js: update sem re-montar
- **Arquivos:** `frontend/src/components/charts/LineChart.vue`, `PieChart.vue`
- **Ação:** `watch` em `chartData` chama `chart.update('active')` em vez de destruir e recriar. Garantir `chart?.destroy()` em `onUnmounted`.

### 6.7 Layout responsivo
- **Arquivo:** CSS do dashboard e grid
- **Ação:** `grid-template-columns`: 4 colunas desktop (>1024px), 2 tablet (640–1024px), 1 mobile (<640px).

---

## BLOCO 7 — BANCO DE DADOS E SEEDERS

### 7.1 DemoDataSeeder
- **Arquivo:** `backend/database/seeders/DemoDataSeeder.php`
- **Ação:** Criar usuário `demo@studytrack.local` (ou similar) e gerar 6 meses de sessões de estudo realistas (várias tecnologias, duração variada, dias consecutivos para streak).
- **DatabaseSeeder:** Chamar `DemoDataSeeder` após UserSeeder e TechnologySeeder.
- **Critério:** `php artisan migrate:fresh --seed` popula dados demonstráveis.

### 7.2 Índice UNIQUE em analytics.user_metrics
- **Verificar:** `analytics.user_metrics` já tem `user_id` como PK; UNIQUE implícito. Se houver necessidade de índice explícito, adicionar migration.
- **Critério:** Um registro por user_id.

---

## BLOCO 8 — TESTES

### 8.1 Teste cross-user (403)
- **Arquivo:** `backend/tests/Feature/Auth/AuthTest.php` ou novo `AuthorizationTest.php`
- **Ação:** UserA cria sessão; UserB tenta `GET /api/v1/study-sessions/{id}` e `PATCH` com token de UserB. Esperar 403.
- **Critério:** `php artisan test --filter=AuthorizationTest` passa.

### 8.2 Teste sessão concorrente (409)
- **Arquivo:** `backend/tests/Feature/StudySessions/StudySessionConcurrentTest.php`
- **Ação:** User inicia sessão (POST start). Tenta iniciar outra sem terminar. Esperar 409 com código `CONCURRENT_SESSION` (ou 422 se a validação no controller retornar antes do trigger).
- **Nota:** O trigger do banco pode lançar exceção; o handler deve retornar envelope JSON com código apropriado.
- **Critério:** 2ª sessão ativa retorna erro semântico.

### 8.3 Event::fake e Queue::fake em testes de sessão
- **Arquivo:** Testes de CRUD de sessões
- **Ação:** `Event::fake()`, `Queue::fake()` no setUp. Após criar sessão, `Event::assertDispatched(StudySessionCreated::class)`, `Queue::assertPushed(RecalculateMetricsJob::class)`.
- **Critério:** Testes isolam efeitos colaterais.

### 8.4 Teste de cache
- **Arquivo:** `backend/tests/Feature/Analytics/AnalyticsCacheTest.php`
- **Ação:** Criar sessão → verificar `Cache::tags(['analytics', 'user:'.$userId])->get('dashboard:'.$userId)` retorna null (invalidação). Segundo GET /dashboard → verificar cache HIT (opcional: mock ou Redis real).
- **Critério:** Cache invalidado após mutação; segundo request serve do cache.

### 8.5 Unit tests: UserMetricsAggregator
- **Arquivo:** `backend/tests/Unit/MetricsAggregatorTest.php`
- **Ação:** Testar streak zero, streak N dias, gap quebra streak, personal best preservado.
- **Critério:** Casos de borda cobertos.

### 8.6 Unit tests: RecalculateMetricsJob
- **Arquivo:** `backend/tests/Unit/RecalculateMetricsJobTest.php`
- **Ação:** Mock MetricsAggregator e AnalyticsService. Verificar que 3 aggregators são chamados, evento MetricsRecalculated é disparado, `failed()` loga com contexto.
- **Critério:** Job isolado, comportamento documentado.

### 8.7 Vitest: analytics.store
- **Arquivo:** `frontend/src/stores/__tests__/analytics.store.spec.ts`
- **Ação:** Testar `fetchDashboard`, `isFresh`, `updateFromWebSocket`, computed `streak`.
- **Critério:** `npm run test:run` passa.

### 8.8 Vitest: useSessionTimer
- **Arquivo:** `frontend/src/composables/__tests__/useSessionTimer.spec.ts`
- **Ação:** Testar init com elapsed do server, incremento do timer, cleanup no unmount.
- **Critério:** `npm run test:run` passa.

---

## BLOCO 9 — CI/CD

### 9.1 Backend CI: Pint + Larastan
- **Arquivo:** `.github/workflows/backend-ci.yml`
- **Ação:**
  - Adicionar step: `./vendor/bin/pint --test` (ou `composer pint --test`).
  - Adicionar step: `./vendor/bin/phpstan analyse --memory-limit=512M` (ou Larastan level 5).
- **Arquivos:** `backend/phpstan.neon` ou `phpstan.neon.dist` com level 5.
- **Critério:** Pipeline backend roda PHPUnit, Pint, Larastan; todos passam.

### 9.2 Frontend CI: Vitest + ESLint
- **Arquivo:** `.github/workflows/frontend-ci.yml`
- **Ação:**
  - Adicionar step: `npm run test:run` (Vitest).
  - Adicionar step: `npm run lint` (ESLint).
- **Critério:** Pipeline frontend roda type-check, Vitest, ESLint, build; todos passam.

### 9.3 Git hooks (opcional)
- **Ação:** `npx husky init`. Pre-commit: Pint (backend) + ESLint (frontend). Commit-msg: validar Conventional Commits.
- **Critério:** Commits quebrados são bloqueados localmente.

---

## BLOCO 10 — DOCUMENTAÇÃO E PORTFÓLIO

### 10.1 README completo
- **Arquivo:** `README.md`
- **Ação:**
  - Badges: CI (GitHub Actions), cobertura se houver.
  - Link de demo (quando deploy estiver pronto).
  - Seção "Sobre o projeto": o que faz, para quem, por quê.
  - Seção "Arquitetura": diagrama de componentes (Mermaid ou imagem) + decisões (schemas separados, event-driven, cache tags).
  - Tabela Stack: tecnologia | motivação.
  - Setup local: `make dev` em menos de 10 min, pré-requisitos (Docker, Git).
  - GIF ou vídeo curto do dashboard em tempo real.

### 10.2 Postman Collection
- **Ação:** Exportar collection com os 31 endpoints (ou os que existem) documentados. Colocar em `docs/` ou referenciar no README.
- **Critério:** Desenvolvedor importa e testa API sem código.

### 10.3 CHANGELOG ou releases
- **Arquivo:** `CHANGELOG.md`
- **Ação:** Manter histórico de versões (feat, fix, chore) em Conventional Commits.
- **Critério:** Evolução do projeto visível.

---

## BLOCO 11 — EXCEÇÕES DE DOMÍNIO E OUTROS

### 11.1 ConcurrentSessionException
- **Arquivo:** `backend/app/Exceptions/ConcurrentSessionException.php`
- **Ação:** Criar exceção com código `CONCURRENT_SESSION`. Handler trata e retorna 409 com envelope JSON.
- **Arquivo:** Onde a concorrência é detectada (trigger retorna erro SQL; pode precisar catch no repositório ou criar exceção quando O banco levantar).
- **Critério:** 2ª sessão ativa retorna 409 com código semântico.

### 11.2 Cache::lock em GET /analytics/dashboard
- **Arquivo:** `backend/app/Modules/Analytics/Services/AnalyticsService.php`
- **Ação:** Em `getDashboardData`, usar `Cache::lock('dashboard:'.$userId, 10)->get(fn () => ...)` para evitar cache stampede quando múltiplos workers fazem o mesmo request.
- **Critério:** Sob carga, apenas um processo reconstrói o cache.

### 11.3 Dockerfiles multi-stage (opcional)
- **Arquivos:** `docker/php/Dockerfile`, `docker/node/Dockerfile.frontend`
- **Ação:** Stage 1: build (composer install, npm run build). Stage 2: runtime (apenas artefatos necessários).
- **Critério:** Imagens menores, sem dev dependencies em produção.

---

## Ordem sugerida de execução

1. **Bloco 1** (Segurança) — impacto imediato em produção
2. **Bloco 2** (Rate limiting, health) — API mais robusta
3. **Bloco 3** (Infra) — ambiente estável
4. **Bloco 4** (WebSocket) — feature principal do dashboard
5. **Blocos 5 e 6** (Frontend) — UX e componentes
6. **Bloco 7** (Seeders) — demo utilizável
7. **Bloco 8** (Testes) — confiabilidade
8. **Bloco 9** (CI) — pipeline verde
9. **Blocos 10 e 11** — polish e documentação

---

## Comandos de verificação final

```bash
# Banco
php artisan migrate:fresh --seed

# Testes
php artisan test
cd frontend && npm run test:run

# Tipos
cd frontend && npm run type-check

# Lint
cd backend && ./vendor/bin/pint --test
cd frontend && npm run lint

# Build
cd frontend && npm run build

# Segurança (nenhuma credencial no repo)
git grep -r 'password' --include='*.php' backend/app/ backend/config/
```

---

*Prompt gerado a partir da verificação de requisitos do StudyTrack Pro. Use com um agente de código (Cursor, Codex, etc.) para implementação incremental.*
