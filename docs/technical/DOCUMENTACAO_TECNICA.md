# StudyTrack Pro Technical Documentation

## 1. Purpose of This Document

This document consolidates, in a single place, the technical explanation of the `StudyTrack Pro` project based on the current state of the repository. The focus here is not marketing or quick onboarding: the proposal is to describe in depth how the system is organized, what technologies it uses, how components relate, what operational flows exist, how the infrastructure was built, and what attention points appear in the code and configuration.

The material was structured to serve at least four uses:

- architectural understanding of the system as a whole;
- technical onboarding acceleration;
- support for maintenance, refactoring, and auditing;
- reference for future evolutions and alignment between frontend, backend, and infrastructure.

## 2. Scope and Source of Information

The information in this document was derived from reading the repository itself, especially from the following files and areas:

- `README.md`
- `docs/README.md` and `docs/technical/*`
- `Makefile`
- `docker-compose.yml`
- `backend/README.md`
- `backend/composer.json`
- `frontend/README.md`
- `frontend/package.json`
- code structure in `backend/app`, `backend/routes`, `backend/config`, `backend/database`
- code structure in `frontend/src`
- CI files in `.github/workflows`
- hook configurations in `.husky`
- Docker configurations in `docker/`

When there is a discrepancy between existing documentation and observed implementation in the code, this document prioritizes the apparent behavior of the code and records the discrepancy as an attention point.

## 3. Product Overview

`StudyTrack Pro` is a full-stack platform for study and productivity tracking. The system allows users to log study sessions, associate these sessions with specific technologies, view consolidated indicators on a dashboard, view distributions by technology, activity heatmaps, and other derived data.

In terms of technical proposal, the project combines:

- a modern SPA in Vue 3 with TypeScript;
- a REST API in Laravel 11;
- transactional persistence in PostgreSQL;
- cache, queues, and pub/sub in Redis;
- asynchronous metrics processing via jobs;
- near real-time updates via WebSocket;
- local packaging and execution via Docker Compose.

The repository also demonstrates common concerns of real applications:

- separation of responsibilities between layers;
- use of queues for heavy work;
- containerized development environment;
- lint, tests, and automated type-check;
- CI workflow for frontend and backend;
- pre-commit hooks to reduce quality regression.

## 4. Repository Macro Structure

The project does not use Node workspaces, NX, Turborepo, or other package-oriented monorepo tools. Instead, it adopts a single repository that aggregates three major blocks:

### 4.1 Main Applications

- `backend/`: Laravel 11 application responsible for the API, authentication, persistence, queues, broadcasting, and central business logic.
- `frontend/`: Vue 3 application responsible for the web interface, navigation, local state management, API consumption, and user experience.

### 4.2 Infrastructure and Automation

- `docker/`: Dockerfiles, OpenResty (proxy), Redis, Postgres (image with PL/Lua when applicable) and auxiliary images.
- `docker-compose.yml`: starts the main stack.
- `docker-compose.dev.yml`: adds development utilities such as pgAdmin and Mailpit.
- `redis-scripts/`: Lua scripts (`job_dedup`, `sliding_window`, `streak_update`) mounted in PHP/Horizon/Reverb containers per compose.
- `Makefile`: automation layer for setup, execution, migrations, tests, and container access.

### 4.3 Governance and Quality

- `.github/workflows/`: backend, frontend, and image pipeline CI.
- `.husky/`: git hooks.
- `commitlint.config.js`: commit message pattern.
- `README.md`, `backend/README.md`, `frontend/README.md`, `docker/README.md`: existing documentation.

## 5. High-Level Architecture

From an architectural perspective, the system is a full-stack monolith separated by layers and domain context.

### 5.1 Summary View

1. The user interacts with the SPA in `frontend/`.
2. The SPA consumes the API in `backend/` via HTTP, generally under `/api/v1`.
3. The backend processes the request, persists data in PostgreSQL, and uses Redis for cache, queues, and real-time communication.
4. Relevant changes, such as creating, updating, or ending sessions, dispatch events.
5. Listeners and jobs update derived metrics and clear caches.
6. Broadcast events notify the frontend, which updates parts of the dashboard without a full reload.

### 5.2 Backend Architectural Style

The backend mixes characteristics of:

- traditional REST API;
- domain modularization;
- service layer pattern;
- repositories to encapsulate data access;
- event-driven flow for derived processes.

This means the project does not concentrate everything in controllers or models. Instead:

- controllers receive the request and delegate;
- form requests validate input;
- services concentrate business rules;
- repositories isolate queries and persistence;
- events/listeners/jobs handle side effects and post-processing.

### 5.3 Frontend Architectural Style

The frontend uses a classic SPA based on Vue 3, with:

- client-side routing via `vue-router`;
- global state via Pinia;
- server data cache and synchronization via TanStack Vue Query;
- reusable UI and layout components;
- composables for shared behavior;
- feature-based components for domain presentation rules.

## 6. Technology Stack

### 6.1 Frontend

- `Vue 3`: interface base.
- `TypeScript`: static typing.
- `Vite 5`: build, dev server, and frontend pipeline.
- `vue-router`: routing.
- `Pinia`: global state.
- `@tanstack/vue-query`: server data cache and synchronization.
- `@tanstack/vue-virtual`: long list virtualization where applicable.
- `Axios`: HTTP client.
- `jspdf`: client-side PDF export where implemented.
- `PrimeVue`: component library.
- `@primeuix/themes`: visual theme.
- `primeicons`: icons.
- `apexcharts` and `vue3-apexcharts`: main charts (includes heatmap via ApexCharts).
- `laravel-echo` and `pusher-js`: broadcasting/Reverb integration.
- `Zod`: schema validation at client points.
- `Vitest`, `happy-dom`, `@vue/test-utils`: testing.
- `ESLint`, `Prettier`, `vue-tsc`: quality and static verification.

### 6.2 Backend

- `PHP ^8.2`
- `Laravel 11`
- `Laravel Sanctum`: authentication tokens.
- `Laravel Reverb`: WebSocket server and integration.
- `Laravel Horizon`: queue supervision and execution.
- `PostgreSQL 16`: main persistence.
- `Redis 7`: cache, queues, sessions, and real-time support.
- `PHPUnit 11`: testing.
- `Larastan/PHPStan`: static analysis.
- `Laravel Pint`: formatting.

### 6.3 Infrastructure

- `Docker` and `Docker Compose`
- `Nginx`
- `pgAdmin` in extended development environment
- `Mailpit` in extended development environment
- `GitHub Actions` for CI/CD

## 7. Frontend in Detail

### 7.1 Role of the Frontend

The frontend is the user interaction layer. It does not replicate all of the backend's business logic, but has important responsibilities:

- managing the authenticated session in the browser;
- rendering the dashboard and domain views;
- routing and access control in the interface;
- consuming and caching data returned by the API;
- presenting charts and indicators;
- real-time interactions via WebSocket;
- specific local persistence, such as frontend-only goals.

### 7.2 Entry Points

The most important frontend bootstrap points are:

- `frontend/index.html`
- `frontend/src/main.ts`
- `frontend/src/App.vue`

In `index.html`, the application defines the base HTML document, `pt-BR` language, loads fonts, and prepares the app mount.

In `main.ts`, the application:

- creates the Vue instance;
- registers Pinia;
- registers Vue Query;
- registers the router;
- configures PrimeVue and UI services like toast and confirm dialog;
- applies initial theme based on `localStorage`;
- imports global styles.

In `App.vue`, the app mounts:

- `RouterView`;
- toast system;
- confirmation dialog;
- initialization of API error and visual notification integration.

### 7.3 Routing

Routing is centralized in `frontend/src/router/`.

Important files:

- `router/index.ts`
- `router/guards.ts`
- `router/routes/*.routes.ts`

#### 7.3.1 Route Strategy

The frontend organizes public and protected routes:

- public: login and registration;
- protected: dashboard, sessions, technologies, goals, export, settings, reports, help, profile.

Protected routes are grouped under the main layout. This allows reusing the sidebar, top bar, visual shell, and global integrations.

#### 7.3.2 Authentication Guard

The guard checks:

- if the route requires authentication;
- if a local token exists;
- if the user has been loaded;
- if `me` should be fetched before entering;
- if the authenticated user is trying to access a guest route.

There is a visible concern with avoiding redundant `fetchMe` calls, using a serialization mechanism to prevent bursts of identical requests during initial navigation.

### 7.4 State Management

The project combines two complementary mechanisms:

- `Pinia` for interface and session global state;
- `TanStack Query` for remote data and invalidation/cache.

#### 7.4.1 Main Stores

- `auth.store.ts`: token, user, login, register, logout, `fetchMe`, `localStorage` persistence.
- `ui.store.ts`: theme, sidebar, visual settings, and responsive behavior.
- `sessions.store.ts`: session data and running timer.
- `analytics.store.ts`: metrics, dashboard, time series, heatmap, and recalculation signaling.
- `technologies.store.ts`: technology list, search, and CRUD with local TTL.
- `goals.store.ts`: user goals persisted locally.
- `notifications.store.ts`: in-memory notifications.

#### 7.4.2 Vue Query Usage

Vue Query appears as a selective synchronization layer. Instead of putting all API consumption inside it, the project uses it in a focused way at higher-value points like dashboard, session list, and technologies.

This strategy produces a middle ground:

- Pinia remains useful for session, UI, and local composition state;
- Query handles cache, invalidation, and lifecycle of specific remote data.

The practical effect is positive, but requires discipline to avoid the same domain having data in more than one source and ending up out of sync.

### 7.5 Component Organization

The frontend separates components by responsibility layer.

#### 7.5.1 `components/layout`

Responsible for the application shell:

- `AppLayout`
- `AppSidebar`
- `AppTopBar`
- page wrappers

This layer handles structure, navigation, and global integrations, such as WebSocket initialization in the authenticated context.

#### 7.5.2 `components/ui`

Functions as a local design system, with wrappers and generic components:

- buttons;
- inputs;
- cards;
- modal;
- base table;
- empty states;
- skeletons;
- theme toggles;
- error components.

This approach reduces repetition and helps standardize visual interactions.

#### 7.5.3 `components/charts`

Encapsulates line, bar, pie, donut, and heatmap charts. This layer exists to prevent each view from needing to know chart library configuration details.

#### 7.5.4 `features/*`

Features group components, composables, and behaviors related to the domain:

- auth
- dashboard
- sessions
- technologies
- goals
- notifications

This structure brings the interface closer to the business language and facilitates evolution by functional context.

### 7.6 Views and Functional Modules

The views in `frontend/src/views/` represent navigation screens. Among the visible modules are:

- authentication;
- dashboard;
- sessions;
- technologies;
- goals;
- export;
- settings;
- reports;
- profile;
- help.

The session system includes, beyond listing, focus and technology detail modes. This suggests the frontend was designed not just for pure CRUD, but to support the study routine as a user experience.

### 7.7 HTTP Client

The central API client is in `frontend/src/api/client.ts`.

#### 7.7.1 Main Responsibilities

- define the API `baseURL`;
- inject Bearer token in requests;
- handle `401` globally;
- handle `429` with visual feedback;
- centralize error message interpretation.

#### 7.7.2 Error Behavior

When receiving `401`, the client:

- clears the local session;
- avoids loops on authentication pages;
- redirects to login.

When receiving `429`, the interface can display a toast, improving rate limit feedback.

#### 7.7.3 Endpoint Organization

API routes are encapsulated in:

- `api/endpoints.ts`
- `api/queryKeys.ts`
- `api/modules/auth.api.ts`
- `api/modules/sessions.api.ts`
- `api/modules/technologies.api.ts`
- `api/modules/analytics.api.ts`
- `api/modules/goals.api.ts`

An important detail is that `goals.api.ts` does not point to the backend; it implements local persistence. This confirms that the goals feature, in the current state of the repository, is frontend-only.

### 7.8 Forms and Validation

The frontend uses a combination of strategies:

- manual validations in forms;
- utility validation composables;
- `BaseInput` with error display support;
- Zod for parsing some API responses.

This strategy works but does not represent a unified model for all forms. In other words, the project has a validation base, but without a single dominant framework for the entire forms experience.

### 7.9 Styling and Design System

The frontend uses:

- global CSS in `assets/styles/main.css`;
- tokens in `assets/styles/variables.css`;
- PrimeVue as the component base;
- Aura theme with dark mode support;
- `data-theme` dataset for light/dark switching.

This combination indicates a hybrid design system:

- part comes from PrimeVue;
- part comes from internal components;
- part comes from local CSS tokens.

From a visual architecture perspective, this is positive, because the project does not depend solely on the library's default appearance and can standardize visual identity.

### 7.10 Real-Time

The frontend real-time is based on:

- `laravel-echo`
- `pusher-js`
- Reverb configuration
- private channel per user

The `useWebSocket` composable connects to the `dashboard.{userId}` channel and listens to events related to:

- metrics updates;
- recalculation start;
- session start;
- session end.

This allows the dashboard to respond to changes originated in the backend without constant polling as the main strategy. The project, however, provides fallback when WebSocket fails, which improves resilience.

### 7.11 Build, Tests, and Quality in Frontend

The main scripts in `frontend/package.json` cover:

- development: `npm run dev`
- build: `npm run build`
- preview: `npm run preview`
- tests: `npm run test`, `npm run test:run`, `npm run test:coverage`
- type-check: `npm run type-check`
- lint: `npm run lint`
- formatting: `npm run format`

Relevant points:

- the build uses `vue-tsc -b` before `vite build`, which strengthens type verification;
- lint is configured with `--fix`, including in the main script, which is convenient locally but less ideal in CI;
- the project has `vite.config.analyze.ts`, indicating concern with bundle analysis.

### 7.12 Technical Attention Points in Frontend

- coexistence of Pinia and Vue Query requires care to avoid dual source of truth;
- goals remain local and not synchronized with server;
- notifications are in memory, without dedicated backend;
- Zod only validates some flows;
- charts concentrate on ApexCharts; keeping theme configurations aligned prevents visual inconsistency;
- legacy composables in `src/composables/` still expose `@deprecated` pointing to equivalents in `features/*`.

## 8. Backend in Detail

### 8.1 Role of the Backend

The backend concentrates the central logic of the system. It is responsible for:

- authentication and token issuance;
- technology CRUD;
- study session CRUD and control;
- analytics calculation and delivery;
- rate limiting;
- health checks;
- broadcasting for real-time updates;
- job and listener orchestration;
- main domain persistence.

### 8.2 Entry and Bootstrap

The most important bootstrap points are:

- `backend/public/index.php`
- `backend/bootstrap/app.php`
- `backend/bootstrap/providers.php`

#### 8.2.1 `public/index.php`

It is the HTTP entry point of the Laravel application. It loads the framework, checks maintenance state, initializes the app, and delivers the request to the modern Laravel 11 kernel.

#### 8.2.2 `bootstrap/app.php`

In this project, this file concentrates important customizations:

- registration of web, api, console, and channels routes;
- API middleware configuration;
- preference for JSON rendering when appropriate;
- compatibility adjustments for process signals.

This shows that the bootstrap is not just the framework's minimum standard; it was used to consolidate cross-cutting behavior.

### 8.3 Domain Modularization

The `backend/app/Modules/` folder shows a clear cut by context:

- `Auth`
- `StudySessions`
- `Technologies`
- `Analytics`

Each module has subfolders like:

- `Services`
- `Repositories`
- `Contracts`
- `DTOs`

This division is important because it prevents the entire system from being oriented only to controllers and models. The code expresses the domain through modules, which helps maintenance scalability.

### 8.4 Controllers, Requests, and Resources

The main HTTP layers are in `backend/app/Http/`.

#### 8.4.1 Controllers

The versioned controllers in `Controllers/V1` organize the API's public surface. They are thin, which indicates a conscious decision to avoid concentrating business logic in the HTTP layer.

Main controllers in `Controllers/V1`:

- `AuthController`
- `StudySessionController`
- `TechnologyController`
- `AnalyticsController`

The `HealthController` is in `App\Http\Controllers\` (outside `V1`) and serves `GET /api/health` in `routes/api.php`. Laravel also exposes `GET /up` (configured in `bootstrap/app.php`) as the framework's minimal health check.

#### 8.4.2 Form Requests

Input validation is in `Http/Requests`, following the Laravel pattern. The benefit is separating:

- input parsing and validation;
- business rules;
- response serialization.

#### 8.4.3 API Resources

Resources help standardize response structure. Additionally, the project uses a response trait to form consistent JSON success and error contracts.

### 8.5 Middleware

Observed custom middlewares:

- `EnsureJsonResponse`
- `SetUserTimezone`
- `LogApiRequests`
- `SlidingWindowRateLimit` (alias `throttle.sliding` in `bootstrap/app.php`, used on session mutation routes; on Lua script failure, behavior controlled by `services.rate_limit.fail_open` — see middleware and `DOCUMENTACAO_TECNICA_LUA.md`)

#### 8.5.1 `EnsureJsonResponse`

Forces or favors JSON API behavior, reducing the risk of unexpected HTML responses in SPA client flows.

#### 8.5.2 `SetUserTimezone`

Adjusts the application timezone per authenticated user when the information is available. This is a relevant UX concern, as it avoids time inconsistencies in data serialization and reading.

#### 8.5.3 `LogApiRequests`

Logs request and response information, including duration. It is an important component of basic observability.

### 8.6 Routes

Main routes are in:

- `backend/routes/api.php`
- `backend/routes/web.php`
- `backend/routes/channels.php`
- `backend/routes/console.php`

#### 8.6.1 Versioned API and Health

Business routes are under `v1`, resulting in `GET/POST/... /api/v1/...` (Laravel's `api` prefix + `v1` group in `routes/api.php`).

The application's health endpoint is **outside** the `v1` group: `GET /api/health` (`HealthController`).

In `routes/web.php` there is also `GET /health` pointing to the same controller (useful when the API front responds at the host root). Additionally, `GET /up` is the default health check registered in the Laravel 11 bootstrap.

Functional groups in `/api/v1`:

- authentication;
- technologies;
- study sessions;
- analytics;

Authenticated broadcasting channels remain in `routes/channels.php`.

#### 8.6.2 Private Channels

The `dashboard.{userId}` channel ensures the user can only subscribe to their own flow. This is consistent with the real-time dashboard design per user.

#### 8.6.3 Scheduler

`routes/console.php` defines scheduled tasks, including weekly summary generation and old queue cleanup. This choice centralizes scheduled automations within the backend instead of depending only on external orchestration.

### 8.7 Services

Services represent the heart of business logic.

#### 8.7.1 AuthService and TokenService

The `AuthService` concentrates registration, login, current user, profile update, and password change. Sanctum token revocation and blacklisting have been centralized in `TokenService` (`App\Modules\Auth\Services\TokenService`), used in logout, mass revocation, login, and password change — also aligned with token validation at the edge (OpenResty), as documented in `DOCUMENTACAO_TECNICA_LUA.md`.

#### 8.7.2 StudySessionService

Controls the lifecycle of sessions:

- creation;
- listing;
- detail;
- update;
- ending;
- deletion;
- resource ownership verification.

It is also the point where sessions dispatch events that feed the entire metrics and updates pipeline.

#### 8.7.3 TechnologyService

Handles user technology CRUD. Instead of physically removing, it appears to adopt a deactivation logic, which preserves history and reduces the risk of breaking references.

#### 8.7.4 AnalyticsService

Delivers consolidated data for:

- dashboard;
- aggregated metrics;
- time series;
- weekly comparisons;
- heatmap;
- export;
- manual recalculation.

This module represents the analytical layer of the system and works in partnership with cache, repositories, jobs, and derived tables.

### 8.8 Repositories and Contracts

The backend uses interfaces and concrete implementations for data access. Dependency injection is centralized in `RepositoryServiceProvider`.

Benefits of this approach:

- reduces direct coupling between service and Eloquent;
- facilitates implementation substitution;
- improves testability;
- organizes queries in a consistent place.

The cost is more files and an additional abstraction layer, but the project appears to accept this cost in exchange for structural clarity.

### 8.9 Models and Persistence Layer

Main models:

- `User`
- `Technology`
- `StudySession`
- `BaseModel`

Observed characteristics:

- use of UUID as primary key;
- standardized date serialization;
- user relationships with technologies and sessions;
- use of support traits.

These choices favor interoperability and help prevent exposure of incremental IDs.

### 8.10 Database

#### 8.10.1 Technology and Strategy

The system uses PostgreSQL 16 with an important architectural idea: schema separation.

#### 8.10.2 `public` Schema

Concentrates main transactional data, such as:

- users;
- technologies;
- study sessions;
- personal tokens;
- structures related to daily application usage.

#### 8.10.3 `analytics` Schema

Concentrates derived and aggregated data, such as:

- user metrics;
- per-technology metrics;
- daily minutes;
- weekly summaries.

This separation is valuable because it better isolates what is transactional from what is analytical. In practice, this brings the design closer to a CQRS-inspired strategy, even without adopting full formal CQRS.

#### 8.10.4 Migrations

The migrations are not limited to a single directory. The project loads separate sets, including:

- standard migrations;
- transactional migrations;
- analytics migrations.

This reinforces the idea of organization by data type and responsibility.

#### 8.10.5 Integrity Rules

From what the code indicates, the database also participates in ensuring consistency, with functions, triggers, indexes, and constraints. An important example is the protection against more than one simultaneous active session for the same user.

### 8.11 Authentication and Authorization

#### 8.11.1 Authentication

The system uses `Laravel Sanctum` with personal tokens. Protected routes require `auth:sanctum`.

Main flows:

- registration;
- login;
- current user;
- logout;
- token revocation;
- password change.

#### 8.11.2 Authorization

There is no strong evidence of extensive use of `Policies` as the main mechanism. Instead, a significant part of authorization occurs imperatively in services or queries filtered by `user_id`.

This works, but generates two maintenance effects:

- ownership logic is scattered;
- error semantics may vary between modules.

Indeed, an observed point is the difference between `403` and `404` responses depending on the resource type and module.

### 8.12 Events, Listeners, and Jobs

This is one of the most interesting aspects of the project.

#### 8.12.1 Events

Events represent important state changes, especially in study sessions and metrics.

#### 8.12.2 Listeners

Listeners react to these events to:

- invalidate cache;
- trigger metrics recalculation;
- trigger broadcast;
- couple secondary flows without polluting the main service.

#### 8.12.3 Jobs

Observed jobs include:

- `RecalculateMetricsJob`
- `GenerateWeeklySummaryJob`

The `RecalculateMetricsJob` is particularly central because it translates transactional changes into analytics table updates. The use of short delay suggests an attempt to batch nearby changes and reduce excessive recalculation.

#### 8.12.4 Architectural Value of This Layer

This architecture produces clear benefits:

- lower response time for user operations;
- decoupling between write path and derived processing;
- ability to evolve analytics without rewriting the main HTTP flow;
- better real-time support.

### 8.13 Cache

The project uses Redis with tagged cache. This is relevant because:

- dashboards and metrics are often expensive to recalculate;
- per-user invalidation is simpler when keys are grouped by tags;
- the API can respond with lower cost on repeated reads.

This design indicates concern with performance from early on, which makes sense in a domain with frequent aggregations and visualizations.

### 8.14 Health Checks and Observability

The `HealthController` tests dependencies such as:

- database;
- Redis;
- queue;
- WebSocket endpoint or connectivity.

This type of endpoint helps in:

- operational diagnostics;
- container health checks;
- proxy or orchestrator integration.

Combined with the log middleware and error logs in jobs, the project has an initial layer of operational observability.

### 8.15 Exception Handling

The project has a custom `Handler` for JSON responses. Handled cases include:

- validation error;
- authentication;
- authorization;
- model not found;
- session concurrency;
- custom API exception;
- certain database errors;
- rate limit;
- internal error fallback.

This improves the predictability of the API's response contract and helps the frontend interpret failures more consistently.

### 8.16 Rate Limiting

Named limiters defined in `AppServiceProvider` (`RateLimiter::for`):

| Name | Behavior (Reference) |
|------|----------------------|
| `login` | 3 req/min per IP |
| `register` | 5 req/min per IP |
| `sensitive` | 5 req/min per authenticated user (or IP) |
| `search` | 120 req/min per user (or IP) |
| `recalculate` | 2 req/min per user (or IP) |
| `export` | 30 req/min per user (or IP) |
| `health` | 300 req/min per IP |

Authenticated read routes use `throttle:60,1` (60 req/min). The generic write group in `api.php` uses `throttle:30,1` (30 req/min) where applicable.

Study session routes (`start`, `end`, `store`, `update`, `destroy`) additionally use `throttle.sliding` (`SlidingWindowRateLimit` middleware) with per-route limits defined in `routes/api.php`, supported by `redis-scripts/sliding_window.lua`.

The source of truth is `backend/app/Providers/AppServiceProvider.php` and `backend/routes/api.php`.

### 8.17 Tests and Quality in Backend

The backend has:

- PHPUnit;
- Larastan/PHPStan;
- Pint.

Observed coverage (structure in `backend/tests`):

- Feature: authentication, sessions, analytics, security (rate limit, injection), JSON contracts (`Feature/Contract`), health, Lua (dedup, sliding window, streak), exceptions;
- Unit: events, listeners, jobs, middleware;
- PHPUnit + Larastan + Pint in CI (`backend-ci.yml`).

This distribution suggests focus on main domain flows, although it is not possible to assert total percentage coverage solely from the structure.

### 8.18 Technical Attention Points in Backend

- authorization distributed across services/queries instead of centralized in policies;
- inconsistent semantics between `403` and `404` for ownership;
- more than one health endpoint (`/up`, `/api/health`, `/health` in web) — useful for distinct probes, but requires clarity in runbooks;
- Horizon panel dependent on `web` context, which may require a specific access flow;
- need to ensure sensitive files and local artifacts are not improperly versioned.

## 9. Infrastructure and Operations

### 9.1 Docker Compose

The `docker-compose.yml` file starts the main stack with services:

- `nginx`
- `php-fpm`
- `reverb`
- `horizon`
- `scheduler`
- `node`
- `postgres`
- `redis`

#### 9.1.1 Nginx

Acts as reverse proxy and public entry point. Routes to:

- Laravel API;
- static frontend;
- WebSocket;
- Horizon;
- health endpoint.

It also defines gzip and security headers, which shows initial concern with delivery and external exposure.

#### 9.1.2 PHP-FPM

Laravel application container for HTTP handling via Nginx.

#### 9.1.3 Reverb

Dedicated process for the WebSocket server.

#### 9.1.4 Horizon

Container dedicated to queue processing and supervision.

#### 9.1.5 Scheduler

Runs `schedule:work`, allowing Laravel's scheduled tasks to be processed continuously.

#### 9.1.6 Node

Frontend development container. It exposes port 5173 and runs `npm install` followed by `npm run dev`. It is important to note that this container is clearly oriented to the development environment and not to the final stable frontend delivery in production.

#### 9.1.7 Postgres

Main database, without public port exposure in the base compose, which improves default local security.

#### 9.1.8 Redis

Cache and infrastructure service, also without public exposure by default.

### 9.2 Expanded Dev Environment

`docker-compose.dev.yml` adds auxiliary tools such as:

- `pgAdmin`
- `Mailpit`

This facilitates debugging and operations in the local environment.

### 9.3 Makefile

The `Makefile` encapsulates common tasks:

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

This is important because it standardizes commands and reduces the developer's operational memory dependency.

### 9.4 CI/CD

The project has three main workflows:

#### 9.4.1 `backend-ci.yml`

Executes installation, migrations, tests, Pint, and PHPStan.

#### 9.4.2 `frontend-ci.yml`

Executes installation, type-check, tests, lint, and build.

#### 9.4.3 `deploy.yml`

Builds Docker images for backend and frontend and publishes them, with a structure prepared for automated deploy evolution.

### 9.5 Git Hooks and Commit Pattern

In `.husky` and `commitlint.config.js`, the repository standardizes:

- commit message validation;
- pre-commit check execution.

In pre-commit, the flow mixes backend and frontend verification. This helps prevent commits with trivial format or consistency issues.

## 10. Important System Flows

### 10.1 Authentication Flow

1. The user sends credentials through the SPA.
2. The frontend calls the login endpoint.
3. The backend validates and generates a Sanctum token.
4. The frontend persists the token and basic user data.
5. Guards begin to release authenticated routes.
6. The HTTP client adds Bearer token to subsequent requests.

### 10.2 Session Creation/Ending Flow

1. The user creates or starts a session through the frontend.
2. The backend persists the session.
3. The session module dispatches a domain event.
4. Listeners invalidate cache and schedule metrics recalculation.
5. The job recalculates aggregates.
6. The backend publishes an updated metrics event.
7. The frontend receives the event and updates the visible dashboard/state.

### 10.3 Analytics Flow

1. The frontend requests the dashboard or other analytical views.
2. The backend queries cache or the analytics repository.
3. If necessary, aggregated data is read from the `analytics` schema.
4. The response returns with a consolidated payload for UI consumption.

### 10.4 Export Flow

1. The user requests an export with a period.
2. The backend queries analytical views and returns exportable JSON.
3. The frontend provides the download/consumption experience.

## 11. Conceptual Data Model

Even without listing all database columns and constraints, the central domain can be understood through these entities:

### 11.1 User

Represents the account owner and the study timeline owner.

Apparent relationships:

- a user has many technologies;
- a user has many sessions;
- a user has derived metrics;
- a user can have many access tokens.

### 11.2 Technology

Represents a categorization axis of study, such as language, framework, tool, or topic. It is used both in user CRUD and in per-technology metrics composition.

### 11.3 Study Session

Represents a unit of studied time. It can be active or ended, is associated with a user and a technology, and serves as the base transactional event for analytics.

### 11.4 Derived Metrics

Represent consolidated views of usage:

- per-user totals;
- per-technology distribution;
- daily minutes;
- weekly summary;
- time series and heatmaps.

These structures do not replace sessions; they exist to accelerate reading, dashboard, and reports.

## 12. Security, Resilience, and Operations

### 12.1 Observed Positive Points

- token authentication on backend;
- private channels for real-time;
- Redis and Postgres not publicly exposed in base compose;
- standardized JSON exception handling;
- rate limiting by functional group;
- API and job logs;
- healthcheck for critical components;
- separation of synchronous and asynchronous loads.

### 12.2 Points That Require Hardening or Review

- consolidate authorization model, preferably with uniform semantics;
- review Horizon access strategy in different environments;
- evaluate whether goals should remain frontend-only;
- check if README links point to current paths (index in `docs/README.md`).

## 13. Documented Divergences and Gaps

Points to keep synchronized between code and text:

- goals as a product feature with persistence only in the frontend (see `docs/operations/GOALS-FRONTEND-ONLY.md`);
- scattered documentation in `docs/`, package READMEs, and `frontend/docs/` — the central index is `docs/README.md`.

This file consolidates the technical view; details on Lua, HTTP edge, and Postgres are in `DOCUMENTACAO_TECNICA_LUA.md` in the same folder.

## 14. General Technical Project Assessment

In terms of structural maturity, the project shows a level above basic for a portfolio or growing product. The strongest signs of this are:

- clear separation between domains;
- use of service and repository layers;
- analytics decoupled from the main write path;
- cache usage with user-oriented invalidation;
- real-time communication via events;
- containerized development stack;
- CI for frontend and backend;
- concern with typing, lint, and tests.

At the same time, there are still typical signs of an evolving system:

- documentation inconsistencies;
- coexistence of approaches in some frontend layers;
- authorization not yet fully centralized;
- goals feature still local;
- some infrastructure and deploy components with a prepared base character, but not necessarily finalized for full productive operation.

## 15. Documentation Evolution Recommendations

As next documentation steps, it would make sense to create or consolidate:

- a context and container architecture diagram;
- a data model document with tables and relationships;
- a system event catalog;
- a deploy/operations guide actually synchronized with the current repository;
- an ownership and authorization matrix per endpoint;
- an API contract specification based on actual behavior.

## 16. Conclusion

`StudyTrack Pro` is a well-structured full-stack system with consistent separation between interface, API, persistence, jobs, and real-time. The project was designed to log study, derive metrics, and present rich visualizations, sustaining this experience with a modern technical base: Vue 3 on the frontend, Laravel 11 on the backend, PostgreSQL for data, Redis for support infrastructure, and Docker for local standardization.

The most evident technical value of the project lies in the combination of:

- clear domain;
- modularized backend;
- decoupled analytics pipeline;
- frontend organized by features;
- real-time update support;
- real concerns with quality, observability, and operations.

As a reference document, this file should be kept aligned whenever there are relevant changes in architecture, infrastructure, data model, API contracts, or execution strategy.

## 17. Complementary Lua Integration Document

Recent Redis Lua, OpenResty, and PL/Lua changes were documented separately in:

- `docs/technical/DOCUMENTACAO_TECNICA_LUA.md`

This complementary file details:

- added Lua scripts;
- Laravel and Redis integrations;
- edge behavior in OpenResty;
- PL/Lua trigger in PostgreSQL;
- Docker and compose adjustments;
- executed tests and validations;
- security concerns and residual risks.
