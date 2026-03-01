# Sumário Completo - StudyTrackPro

Documentação exaustiva de todos os aspectos do projeto: tecnologias, arquitetura, testes, boas práticas, frontend, backend, proxy, banco de dados, Docker, fluxo da aplicação e scripts.

---

## 1. Visão Geral da Stack

| Camada | Tecnologia | Versão |
|--------|------------|--------|
| Frontend | Vue 3, TypeScript, Vite | 3.4+, 5.4, 5.x |
| Backend | Laravel 11, PHP | 11.0, 8.2+ |
| Banco de Dados | PostgreSQL | 16 |
| Cache/Filas | Redis | 7 |
| WebSocket | Laravel Reverb | 1.x |
| Containerização | Docker Compose | 3.9 |
| Proxy Reverso | Nginx | 1.25-alpine |

---

## 2. Estrutura de Diretórios

```
studyTrackPro/
├── backend/                 # API Laravel 11
├── frontend/                # SPA Vue 3
├── docker/                  # Configurações Docker
│   ├── nginx/              # Nginx (proxy reverso)
│   ├── php/                # Dockerfiles PHP
│   ├── node/               # Dockerfile frontend
│   ├── postgres/           # Scripts init PostgreSQL
│   └── redis/              # redis.conf
├── docs/                   # Postman collection, documentação
├── projeto/                # Docs técnicos (TXT)
├── .github/workflows/      # CI/CD
├── .husky/                 # Git hooks (pre-commit)
├── docker-compose.yml
├── docker-compose.dev.yml
├── Makefile
├── package.json            # Root: lint, Husky, Commitlint
├── commitlint.config.js
└── .env.example
```

---

## 3. Frontend

### 3.1 Tecnologias e Dependências

- **Framework:** Vue 3 (Composition API)
- **Linguagem:** TypeScript 5.4
- **Build:** Vite 5
- **Estado:** Pinia 2.1
- **Roteamento:** Vue Router 4.2
- **HTTP:** Axios 1.6
- **Gráficos:** Chart.js 4.4 + vue-chartjs 5.3
- **WebSocket:** Laravel Echo 2.3 + Pusher-js 8.4
- **Testes:** Vitest 1.0, Vue Test Utils 2.4, happy-dom 15.11

### 3.2 Estrutura de Pastas

```
frontend/src/
├── api/                    # Cliente HTTP
│   ├── client.ts           # Axios + interceptors (401, 429)
│   ├── endpoints.ts        # Constantes de endpoints
│   └── modules/
│       ├── auth.api.ts
│       ├── analytics.api.ts
│       ├── sessions.api.ts
│       └── technologies.api.ts
├── stores/                 # Pinia (auth, analytics, sessions, technologies, ui)
├── router/
│   ├── index.ts            # createRouter, rotas modulares
│   ├── guards.ts           # setupAuthGuard (requiresAuth, guest)
│   └── routes/             # auth, dashboard, sessions, technologies, profile
├── views/                  # Páginas
├── components/             # layout/, ui/
├── composables/            # useWebSocket.ts (Echo + canais privados)
├── types/                  # api, domain, websocket, chart
└── assets/styles/
```

### 3.3 Fluxo de Inicialização (main.ts)

1. `createApp(App)` + `createPinia()` + `use(router)`
2. `router.beforeEach(setupAuthGuard)` - verifica autenticação antes de cada rota
3. Se `requiresAuth` e não autenticado: redirect `/login`
4. Se `guest` e autenticado: redirect `/dashboard`
5. Se autenticado sem `user`: `authStore.fetchMe()` antes de prosseguir

### 3.4 Proxy (Vite)

- `vite.config.ts`: proxy `/api` → `PROXY_TARGET` (padrão `http://localhost`)
- `changeOrigin: true`
- Em Docker: `PROXY_TARGET=http://nginx`

### 3.5 WebSocket (useWebSocket)

- Laravel Echo (broadcaster: reverb)
- Autenticação: `Authorization: Bearer ${token}` em `authEndpoint`
- Canal privado: `dashboard.{userId}`
- Eventos: `.metrics.updated`, `.metrics.recalculating`, `.session.started`, `.session.ended`
- `analyticsStore.updateFromWebSocket()` e `sessionsStore.setActiveSession()`/`clearActiveSession()`

### 3.6 Scripts (frontend/package.json)

| Script | Comando |
|--------|---------|
| `dev` | `vite` (porta 5173) |
| `build` | `vue-tsc -b && vite build` |
| `preview` | `vite preview` |
| `test` | `vitest` (watch) |
| `test:run` | `vitest run` |
| `test:coverage` | `vitest run --coverage` (v8) |
| `type-check` | `vue-tsc --noEmit` |
| `lint` | `eslint . --fix` |

---

## 4. Backend

### 4.1 Tecnologias e Dependências

- **Framework:** Laravel 11
- **PHP:** 8.2+
- **Autenticação:** Laravel Sanctum 4.0
- **WebSocket:** Laravel Reverb 1.0
- **Filas:** Laravel Horizon (Redis)
- **Dev:** Pint, PHPUnit 11, Larastan, Faker, Mockery

### 4.2 Arquitetura (DDD-like)

- **Módulos:** Auth, StudySessions, Analytics, Technologies
- **Repository Pattern:** `Contracts/*RepositoryInterface` → `Eloquent*Repository`
- **Service Layer:** `AuthService`, `StudySessionService`, `TechnologyService`, `AnalyticsService`
- **Container:** `RepositoryServiceProvider` registra bindings (AppServiceProvider)

### 4.3 Estrutura de Pastas

```
backend/app/
├── Events/
│   ├── StudySession/       # SessionStarted, SessionEnded, Created, Updated, Deleted
│   └── Analytics/          # MetricsRecalculated
├── Jobs/                   # RecalculateMetricsJob, GenerateWeeklySummaryJob
├── Listeners/
│   ├── StudySession/       # Broadcast*, DispatchMetricsRecalculation, InvalidateSessionCache
│   └── Analytics/          # BroadcastMetricsUpdate
├── Http/
│   ├── Controllers/V1/     # Auth, Analytics, StudySession, Technology
│   ├── Middleware/         # EnsureJsonResponse, SetUserTimezone, LogApiRequests
│   ├── Requests/           # Form Requests (validação)
│   └── Resources/         # API Resources
├── Models/                 # User, Technology, StudySession, BaseModel, Analytics/*
├── Modules/                # Auth, StudySessions, Analytics, Technologies
├── Providers/              # App, Event, Repository
├── Traits/                 # HasUuid, HasApiResponse, HasAuditLog
└── Exceptions/Handler.php
```

### 4.4 Middleware Pipeline (bootstrap/app.php)

- API: `EnsureJsonResponse`, `EnsureFrontendRequestsAreStateful` (prepend)
- API: `SetUserTimezone`, `LogApiRequests` (append)
- Exceções: render JSON quando `expectsJson()`

### 4.5 Rate Limiting (AppServiceProvider)

| Nome | Limite | Por |
|------|--------|-----|
| `auth` | 5/min | IP |
| `sensitive` | 5/min | user_id ou IP |
| `search` | 120/min | user_id ou IP |
| `recalculate` | 2/min | user_id ou IP |
| Padrão | 60/min ou 30/min | user_id ou IP |

### 4.6 Eventos e Listeners

```
StudySessionCreated
├── InvalidateSessionCache
├── DispatchMetricsRecalculation → RecalculateMetricsJob
├── BroadcastSessionStarted
└── BroadcastMetricsRecalculating

StudySessionUpdated
├── InvalidateSessionCache
├── DispatchMetricsRecalculation → RecalculateMetricsJob
├── BroadcastMetricsRecalculating
└── BroadcastSessionEnded

StudySessionDeleted
├── InvalidateSessionCache
└── DispatchMetricsRecalculation → RecalculateMetricsJob

MetricsRecalculated
└── BroadcastMetricsUpdate
```

### 4.7 Horizon (Filas)

- Conexão Redis, filas: `default`, `metrics`
- `supervisor-metrics`: queue `metrics`, timeout 90s, maxProcesses 1 (local) / 3 (prod)

### 4.8 Scripts (composer.json)

- `post-autoload-dump`: package discovery
- `post-update-cmd`: publicar assets
- `post-root-package-install`: copiar .env.example
- `post-create-project-cmd`: `php artisan key:generate`

---

## 5. Banco de Dados

### 5.1 Schemas

- **`public` (transactional):** dados operacionais
- **`analytics`:** métricas pré-calculadas (CQRS)

### 5.2 Tabelas Transacionais (public)

| Tabela | Descrição Principal |
|--------|----------------------|
| `users` | UUID, name, email, password, timezone, locale, avatar_url |
| `technologies` | user_id, name, slug, color (#hex), icon, is_active; UNIQUE(user_id, name) |
| `study_sessions` | user_id, technology_id, started_at, ended_at, notes, mood (1-5), focus_score (1-10), duration_min (GENERATED) |
| `personal_access_tokens` | Sanctum |

### 5.3 Tabelas Analytics (schema analytics)

| Tabela | Descrição |
|--------|-----------|
| `user_metrics` | total_sessions, total_minutes, total_hours (GENERATED), avg_session_min, streak, last_session_at |
| `technology_metrics` | total_minutes, session_count, percentage_total, first/last_studied_at |
| `daily_minutes` | study_date, total_minutes, session_count, technologies (UUID[]), avg_mood |
| `weekly_summaries` | week_start, total_minutes, session_count, active_days |

### 5.4 Extensões e Triggers

- Extensões: `uuid-ossp`, `pg_trgm`
- Triggers: `fn_set_updated_at`, `fn_generate_slug`, `check_concurrent_sessions` (máx 1 sessão ativa por usuário), `validate_ended_at_trigger`
- Índices: B-tree (user_id, started_at DESC), BRIN (started_at), GIN (technologies.name), partial (ended_at IS NULL)

---

## 6. Proxy e Rede

### 6.1 Nginx (docker/nginx/nginx.conf)

| Location | Destino | Uso |
|----------|---------|-----|
| `/` | SPA Vue (`/var/www/html/public/frontend`) | HTML5 History Mode |
| `/api/` | php-fpm:9000 (FastCGI) | API Laravel |
| `/app/` | reverb:8080 | WebSocket (upgrade) |
| `~* \.(js|css|...)` | cache 1y | Assets estáticos |
| `location ~ \.php$` | php-fpm | Fallback PHP |

- Headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`
- Gzip habilitado

### 6.2 Fluxo de Requisição

- **Dev:** Cliente → Vite:5173 → proxy /api → Nginx:80 → php-fpm
- **Prod:** Cliente → Nginx:80 → /api php-fpm, /app reverb, / SPA
- **WebSocket:** Cliente → Nginx /app/ → Reverb:8080 (upgrade)

---

## 7. Docker

### 7.1 Serviços (docker-compose.yml)

| Serviço | Imagem/Build | Portas | Descrição |
|---------|--------------|--------|-----------|
| nginx | nginx:1.25-alpine | 80, 443 | Proxy reverso |
| php-fpm | docker/php | - | Laravel API |
| reverb | docker/php | 8080 (interno) | WebSocket |
| horizon | docker/php | - | Queue worker |
| scheduler | docker/php | - | schedule:work |
| node | docker/node (frontend) | 5173 | Vite dev server |
| postgres | postgres:16-alpine | 5432 | Banco de dados |
| redis | redis:7-alpine | 6379 | Cache, filas, sessões |

### 7.2 Dev Extra (docker-compose.dev.yml)

- **pgAdmin:** porta 5050 (admin@studytrack.local / admin)
- **Mailpit:** porta 8025 (e-mails)

### 7.3 Volumes

- `postgres_data`, `redis_data`

### 7.4 Redis Databases

- DB 1: Cache
- DB 2: Queue
- DB 3: Session

---

## 8. API REST

### 8.1 Base e Autenticação

- Base: `/api/v1`
- Broadcast auth: `/api/broadcasting/auth` (Sanctum)
- Health: `/api/health` e `/up` (Laravel)

### 8.2 Endpoints por Domínio

**Auth:** `POST /register`, `POST /login`, `GET /me`, `PUT /me`, `POST /change-password`, `POST /logout`, `GET /tokens`, `DELETE /tokens`

**Technologies:** `GET /`, `GET /{id}`, `GET /search`, `POST /`, `PUT /{id}`, `DELETE /{id}`

**Study Sessions:** `GET /`, `GET /{id}`, `GET /active`, `POST /start`, `POST /`, `PATCH /{id}/end`, `PUT /{id}`, `PATCH /{id}`, `DELETE /{id}`

**Analytics:** `GET /dashboard`, `GET /user-metrics`, `GET /tech-stats`, `GET /time-series`, `GET /weekly`, `GET /heatmap`, `POST /recalculate`

---

## 9. Testes

### 9.1 Backend (PHPUnit)

- Config: `phpunit.xml` - DB `studytrack_test`, cache array, queue sync
- **Feature:** Auth, StudySessions CRUD, Technologies CRUD, Analytics cache, Authorization, StudySessionConcurrent
- **Unit:** RecalculateMetricsJob, BroadcastMetricsRecalculating, StudySessionService, MetricsAggregator

### 9.2 Frontend (Vitest)

- happy-dom, coverage v8, globals
- Testes de stores e componentes

### 9.3 CI/CD (GitHub Actions)

- **Backend CI:** PHP 8.3, PostgreSQL 16, composer install, migrate, test, Pint, PHPStan
- **Frontend CI:** Node 20.19, npm ci, type-check, Vitest, ESLint, build

---

## 10. Scripts

### 10.1 Makefile

| Comando | Ação |
|---------|------|
| `make dev` | docker compose up -d (com dev) |
| `make stop` | docker compose down |
| `make build` | docker compose build |
| `make shell-php` | shell no php-fpm |
| `make shell-vue` | shell no node |
| `make test` | test-back + test-front |
| `make test-back` | cria DB teste + php artisan test |
| `make test-front` | npm run test:run |
| `make migrate` | php artisan migrate |
| `make seed` | php artisan db:seed |
| `make fresh` | migrate:fresh --seed |
| `make horizon` | php artisan horizon |
| `make pint` | formata PHP |
| `make lint` | ESLint frontend |
| `make logs` | docker compose logs -f |

### 10.2 Root package.json

| Script | Ação |
|--------|------|
| `prepare` | husky (setup hooks) |
| `lint` | ESLint no frontend |
| `lint:backend` | Pint --test no backend |
| `precommit` | Pint + ESLint + type-check |

### 10.3 Husky e Commitlint

- `.husky/pre-commit`: Pint (backend) + ESLint + type-check (frontend)
- `commitlint.config.js`: Conventional Commits, max 72 chars, tipos: feat, fix, docs, style, refactor, perf, test, build, ci, chore

---

## 11. Fluxo Detalhado da Aplicação (Lado Cliente)

1. **Inicialização:** main.ts carrega App + Pinia + Router. setupAuthGuard roda em cada navegação.
2. **Login:** POST /auth/login → authStore.setToken/setUser → redirect dashboard → useWebSocket.connect
3. **Dashboard:** GET /analytics/dashboard → analyticsStore → Chart.js. WebSocket escuta metrics.updated e session.started/ended
4. **Iniciar Sessão:** POST /study-sessions/start → evento SessionStarted → BroadcastSessionStarted → WebSocket .session.started → sessionsStore.setActiveSession
5. **Finalizar Sessão:** PATCH /study-sessions/{id}/end → BroadcastSessionEnded → sessionsStore.clearActiveSession
6. **Interceptors:** 401 → logout + redirect /login; 429 → toast erro

---

## 12. Boas Práticas Implementadas

- **Backend:** Repository Pattern, Service Layer, DTOs, Form Requests, Event-Driven, CQRS (analytics separado)
- **Frontend:** Composition API, Pinia stores modulares, TypeScript strict, API client com interceptors
- **DB:** Schemas separados, generated columns, constraints, triggers, índices adequados
- **Segurança:** Sanctum, rate limiting, headers Nginx, CORS
- **Qualidade:** Pint, ESLint, PHPStan, Vitest, PHPUnit
- **DevEx:** Docker, Makefile, Husky, Commitlint
