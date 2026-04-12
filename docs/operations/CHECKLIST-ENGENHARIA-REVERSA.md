# Checklist de Engenharia Reversa — StudyTrackPro

Guia para estudar o projeto e recriar cada parte à mão, em ordem sugerida (fundamentos → camadas → integração).

---

## Parte 1 — O que estudar (conceitos e padrões)

### 1.1 Arquitetura geral

- [ ] **Fluxo de dados:** Frontend → API (REST) → Controller → Service → Repository → Model/DB; e Event → Listener → Job/Broadcast.
- [ ] **Módulos do backend:** Auth, StudySessions, Technologies, Analytics (cada um com Service, DTOs, Repository com interface).
- [ ] **Event-driven:** Eventos no passado (ex.: `StudySessionCreated`), disparados pelo Service; Listeners para cache e jobs; Broadcast para Reverb.
- [ ] **Duplo schema no PostgreSQL:** `public` (transacional: users, technologies, study_sessions) e `analytics` (métricas agregadas).
- [ ] **Contrato API:** Resources Laravel ↔ tipos TypeScript em `frontend/src/types/`; payloads WebSocket em `websocket.types.ts`.

### 1.2 Backend (Laravel)

- [ ] **Controller thin:** Só recebe request, chama Service, retorna Resource/response; sem regra de negócio.
- [ ] **Form Request:** Validação em classes em `Http/Requests/` (Register, Store, Update, Export, etc.); nunca no controller.
- [ ] **Repository pattern:** Interface em `Contracts/`, implementação Eloquent; binding no `RepositoryServiceProvider`.
- [ ] **DTOs:** Objetos readonly para entrada/saída entre Controller e Service (ex.: `RegisterDTO`, `StudySessionDTO`).
- [ ] **Traits:** `HasUuid` em `BaseModel`; existem `HasAuditLog`, `HasCacheInvalidation`, `HasApiResponse` em `app/Traits/` — ver uso real em cada model/controller.
- [ ] **Rate limiting:** Limiters nomeados em `AppServiceProvider` (`login`, `register`, `search`, `sensitive`, `recalculate`, `export`, `health`); rotas em `routes/api.php` (incl. `throttle.sliding` em mutações de `study-sessions`).
- [ ] **Cache com tags:** Invalidação por tags (ex.: sessões do usuário) em Listeners.
- [ ] **Sanctum:** Tokens para SPA; rotas protegidas com `auth:sanctum`.
- [ ] **Reverb:** Canais privados em `channels.php` (`dashboard.{userId}`); eventos com payload definido para o frontend.

### 1.3 Frontend (Vue 3 + TypeScript)

- [ ] **Composition API + `<script setup>`:** Padrão em componentes e views.
- [ ] **Pinia:** Stores por domínio (auth, sessions, technologies, goals, analytics, notifications, ui); sem chamar API inexistente (Goals = localStorage).
- [ ] **Módulos API:** Uma pasta/fichário por domínio (auth.api, sessions.api, etc.); uso do `client` axios com interceptors (token, 401, 429).
- [ ] **Tipos:** `api.types.ts`, `domain.types.ts`, `websocket.types.ts`, etc.; alinhados aos Resources e eventos.
- [ ] **Design system:** Tokens em `variables.css` (cores, espaçamento, radius, sombras, tipografia, motion); uso em componentes, sem valores soltos.
- [ ] **Estrutura de pastas:** `ui/` (agnósticos), `layout/`, `features/` (por domínio), `views/`, `composables/`, `stores/`, `api/`, `types/`.
- [ ] **Router:** Guard de auth; layout raiz com filhos; `meta.title` para título da página.
- [ ] **WebSocket:** Composable `useWebSocket` (Echo, canal privado, listeners, atualização de stores, cleanup em `onUnmounted`).

### 1.4 Integração e qualidade

- [ ] **Fluxo ponta a ponta:** Ex.: “Iniciar sessão” → POST start → Event → Listener → Broadcast → frontend escuta e atualiza store/UI.
- [ ] **Checklists do projeto:** [CHECKLIST-E-PROMPTS.md](CHECKLIST-E-PROMPTS.md), [../../frontend/docs/CHECKLIST-FRONTEND.md](../../frontend/docs/CHECKLIST-FRONTEND.md); throttles na tabela do mesmo ficheiro.
- [ ] **Variáveis de ambiente:** [ENV-VARS.md](ENV-VARS.md), `backend/.env.example` e `frontend/.env.example`.

---

## Parte 2 — O que recriar (na prática)

Sugestão: recriar em um branch ou projeto paralelo, na ordem abaixo. Marque cada item ao concluir.

### 2.1 Fundação backend

- [ ] **Migrations (transacional):** Estudar ordem e conteúdo de `database/migrations/transactional/` (extensions, users, technologies, study_sessions, tokens, índices, triggers). Recriar uma migration “nova” (ex.: uma tabela auxiliar) seguindo o padrão.
- [ ] **Models:** `BaseModel` (UUID, audit), `User`, `Technology`, `StudySession`. Recriar um model enxuto com UUID e um relacionamento (ex.: Technology hasMany StudySessions).
- [ ] **Repository:** Interface + Eloquent (ex.: `TechnologyRepositoryInterface` + `EloquentTechnologyRepository`). Registrar no provider. Recriar um método `find(int|string)` e um `getAllForUser()`.
- [ ] **DTO:** Uma classe readonly (ex.: `TechnologyDTO` com id, name, slug, etc.). Recriar um DTO de criação (ex.: `StoreTechnologyDTO`).
- [ ] **Form Request:** Uma classe (ex.: `StoreTechnologyRequest`). Recriar uma validação com regras e mensagens.
- [ ] **Resource:** Uma classe (ex.: `TechnologyResource`) que formata o model para JSON. Recriar um Resource com conditional attributes.
- [ ] **Service:** Um método que usa Repository e retorna DTO/Resource (ex.: `TechnologyService::create`). Recriar um método “list” com filtro opcional.
- [ ] **Controller:** Uma rota GET e uma POST que chamam o Service e retornam Resource/JsonResponse. Recriar o par index/store para um recurso simples.
- [ ] **Rota + throttle:** Registrar no `api.php` com prefixo `v1`, middleware `auth:sanctum` e um throttle nomeado. Recriar uma rota protegida com throttle 60,1.

### 2.2 Eventos e jobs (backend)

- [ ] **Event:** Uma classe (ex.: `StudySessionCreated`) com payload mínimo. Recriar um evento “AlgoCriado” com um ID.
- [ ] **Listener:** Um listener que invalida cache (tag) ou faz dispatch de job. Recriar um listener que apenas loga ou invalida uma tag.
- [ ] **Job:** Um job que roda na fila (ex.: `RecalculateMetricsJob`). Recriar um job “DummyJob” que processa um ID e usa `ShouldBeUnique` se fizer sentido.
- [ ] **Registro:** `EventServiceProvider`: evento → listener; fila configurada (Horizon). Recriar o par evento-listener e rodar com `queue:work`.

### 2.3 WebSocket (backend)

- [ ] **Canal:** `routes/channels.php` — canal privado `dashboard.{userId}` com autorização por `$user->id`. Recriar a regra de autorização lendo o código atual.
- [ ] **Broadcast em listener:** Um listener que usa `broadcast(new SessionStarted(...))` para o canal do usuário. Recriar um evento broadcast simples e enviar um payload fixo.
- [ ] **Config:** `config/broadcasting.php` (reverb); variáveis REVERB_* no `.env`. Garantir que o health check inclua Reverb se for o caso.

### 2.4 Schema analytics (backend)

- [ ] **Migrations analytics:** Estrutura em `database/migrations/analytics/` (schema, tabelas de métricas). Estudar uma tabela (ex.: `daily_minutes`) e recriar uma migration nova no mesmo schema.
- [ ] **Models Analytics:** `Analytics/DailyMinutes`, etc., com `$connection`/schema. Recriar um model que aponta para o schema `analytics`.
- [ ] **AnalyticsService + repositório:** Como o service agrega e usa o repositório. Recriar um método “getLast7Days” que retorna dados do repositório.

### 2.5 Frontend — API e tipos

- [ ] **client axios:** `baseURL`, interceptor de request (token), interceptor de response (401 redirect, 429 toast). Recriar o `client.ts` e um helper `getApiErrorMessage`.
- [ ] **Tipos:** Um módulo de tipos (ex.: `Technology`, `StudySession`) espelhando a API. Recriar interfaces para um recurso (ex.: Technology + TechnologyForm).
- [ ] **Módulo API:** Um arquivo (ex.: `technologies.api.ts`) com funções que chamam o client (getAll, getById, create, update, delete). Recriar um módulo para um recurso mínimo.

### 2.6 Frontend — Store e composables

- [ ] **Store Pinia:** Estado, getters, actions que chamam o módulo API e tratam erro/loading. Recriar uma store mínima (ex.: list + fetchList).
- [ ] **Composable:** Um composable (ex.: `useTechnologies`) que usa a store e expõe dados + ações. Recriar um composable que chama uma action e retorna refs.
- [ ] **useWebSocket:** Conexão Echo, subscribe ao canal privado, listeners (session.started, metrics.updated, etc.), atualização de stores e cleanup. Recriar apenas o subscribe + um listener que atualiza um ref local.

### 2.7 Frontend — UI e design system

- [ ] **variables.css:** Cores, espaçamento, radius, sombras, tipografia, motion, tema escuro. Recriar um arquivo de tokens mínimo (5 cores, 3 espaçamentos, 1 radius).
- [ ] **Componente base:** Um componente em `ui/` (ex.: BaseButton) com props, slots, variantes. Recriar um botão com variante primary/secondary e tamanho.
- [ ] **Layout:** AppLayout, AppSidebar, estrutura de rotas com layout. Recriar um layout com sidebar e área de conteúdo.
- [ ] **Página:** Uma view que usa layout, store/composable, componentes ui e trata loading/erro/vazio. Recriar uma página “Lista de X” com tabela ou cards.

### 2.8 Frontend — Router e auth

- [ ] **Rotas:** Estrutura em `router/routes/` (auth, dashboard, sessions, etc.) e montagem no `index.ts`. Recriar um arquivo de rotas com duas rotas (lista + detalhe) e meta.title.
- [ ] **Guard:** `setupAuthGuard`: checagem de `requiresAuth`, redirecionamento para login, armazenamento de “redirect” após login. Recriar o guard lendo o atual.
- [ ] **Fluxo de login:** LoginView → auth.api.login → auth store (token + user) → redirect. Recriar o fluxo em uma tela mínima (form + chamada + redirect).

### 2.9 Integração ponta a ponta

- [ ] **CRUD completo:** Uma entidade (ex.: Technologies): list no frontend, criar, editar, excluir; backend com Controller + Service + Repository + Form Request + Resource. Recriar o fluxo para uma entidade nova “Tags” (só id e name) no backend e uma página no frontend.
- [ ] **Sessão de estudo:** “Start” no frontend → POST start → evento SessionStarted → broadcast → frontend recebe e atualiza (ex.: banner “sessão ativa”). Reproduzir o fluxo com um botão “Simular start” e um listener que mostra um toast.
- [ ] **Dashboard + analytics:** Chamada GET dashboard no frontend, uso dos dados (widgets/gráficos). Recriar um widget que chama `analytics.api.getDashboard()` e exibe um número.

### 2.10 Testes e documentação

- [ ] **Feature test (backend):** Um teste que chama uma rota API (auth, payload, status). Recriar um teste para GET list e um para POST create com validação.
- [ ] **Unit test (backend):** Um teste de Service ou de método de Repository. Recriar um teste que mocka o repository e verifica o retorno do service.
- [ ] **Coleção Postman:** Endpoints documentados com variáveis e exemplos. Adicionar um endpoint novo à coleção existente.
- [ ] **README / ENV:** Instruções de setup e lista de variáveis. Atualizar README com um passo “Rodar Reverb” e ENV com uma variável nova usada no checklist.

---

## Parte 3 — Ordem sugerida de estudo (por dia/semana)

| Fase | Foco | Itens do checklist |
|------|------|--------------------|
| 1 | Backend: modelos e acesso a dados | 2.1 (migrations, models, repository, DTO, form request, resource, service, controller, rota) |
| 2 | Backend: eventos e filas | 2.2 (event, listener, job, registro) |
| 3 | Backend: WebSocket e analytics | 2.3 e 2.4 |
| 4 | Frontend: rede e estado | 2.5 e 2.6 (client, tipos, módulo API, store, composable, useWebSocket) |
| 5 | Frontend: UI e rotas | 2.7 e 2.8 (variables, componente, layout, página, rotas, guard, login) |
| 6 | Integração e qualidade | 2.9 e 2.10 (CRUD E2E, fluxo sessão, dashboard, testes, Postman, docs) |

---

## Referências rápidas no repositório

- **Rotas API:** `backend/routes/api.php`
- **Canais WebSocket:** `backend/routes/channels.php`
- **Eventos/Listeners:** `backend/app/Events/`, `backend/app/Listeners/`, `backend/app/Providers/EventServiceProvider.php`
- **Módulos:** `backend/app/Modules/` (Auth, StudySessions, Technologies, Analytics)
- **Resources:** `backend/app/Http/Resources/`
- **Frontend API:** `frontend/src/api/` (client, modules, queryKeys)
- **Tipos:** `frontend/src/types/` (api.types.ts, websocket.types.ts)
- **Design tokens:** `frontend/src/assets/styles/variables.css`
- **Router:** `frontend/src/router/index.ts`, `frontend/src/router/guards.ts`, `frontend/src/router/routes/`
- **Checklists oficiais:** [CHECKLIST-E-PROMPTS.md](CHECKLIST-E-PROMPTS.md), [../../frontend/docs/CHECKLIST-FRONTEND.md](../../frontend/docs/CHECKLIST-FRONTEND.md)
- **Env:** [ENV-VARS.md](ENV-VARS.md), `backend/.env.example`, `frontend/.env.example`
- **Técnico:** [../technical/DOCUMENTACAO_TECNICA.md](../technical/DOCUMENTACAO_TECNICA.md)
