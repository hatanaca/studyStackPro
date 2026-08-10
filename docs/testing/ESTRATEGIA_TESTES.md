# Complete Testing Strategy — StudyTrack Pro

## Summary

1. [Strategy Overview](#1-strategy-overview)
2. [System Area Mapping](#2-system-area-mapping)
3. [Testing Strategy by Layer](#3-testing-strategy-by-layer)
4. [Detailed Functional Coverage](#4-detailed-functional-coverage)
5. [Security Coverage](#5-security-coverage)
6. [Integration and Consistency Coverage](#6-integration-and-consistency-coverage)
7. [Quality and Regression Coverage](#7-quality-and-regression-coverage)
8. [Coverage Matrix by Module](#8-coverage-matrix-by-module)
9. [Prioritization](#9-prioritization)
10. [Practical Implementation Suggestion](#10-practical-implementation-suggestion)
11. [Automation Plan](#11-automation-plan)
12. [CI/CD Execution Plan](#12-cicd-execution-plan)
13. [Uncovered Risks](#13-uncovered-risks)
14. [Final Recommendations](#14-final-recommendations)

---

## 1. Strategy Overview

### 1.1 Principles

The StudyTrack Pro testing strategy follows the test pyramid adapted for a full-stack event-driven application:

```
              /  E2E  \             <- few, slow, high confidence value
             / Contract \           <- validate API/frontend boundaries
            / Integration  \        <- end-to-end business flows in backend
           /   Unitaries    \       <- wide base, fast, isolated
```

The central focus is **ensuring that critical end-user flows work reliably**, while detecting regressions quickly and protecting security boundaries.

### 1.2 Scope

| Dimension | Coverage |
|---|---|
| Backend (Laravel) | Unit, integration, contract, security, resilience |
| Frontend (Vue) | Unit, component, store, composable, snapshot |
| Full-stack integration | E2E, API contract, type consistency |
| Database | Migrations, triggers, constraints, analytics schema |
| Cache (Redis) | Invalidation, TTL, stampede, tags |
| Queues/Jobs | Execution, retry, idempotency, Lua dedup |
| WebSocket/Realtime | Channel authorization, broadcast, fallback |
| Infrastructure | Health check, Docker, Nginx routing |
| Critical UX | Login flows, study session, dashboard |

### 1.3 Coverage Goals

| Layer | Minimum Goal | Ideal Goal |
|---|---|---|
| Backend — services | 90% | 95% |
| Backend — controllers (feature) | 85% | 90% |
| Backend — jobs/listeners | 80% | 90% |
| Frontend — stores | 85% | 90% |
| Frontend — composables | 80% | 90% |
| Frontend — critical components | 70% | 80% |
| E2E — critical flows | 100% of the 8 main flows | — |

> **Note:** The goals in the table above are **engineering objectives** (quality roadmap). The **current CI does not fail** pull requests for minimum coverage nor require MSW/Playwright; see section **1.4** below.

### 1.4 Current CI State (Repository)

Actual files: [.github/workflows/backend-ci.yml](../../.github/workflows/backend-ci.yml) and [.github/workflows/frontend-ci.yml](../../.github/workflows/frontend-ci.yml).

**Backend (job `backend`, Ubuntu):**

- Services: PostgreSQL 16, Redis 7 (ports on runner).
- PHP **8.4** on runner (`composer.json` requires `^8.2`; locally can use 8.2+).
- `composer install` in `backend/`, `cp .env.example .env`, `key:generate`, `migrate --force`.
- `php artisan test --coverage-clover=coverage.xml` — generates coverage and **fails if tests fail**; **no** gate configured for minimum percentage.
- `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse`.

**Frontend (job `frontend`, Ubuntu):**

- Node **20.19**, `npm ci` in `frontend/`.
- `npm run type-check`, `npm run test:run`, `npm run lint`, `npm run build`.
- **No** mandatory coverage report in CI nor percentage threshold.

**Not present in CI today (planning / conceptual section 12):** MSW, Playwright/Cypress, dedicated contract job, E2E against Docker Compose, automatic failure for coverage < 80%/70%.

---

## 2. System Area Mapping

### 2.1 Frontend

| Area | Key Files | Risk |
|---|---|---|
| Auth store + API | `auth.store.ts`, `auth.api.ts`, `LoginForm.vue`, `RegisterForm.vue` | Access loss, 401 loop |
| Router + Guards | `guards.ts`, `router/index.ts`, 10 route files | Unauthorized access, infinite redirect |
| Sessions (CRUD + timer) | `sessions.store.ts`, `sessions.api.ts`, `SessionsView.vue`, `SessionFocusView.vue` | Wrong timer, session won't end |
| Technologies | `technologies.store.ts`, `technologies.api.ts`, `TechnologiesView.vue` | Broken CRUD, desync |
| Analytics/Dashboard | `analytics.store.ts`, `analytics.api.ts`, `DashboardView.vue`, charts/* | Empty dashboard, wrong data |
| Goals (local-only) | `goals.store.ts`, `goals.api.ts` (localStorage) | Data loss, no sync |
| WebSocket | `useWebSocket.ts`, `websocket.types.ts` | Dashboard won't update |
| HTTP client | `client.ts` | Broken interceptors, 401 loop |
| UI components | 32 components in `components/ui/` | Visual regression |
| Charts | 5 components in `components/charts/` | Empty chart, wrong config |

### 2.2 Backend

| Area | Key Files | Risk |
|---|---|---|
| Auth (register/login/logout/password/tokens) | `AuthController`, `AuthService`, `TokenService` | Unauthorized access, token leak |
| Study Sessions (CRUD + start/end/active) | `StudySessionController`, `StudySessionService`, repository | Duplicate session, data loss |
| Technologies (CRUD + search + deactivate) | `TechnologyController`, `TechnologyService`, repository | Inconsistent CRUD |
| Analytics (dashboard/metrics/heatmap/export/recalc) | `AnalyticsController`, `AnalyticsService`, repository, `MetricsAggregator` | Wrong metrics, stale cache |
| Events/Listeners | 7 events, 7 listeners | Broken pipeline, stale metrics |
| Jobs | `RecalculateMetricsJob`, `GenerateWeeklySummaryJob` | Recalc fails, stale metrics |
| Middleware | `EnsureJsonResponse`, `SetUserTimezone`, `LogApiRequests`, `SlidingWindowRateLimit` | Failed rate limit, wrong timezone |
| Exception Handler | `Handler.php` | Inconsistent response, stack trace leak |
| Health Check | `HealthController` | False positive, degraded service |
| Lua Scripts | `sliding_window.lua`, `streak_update.lua`, `job_dedup.lua` | Rate limit bypass, wrong streak |

### 2.3 Database

| Area | Risk |
|---|---|
| `public` schema — transactional tables | Constraint violation, migration failure |
| `analytics` schema — derived tables | Desync with transactional data |
| Triggers (single active session) | Concurrency bypass |
| Functions and indexes (GIN, composite) | Degraded performance |
| UUIDs as PK | Theoretical collision, invalid format |

### 2.4 Cache (Redis)

| Area | Risk |
|---|---|
| Tagged cache (`analytics`, `sessions`, `user:{id}`) | Tags not invalidated |
| Dashboard lock (stampede prevention) | Deadlock, eternal lock |
| TTLs (5min dashboard, 15min time-series, 1h heatmap) | Prolonged stale data |

### 2.5 Queues/Jobs

| Area | Risk |
|---|---|
| `metrics` queue | Job not processed |
| `ShouldBeUnique` (RecalculateMetricsJob) | Dedup fails, duplicate recalc |
| Retry with backoff (30s, 60s, 120s) | Abandoned job, infinite retry |
| Lua dedup (`job_dedup.lua`) | Lua unavailable, uncontrolled fail-open |

### 2.6 WebSocket/Realtime

| Area | Risk |
|---|---|
| Private channel `dashboard.{userId}` | Cross-user leak |
| Events: `.metrics.updated`, `.metrics.recalculating`, `.session.started`, `.session.ended` | Event doesn't arrive, wrong payload |
| Fallback timer (45s) | Eternal spinner if WebSocket drops |
| Reconnection | Dashboard freezes |

### 2.7 Infrastructure

| Area | Risk |
|---|---|
| Docker Compose (8 services) | Container won't start, port conflict |
| Nginx routing (API, frontend, WebSocket, Horizon) | Wrong route, 502 |
| Health endpoint | Degraded not detected |
| Migrations in 3 directories | Migration executed out of order |

---

## 3. Testing Strategy by Layer

### 3.1 Unit Tests

**Objective:** Validate isolated logic units without external dependencies.

**Backend (PHPUnit):**

| What to Test | Rationale |
|---|---|
| `AuthService.register()` — password hashing, creation via repository | Registration logic without HTTP |
| `AuthService.login()` — valid/invalid credentials, old token revocation | Critical authentication flow |
| `AuthService.changePassword()` — hash check, revocation, false return | Password change security |
| `StudySessionService.findForUser()` — ownership check, ModelNotFoundException | Service-level authorization |
| `StudySessionService.create()` — DTO mapping, event dispatched | Creation correctness |
| `StudySessionService.delete()` — event with correct data before delete | Operation order |
| `TechnologyService.deactivate()` — soft delete, not hard delete | History preservation |
| `AnalyticsService.buildDashboardData()` — payload composition | Response structure |
| `MetricsAggregator.recalculateUserMetrics()` — totals, streaks calculation | Mathematical correctness |
| `MetricsAggregator.recalculateTechnologyMetrics()` — grouping by tech | Correct aggregation |
| `MetricsAggregator.recalculateDailyMinutes()` — timezone, daily grouping | Timezone affects result |
| `DispatchMetricsRecalculation.handle()` — relevant field filtering on update | Avoids unnecessary recalc |
| `InvalidateSessionCache.handle()` — correct tag flush | Cache cleared after CRUD |
| `RecalculateMetricsJob` — transaction, flush, broadcast | Complete job pipeline |
| `TokenService.revoke()` / `revokeMany()` — count, cleanup | Token management |
| DTOs — construction, defaults, toArray | Internal data contract |
| `StudySessionFilterDTO.fromArray()` — query param parsing | Listing filters |

**Frontend (Vitest):**

| What to Test | Rationale |
|---|---|
| `auth.store` — login persists token in localStorage, logout clears, clearSessionLocally | Authentication state |
| `auth.store` — register persists user + token | Consistent registration |
| `sessions.store` — setActiveSession, clearActiveSession | Timer depends on this |
| `analytics.store` — updateFromWebSocket, setRecalculating | Reactive dashboard |
| `technologies.store` — CRUD + local TTL | Technology list |
| `goals.store` — localStorage persistence, local CRUD | Frontend-only feature |
| `useSessionTimer` — start, tick, stop, elapsed | Timer is a critical feature |
| `useFormValidation` — email, required, min length rules | Form validation |
| `useDebounce` — delay, cancel | Search depends on this |
| `usePagination` — page, perPage, total, hasNext | Paginated listing |
| `useSort` — asc/desc, multi-field | Sorting |
| `useWebSocket` — connect, disconnect, isConnected | WS lifecycle |
| `useAsync` — loading, error, data, execute | Async wrapper |
| `getApiErrorMessage()` — various error formats | Error handling |
| `apiClient` interceptors — injects token, doesn't inject without token | Correct headers |
| `dateUtils` — formatting, parse, diffInMinutes | Time calculation |
| `formatters` — duration, percentage, numbers | Dashboard display |
| `validators.extended` — custom rules | Forms |

### 3.2 Integration Tests

**Objective:** Validate complete business flows involving multiple layers (controller -> service -> repository -> database).

**Backend (PHPUnit with RefreshDatabase):**

| Scenario | Layers Involved |
|---|---|
| Register + login + me + logout | HTTP -> AuthController -> AuthService -> Sanctum -> DB |
| Full technology CRUD (create, list, show, update, deactivate) | HTTP -> TechnologyController -> TechnologyService -> Repository -> DB |
| Full session CRUD (manual store, list, show, update, delete) | HTTP -> StudySessionController -> StudySessionService -> Repository -> DB -> Events |
| Start session -> active -> end | HTTP -> Controller -> Service -> DB -> Events (creation + update) |
| Start 2nd session -> 409 Concurrent | HTTP -> Controller -> Service -> Exception -> Handler -> 409 |
| Store session -> event -> listener -> job dispatch | HTTP -> Service -> Event -> DispatchMetricsRecalculation -> Queue assertion |
| Delete session -> event -> cache invalidation + recalc | HTTP -> Service -> Event -> InvalidateSessionCache + DispatchMetricsRecalculation |
| Dashboard with cache miss -> hit -> invalidation -> miss | HTTP -> AnalyticsService -> Cache -> Repository |
| Export with valid / invalid range | HTTP -> AnalyticsController -> AnalyticsService -> Repository |
| Recalculate -> 202 + job dispatched | HTTP -> AnalyticsController -> AnalyticsService -> Queue |
| Health check with all services OK | HTTP -> HealthController -> DB::connection + Redis::ping |
| Health check with Redis down | HTTP -> HealthController -> 503 degraded |
| Change password -> revokes tokens | HTTP -> AuthController -> AuthService -> DB |
| Revoke all tokens | HTTP -> AuthController -> TokenService -> DB |
| Session listing with filters (technology_id, period, pagination) | HTTP -> Controller -> Service -> Repository -> DB queries |
| Technology search with query and limit | HTTP -> Controller -> Service -> Repository -> DB (GIN index) |

**Frontend (Vitest + vue-test-utils):**

| Scenario | Layers Involved |
|---|---|
| LoginForm submits -> auth.store.login -> mock API -> redirect | Component -> Store -> API mock -> Router |
| RegisterForm submits -> store.register -> persists -> redirect | Component -> Store -> API mock -> Router |
| SessionsView mounts -> loads list -> displays -> pagination | Component -> Vue Query -> API mock -> Rendering |
| DashboardView mounts -> loads analytics -> renders KPIs | Component -> Store -> API mock -> Charts |
| TechnologiesView -> creates technology -> list updates | Component -> Store -> API mock -> Reactivity |
| GoalsView -> creates goal -> persists localStorage -> displays | Component -> Store -> localStorage |
| Route guard: no token -> redirect login | Router -> Guard -> AuthStore |
| Route guard: has token, no user -> fetchMe -> next | Router -> Guard -> AuthStore -> API mock |
| Route guard: guest with token -> redirect dashboard | Router -> Guard -> AuthStore |
| 401 interceptor -> clearSessionLocally -> redirect login | apiClient -> interceptor -> AuthStore -> Router |
| 429 interceptor -> toast displayed | apiClient -> interceptor -> toast callback |

### 3.3 End-to-End Tests

**Objective:** Validate complete flows from the user's perspective in a real environment (Cypress or Playwright against Docker Compose).

| E2E Flow | Steps | Verification |
|---|---|---|
| **F1: Complete registration** | Open /register -> fill name, email, password -> submit -> redirect dashboard | Dashboard visible, user logged in |
| **F2: Login + navigation** | Open /login -> valid credentials -> dashboard -> sidebar -> navigate sessions -> back | All screens load |
| **F3: Complete study session** | Login -> create technology -> start focus session -> timer runs -> end -> dashboard updates | Timer works, session appears in list, metrics change |
| **F4: Manual session log** | Login -> sessions -> new manual session (with started_at, ended_at, notes, mood) -> submit | Session in list with correct data |
| **F5: Technology CRUD** | Login -> technologies -> create -> edit name -> deactivate -> verify it's gone from list | Technology persists and deactivates |
| **F6: Reactive dashboard** | Login -> dashboard -> verify KPIs -> create session in another tab -> dashboard updates (WebSocket or polling) | Numbers change |
| **F7: Logout + guard** | Login -> logout -> try to access /dashboard -> redirect /login | Cannot access protected area |
| **F8: Error handling** | Login -> trigger 422 (session without technology_id) -> error message | Toast/message visible |

### 3.4 API Contract Tests

**Objective:** Ensure frontend and backend agree on request and response structures.

**Implementation:** Use JSON schemas exported from the backend (or derived from API Resources) and validate against frontend `types/*.ts`.

| Contract | Backend (Resource/Response) | Frontend (Type) | Critical Fields |
|---|---|---|---|
| Login response | `{ success, data: { user: UserResource, token, token_type } }` | `auth.api.ts` response | `user.id`, `user.email`, `token` |
| Register response | `{ success, data: { user, token, token_type } }` | `auth.api.ts` response | Same structure as login |
| Me response | `{ success, data: UserResource }` | `User` in `domain.types.ts` | `id`, `name`, `email`, `timezone` |
| Study session response | `StudySessionResource` | `StudySession` in types | `id`, `technology_id`, `started_at`, `ended_at`, `duration_min`, `notes`, `mood`, `focus_score` |
| Session list response | `{ success, data: [...], meta: { current_page, last_page, per_page, total } }` | API response + meta | Pagination |
| Active session response | `{ ...session, elapsed_seconds }` | `ActiveSessionResponse` | `elapsed_seconds` |
| Technology response | `TechnologyResource` | `Technology` in types | `id`, `name`, `slug`, `color`, `is_active` |
| Dashboard response | `DashboardResource` | analytics types | `user_metrics`, `technology_metrics`, `time_series_30d`, `top_technologies` |
| Error response (422) | `{ success: false, error: { code, message, details } }` | `getApiErrorMessage()` | `error.code`, `error.message` |
| Error response (401) | `{ success: false, error: { code: 'UNAUTHENTICATED' } }` | interceptor | `error.code` |
| Error response (409) | `{ success: false, error: { code: 'CONCURRENT_SESSION' } }` | error type | `error.code` |
| Error response (429) | `{ success: false, error: { code: 'RATE_LIMITED', retry_after } }` | interceptor | numeric `retry_after` |
| Export response | `{ success, data: { exported_at, period, data } }` | export types | data array structure |
| Heatmap response | `{ success, data: [...] }` | chart types | Format compatible with HeatmapChart |
| Time series response | `{ success, data: [...] }` | chart types | Format compatible with LineChart |
| Health response | `{ status, version, timestamp, ?services }` | Not consumed by frontend, but by infra | `status` in ['healthy', 'degraded'] |

**How to implement:**

1. In backend, create a test that serializes each Resource and asserts the JSON structure.
2. In frontend, create Zod schemas that reflect TS typing and validate mocked responses.
3. In CI, generate a contract snapshot in backend and validate in frontend (or use a tool like Pact).

### 3.5 Regression Tests

**Objective:** Detect regressions in existing functionality after changes.

| Regression Scenario | Likely Trigger | Test |
|---|---|---|
| Login stops working after AuthService change | Auth refactoring | Feature test: login with valid credentials returns 200 + token |
| Active session not detected after repository change | Query altered | Feature test: start -> active returns session |
| Dashboard returns empty data after aggregator change | Analytics query change | Feature test: dashboard with existing sessions returns data |
| Frontend timer freezes after useSessionTimer refactoring | Composable change | Unit test: timer increments correctly |
| Route guard allows access without token | guards.ts change | Unit test: protected route without token -> redirect login |
| Cache not invalidated after session CRUD | Listener changed | Integration test: create session -> dashboard cache stale -> verify listener cleared |
| Event not broadcast after session end | Listener change | Unit test: SessionEnded -> BroadcastSessionEnded called |
| Metrics recalculation not dispatched | DispatchMetricsRecalculation listener changed | Unit test: event -> job dispatched |
| 401 doesn't redirect to login | Interceptor changed | Unit test: apiClient with 401 -> clearSessionLocally |
| Pagination broken in session list | Controller/repository change | Feature test: paginated listing returns correct meta |
| Technology search returns no results | GIN query changed | Feature test: search with term -> results |
| Export returns incorrect data | Repository changed | Unit test: getExportData with range -> correct format |
| Goals disappear after refresh | Store changed | Unit test: goals persist in localStorage and reload |

### 3.6 Security Tests

Detailed in section 5.

### 3.7 Basic Load/Performance Tests

**Objective:** Identify obvious bottlenecks and ensure the system supports normal usage.

| Test | Tool | Metric | Acceptable Limit |
|---|---|---|---|
| Login under load (50 concurrent users) | k6 / Artillery | p95 response time | < 500ms |
| Dashboard under load (100 req/s) | k6 | p95 response time | < 1s |
| Session listing (1000 sessions per user) | PHPUnit + factory | Response time | < 300ms |
| RecalculateMetricsJob with 5000 sessions | PHPUnit + factory | Execution time | < 10s |
| Technology search with 100 techs and partial match | PHPUnit + factory | Response time | < 200ms |
| Cache hit rate after warm-up | Redis MONITOR + metrics | Hit rate | > 80% |
| Concurrent session start (race condition) | PHPUnit with parallelism or k6 | 0 duplicate sessions | Exactly 1 created |
| Frontend bundle | `vite build` + `vite-plugin-inspect` | Total size | < 500KB gzipped |

### 3.8 Resilience/Failure Tests

**Objective:** Validate system behavior when dependencies fail.

| Failure Scenario | Expectation | Test |
|---|---|---|
| Redis unavailable — dashboard request | Bypass cache, query DB directly (or 503) | Feature test with Redis mock throwing exception |
| Redis unavailable — sliding window rate limit | Fail-open (request passes) | Unit test of middleware with Lua throwing exception |
| Redis unavailable — job dedup | Fail-open (job dispatches) | Unit test of listener with Lua throwing exception |
| PostgreSQL slow — dashboard | Controlled timeout, no eternal hang | Feature test with mocked slow query |
| Reverb/WebSocket down — frontend | 45s fallback timer releases spinner | Unit test of useWebSocket with failed connection |
| RecalculateMetricsJob fails 3x | Error log, no infinite retry | Unit test: job with exception -> tries exhausted -> failed() called |
| MetricsRecalculated broadcast fails | Dashboard doesn't update via WS, but polling or manual refresh recovers | E2E: kill Reverb -> create session -> manual dashboard refresh |
| PHP-FPM container restarts during request | Nginx returns 502/504, frontend shows error | E2E: kill php-fpm during request -> verify frontend shows message |
| Token expired during use | 401 -> redirect login, no loop | Unit test: apiClient with 401 on protected route |

### 3.9 Usability/Critical User Flow Tests

**Objective:** Ensure the end-user experience is coherent and free of frustrations.

| Critical Flow | Acceptance Criteria |
|---|---|
| First access: register -> first login -> empty dashboard | Welcome message or friendly empty state, no error |
| Create first technology | Clear form, success feedback, technology appears in list |
| Start first study session | Choose technology -> timer starts -> clear visual feedback |
| End session | End button -> timer stops -> session appears as completed with duration |
| Return next day and see dashboard | Previous day's data reflected, heatmap with 1 day filled |
| Try to start session with another active | Clear message that active session exists, with option to end |
| Navigate between screens with active session | Timer visible somewhere, session not lost |
| Lose connection and reconnect | Dashboard eventually updates, no corrupted state |
| Switch theme (dark/light) | All components, charts and icons adapt without visual break |
| Export data | Loading feedback, download works, correct data |

---

## 4. Detailed Functional Coverage

### 4.1 Authentication (Login, Registration, Logout, Password Change, Token Management)

#### 4.1.1 Registration

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| AUTH-R01 | Registration with valid data | name, email, password, password_confirmation | 201, user + token | Feature |
| AUTH-R02 | Registration with duplicate email | existing email | 422 VALIDATION_ERROR | Feature |
| AUTH-R03 | Registration without password_confirmation | password without confirmation | 422 VALIDATION_ERROR | Feature |
| AUTH-R04 | Registration with short password | password < 8 chars | 422 VALIDATION_ERROR | Feature |
| AUTH-R05 | Registration with invalid email | email: "notanemail" | 422 VALIDATION_ERROR | Feature |
| AUTH-R06 | Registration with custom timezone | timezone: "America/Sao_Paulo" | 201, user.timezone = "America/Sao_Paulo" | Feature |
| AUTH-R07 | Registration without timezone uses UTC default | no timezone field | 201, user.timezone = "UTC" | Feature |
| AUTH-R08 | Returned token is functional | use token for GET /auth/me | 200, user data | Feature |
| AUTH-R09 | Registration rate limit | > N registrations in 1 minute | 429 RATE_LIMITED | Feature |

#### 4.1.2 Login

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| AUTH-L01 | Login with valid credentials | correct email + password | 200, user + token + Bearer token_type | Feature |
| AUTH-L02 | Login with wrong password | wrong password | 401 UNAUTHENTICATED | Feature |
| AUTH-L03 | Login with non-existent email | unregistered email | 401 UNAUTHENTICATED | Feature |
| AUTH-L04 | Login revokes previous tokens | login -> login again | First token invalid | Feature |
| AUTH-L05 | Login with empty fields | email: "", password: "" | 422 VALIDATION_ERROR | Feature |
| AUTH-L06 | Login rate limit | > N logins in 1 minute | 429 RATE_LIMITED | Feature |
| AUTH-L07 | Login returns consistent structure | valid credentials | data.user contains id, name, email; data.token string | Contract |

#### 4.1.3 Logout

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| AUTH-O01 | Logout revokes current token | POST /auth/logout with valid token | 200, token invalidated | Feature |
| AUTH-O02 | Logout with invalid token | Bearer with expired token | 401 | Feature |
| AUTH-O03 | After logout, me returns 401 | logout -> GET /auth/me | 401 | Feature |

#### 4.1.4 Password Change

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| AUTH-P01 | Change with correct current password | correct current_password, new password | 200, success | Feature |
| AUTH-P02 | Change with wrong current password | wrong current_password | 422 | Feature |
| AUTH-P03 | Change revokes all tokens | change password -> use old token | 401 | Feature |
| AUTH-P04 | New password too short | password < 8 chars | 422 | Feature |
| AUTH-P05 | Rate limit (sensitive endpoint) | > N changes in 1 minute | 429 | Feature |

#### 4.1.5 Token Management

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| AUTH-T01 | List tokens returns array | GET /auth/tokens | 200, array with id, name, created_at, last_used_at | Feature |
| AUTH-T02 | Revoke all tokens | DELETE /auth/tokens | 200, revoked_count >= 1 | Feature |
| AUTH-T03 | After revoking all, no token works | revokeAll -> any request | 401 | Feature |

### 4.2 Route Guards and Session Persistence

| ID | Scenario | Expected | Type |
|---|---|---|---|
| GUARD-01 | Protected route without token -> redirect /login | next({ name: 'login' }) | Unit (guard) |
| GUARD-02 | Guest route with token -> redirect /dashboard | next({ name: 'dashboard' }) | Unit (guard) |
| GUARD-03 | Protected route with token, no user -> fetchMe -> next | user populated, next() | Unit (guard) |
| GUARD-04 | Protected route with token, user present -> background refresh | fetchMe in background, immediate next() | Unit (guard) |
| GUARD-05 | fetchMe fails with 401 -> redirect login | token cleared, redirect | Unit (guard) |
| GUARD-06 | Page refresh: token in localStorage -> restores session | user reloaded, dashboard accessible | E2E |
| GUARD-07 | fetchMe already in progress -> doesn't duplicate call | fetchMePromise reused | Unit (guard) |

### 4.3 Technology CRUD

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| TECH-01 | List user technologies | GET /technologies | 200, TechnologyResource array | Feature |
| TECH-02 | Create technology | name, slug, color | 201, technology created | Feature |
| TECH-03 | Create with duplicate name | existing name | 422 | Feature |
| TECH-04 | Create without required fields | empty payload | 422 | Feature |
| TECH-05 | Show technology by ID | GET /technologies/{id} | 200, technology | Feature |
| TECH-06 | Show another user's technology | other user's tech ID | 403 or 404 | Feature/Security |
| TECH-07 | Update technology | PUT with new data | 200, updated data | Feature |
| TECH-08 | Deactivate technology | DELETE /technologies/{id} | 200, technology deactivated (soft delete) | Feature |
| TECH-09 | Deactivated technology doesn't appear in index | after deactivation | GET index doesn't contain tech | Feature |
| TECH-10 | Sessions of deactivated tech remain intact | deactivate tech with sessions | Sessions still exist | Feature |
| TECH-11 | Search with partial query | GET /search?q=vue&limit=5 | Filtered results | Feature |
| TECH-12 | Search with empty query | GET /search?q= | Empty return or all | Feature |
| TECH-13 | Search rate limit | > N searches in 1 minute | 429 | Feature |

### 4.4 Study Sessions

#### 4.4.1 Creation and Manual Log

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| SESS-C01 | Create manual session with all fields | technology_id, started_at, ended_at, notes, mood, focus_score | 201, session created with all fields | Feature |
| SESS-C02 | Create session without ended_at (open) | only started_at | 201, session with ended_at null | Feature |
| SESS-C03 | Create with another user's technology_id | other user's tech | 403 | Feature/Security |
| SESS-C04 | Create without technology_id | payload without tech | 422 | Feature |
| SESS-C05 | Create with ended_at before started_at | ended_at < started_at | 422 | Feature |
| SESS-C06 | Creation dispatches StudySessionCreated | any valid creation | Event dispatched | Unit (service) |
| SESS-C07 | Sliding window rate limit on store | > 30 req/min | 429 | Feature |

#### 4.4.2 Focus Mode (start/end)

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| SESS-F01 | Start with technology_id | POST /start with tech | 201, active session | Feature |
| SESS-F02 | Start without technology_id (uses first tech) | POST /start without tech | 201, uses user's first tech | Feature |
| SESS-F03 | Start with already active session | second call to /start | 409 CONCURRENT_SESSION | Feature |
| SESS-F04 | End active session | PATCH /end | 200, ended_at populated, duration_min calculated | Feature |
| SESS-F05 | End already ended session | PATCH /end on session with ended_at | 422 "Session already ended" | Feature |
| SESS-F06 | Active returns in-progress session | GET /active with active session | 200, session + elapsed_seconds | Feature |
| SESS-F07 | Active returns null without active session | GET /active without session | 200, data: null | Feature |

#### 4.4.3 Listing, Detail, Update, Delete

| ID | Scenario | Input | Expected | Type |
|---|---|---|---|---|
| SESS-L01 | List with pagination | GET /study-sessions?page=1&per_page=10 | 200, meta with current_page, last_page, per_page, total | Feature |
| SESS-L02 | List filtered by technology_id | GET /study-sessions?technology_id=X | Only sessions of that tech | Feature |
| SESS-L03 | List filtered by period | GET /study-sessions?start_date=X&end_date=Y | Only sessions in period | Feature |
| SESS-D01 | Show own user's session | GET /study-sessions/{id} | 200, session data | Feature |
| SESS-D02 | Show another user's session | GET with other user's id | 403 | Feature/Security |
| SESS-D03 | Show non-existent session | invalid UUID | 404 | Feature |
| SESS-U01 | Update notes and mood | PATCH with notes + mood | 200, fields updated | Feature |
| SESS-U02 | Update another user's session | PATCH on other user's session | 403 | Feature/Security |
| SESS-X01 | Delete own session | DELETE /study-sessions/{id} | 200, session removed | Feature |
| SESS-X02 | Delete another user's session | DELETE on other user's session | 403 | Feature/Security |
| SESS-X03 | Delete dispatches StudySessionDeleted with correct data | any valid delete | Event with userId, sessionId, duration_min, started_at | Unit (service) |

### 4.5 Concurrent Session Prevention

| ID | Scenario | Expected | Type |
|---|---|---|---|
| CONC-01 | Start -> Start (same user) | 409 on second | Feature |
| CONC-02 | Start -> End -> Start | Success on all three operations | Feature |
| CONC-03 | Race condition: 2 simultaneous starts | Exactly 1 created (trigger/constraint) | Feature (parallelism) |
| CONC-04 | Start user A, Start user B | Both create (independent) | Feature |
| CONC-05 | Database trigger rejects duplicate active session insert | Direct INSERT with 2 active sessions | Migration/DB test |

### 4.6 Dashboard and Analytics

| ID | Scenario | Expected | Type |
|---|---|---|---|
| DASH-01 | Dashboard with user without sessions | Payload with zeros or empty | Feature |
| DASH-02 | Dashboard with recent sessions | user_metrics, technology_metrics, time_series_30d, top_technologies populated | Feature |
| DASH-03 | Dashboard uses cache (second call without recalc) | Identical response, no DB hit | Feature (assert cache hit) |
| DASH-04 | Dashboard after creating session (cache invalidated) | Updated data on next call | Feature |
| DASH-05 | User metrics returns streaks and totals | Streak fields, total_minutes, total_sessions | Feature |
| DASH-06 | Tech stats returns per-technology metrics | Array with name, total_minutes, session_count per tech | Feature |
| DASH-07 | Time series with 30 days | Array of { date, minutes } objects with 30 entries | Feature |
| DASH-08 | Time series with custom parameter (7 days) | 7 entries | Feature |
| DASH-09 | Weekly returns weekly comparison | Current week vs previous week data | Feature |

### 4.7 Heatmap, Time Series, Aggregations, and Export

| ID | Scenario | Expected | Type |
|---|---|---|---|
| HEAT-01 | Current year heatmap | Activity data by day | Feature |
| HEAT-02 | Specific year heatmap | Data for that year | Feature |
| HEAT-03 | Heatmap for user without sessions | Empty or zeroed array | Feature |
| EXP-01 | Export with valid range | 200 with exported_at, period, data array | Feature |
| EXP-02 | Export with inverted range (start > end) | 422 or empty array | Feature |
| EXP-03 | Export with very large range | Works (but check performance) | Feature/Performance |
| EXP-04 | Export without parameters | 422 (start and end required) | Feature |
| EXP-05 | Export rate limit | > N exports in 1 minute | 429 | Feature |

### 4.8 WebSocket Failure Fallback

| ID | Scenario | Expected | Type |
|---|---|---|---|
| WS-F01 | Reverb unavailable -> dashboard loads normally | Dashboard fetches via HTTP without error | E2E |
| WS-F02 | WS connection drops during use -> isConnected = false | State reflects disconnection | Unit (composable) |
| WS-F03 | Recalculating without metrics.updated in 45s -> spinner releases | setRecalculating(false) after timeout | Unit (composable) |
| WS-F04 | WS reconnects after drop | isConnected returns to true | E2E |

### 4.9 Backend-Frontend Event Synchronization

| ID | Scenario | Expected | Type |
|---|---|---|---|
| SYNC-01 | Create session -> event -> recalc -> broadcast -> frontend updates dashboard | Dashboard displays new metrics | Integration/E2E |
| SYNC-02 | End session -> SessionEnded broadcast -> frontend clears active session | Timer stops, active session disappears | Integration/E2E |
| SYNC-03 | Start session -> SessionStarted broadcast -> frontend displays active session | Timer starts automatically | Integration/E2E |
| SYNC-04 | MetricsRecalculating broadcast -> frontend shows spinner | analyticsStore.isRecalculating = true | Unit (composable) |
| SYNC-05 | MetricsRecalculated broadcast -> frontend receives complete dashboard | analyticsStore updates, spinner disappears | Unit (composable) |

### 4.10 Frontend-Only Goals

| ID | Scenario | Expected | Type |
|---|---|---|---|
| GOAL-01 | Create goal -> persists in localStorage | goal appears in list, survives refresh | Unit (store) |
| GOAL-02 | Edit goal | Goal updated in localStorage | Unit (store) |
| GOAL-03 | Delete goal | Removed from localStorage and list | Unit (store) |
| GOAL-04 | Clear localStorage -> goals disappear | No goals after clear | Unit (store) |
| GOAL-05 | Goal with calculated progress | useGoalProgress returns % based on sessions | Unit (composable) |
| GOAL-06 | User goals don't leak to another user (multi-tab) | Key-based isolation | Unit (store) |

### 4.11 HTTP Error Handling

| ID | Status | Scenario | Expected Frontend | Expected Backend | Type |
|---|---|---|---|---|---|
| ERR-401 | 401 | Expired token on any request | clearSessionLocally + redirect /login | `{ success: false, error: { code: 'UNAUTHENTICATED' } }` | Unit + Feature |
| ERR-403 | 403 | Access another user's resource | "Access denied" message | `{ success: false, error: { code: 'FORBIDDEN' } }` | Feature |
| ERR-404 | 404 | Non-existent resource | "Not found" message | `{ success: false, error: { code: 'NOT_FOUND' } }` | Feature |
| ERR-422 | 422 | Validation failed | Show field details | `{ success: false, error: { code: 'VALIDATION_ERROR', details } }` | Feature |
| ERR-429 | 429 | Rate limit exceeded | "Too many requests" toast | `{ success: false, error: { code: 'RATE_LIMITED', retry_after } }` | Feature |
| ERR-500 | 500 | Internal error | Generic message | `{ success: false, error: { code: 'INTERNAL_ERROR' } }` (no stack trace in prod) | Feature |
| ERR-409 | 409 | Concurrent session | "Active session exists" message | `{ success: false, error: { code: 'CONCURRENT_SESSION' } }` | Feature |

### 4.12 Health Checks

| ID | Scenario | Expected | Type |
|---|---|---|---|
| HC-01 | All services OK | 200, status: "healthy" | Feature |
| HC-02 | DB down | 503, status: "degraded" | Feature |
| HC-03 | Redis down | 503, status: "degraded" | Feature |
| HC-04 | Reverb down | status may be healthy (WS non-critical) or degraded | Feature |
| HC-05 | In local/testing environment, services appear | services: { database, redis, queue, websocket } | Feature |
| HC-06 | In production, services don't appear | No services key | Feature |
| HC-07 | Health rate limit | > N checks in 1 minute | 429 | Feature |

---

## 5. Security Coverage

### 5.1 Token Authentication

| ID | Scenario | Expected | Type |
|---|---|---|---|
| SEC-A01 | Request without Authorization header on protected route | 401 | Feature |
| SEC-A02 | Request with invalid token (garbage) | 401 | Feature |
| SEC-A03 | Request with expired/revoked token | 401 | Feature |
| SEC-A04 | User A's token doesn't work as User B | Returned data belongs to token owner | Feature |
| SEC-A05 | Login revokes previous tokens (single session) | Old token returns 401 | Feature |
| SEC-A06 | Password not exposed in UserResource | GET /me doesn't contain password field | Contract |
| SEC-A07 | Token not logged (dontFlash) | password/current_password not logged | Unit (Handler) |

### 5.2 Cross-User Authorization

| ID | Scenario | Expected | Type |
|---|---|---|---|
| SEC-Z01 | User B accesses User A's session via GET | 403 | Feature |
| SEC-Z02 | User B updates User A's session via PATCH | 403 | Feature |
| SEC-Z03 | User B deletes User A's session via DELETE | 403 | Feature |
| SEC-Z04 | User B accesses User A's technology via GET | 403 or 404 | Feature |
| SEC-Z05 | User B uses User A's technology_id to create session | 403 | Feature |
| SEC-Z06 | User B accesses User A's dashboard | Impossible (endpoint uses request->user()) | Feature |
| SEC-Z07 | User B tries to revoke User A's tokens | Impossible (endpoint uses request->user()) | Feature |

### 5.3 Rate Limit

| ID | Endpoint | Expected Limit | Type |
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
| SEC-RL13 | Authenticated read routes | throttle:60,1 | Feature |
| SEC-RL14 | Authenticated write routes (api.php group) | throttle:30,1 | Feature |

### 5.4 Payload Validation

| ID | Scenario | Expected | Type |
|---|---|---|---|
| SEC-V01 | Registration with extra fields (e.g., is_admin) | Extra fields ignored | Feature |
| SEC-V02 | Session with duration_min in payload (calculated field) | Field ignored or rejected | Feature |
| SEC-V03 | Session with user_id in payload (attempt injection) | Ignored, uses request->user() | Feature |
| SEC-V04 | Technology with is_active: false on create | Ignored or rejected | Feature |
| SEC-V05 | SQL injection in search field | Search doesn't execute arbitrary SQL | Feature/Security |
| SEC-V06 | XSS in session notes field | Content stored/returned without execution | Feature/Security |
| SEC-V07 | Malformed JSON payload | 422 or 400 | Feature |

### 5.5 Sensitive Data Leakage

| ID | Scenario | Expected | Type |
|---|---|---|---|
| SEC-D01 | UserResource doesn't expose password hash | Field absent from response | Contract |
| SEC-D02 | Production error response doesn't expose stack trace | Generic error.message | Feature |
| SEC-D03 | Logs don't contain passwords (dontFlash) | password, current_password, password_confirmation absent | Unit (Handler) |
| SEC-D04 | Production health check doesn't expose service details | No services key | Feature |

### 5.6 Private WebSocket Channel Protection

| ID | Scenario | Expected | Type |
|---|---|---|---|
| SEC-WS01 | User A subscribes to dashboard.{userA.id} | Authorized | Feature/Integration |
| SEC-WS02 | User A tries to subscribe to dashboard.{userB.id} | Rejected (403 on channel auth) | Feature/Integration |
| SEC-WS03 | Channel auth request without token | 401 | Feature |

### 5.7 403 vs 404 Consistency

| ID | Scenario | Current Behavior | Recommendation |
|---|---|---|---|
| SEC-C01 | GET another user's session | 403 (reveals existence) | Consider 404 to not leak resource existence |
| SEC-C02 | GET another user's technology | 403 or 404 (verify) | Standardize |
| SEC-C03 | GET non-existent session | 404 | OK |

> **Note:** The `findForUser` service throws `ModelNotFoundException` for non-existent ID and `AuthorizationException` for incorrect ownership. This differentiates 404 from 403, which can leak resource existence information. Document the decision or align to 404 in both cases.

---

## 6. Integration and Consistency Coverage

### 6.1 Frontend <-> Backend Consistency

| ID | Scenario | Validation | Type |
|---|---|---|---|
| INT-01 | `UserResource` fields vs `User` in `domain.types.ts` | Structure snapshot | Contract |
| INT-02 | `StudySessionResource` fields vs frontend type | Structure snapshot | Contract |
| INT-03 | `TechnologyResource` fields vs frontend type | Structure snapshot | Contract |
| INT-04 | `DashboardResource` fields vs frontend analytics type | Structure snapshot | Contract |
| INT-05 | Pagination meta (current_page, last_page, per_page, total) | Frontend reads all 4 fields | Contract |
| INT-06 | ISO 8601 date format in all responses | Frontend parses with Date or dayjs | Contract |
| INT-07 | Error format `{ success: false, error: { code, message } }` | `getApiErrorMessage()` extracts correctly | Contract |

### 6.2 Session Impact on Metrics

| ID | Scenario | Validation | Type |
|---|---|---|---|
| INT-M01 | Create ended session -> metrics increment | Dashboard: total_minutes increases, total_sessions + 1 | Integration |
| INT-M02 | Delete session -> metrics decrement | Dashboard: total_minutes decreases, total_sessions - 1 | Integration |
| INT-M03 | Update session ended_at -> duration recalculated | duration_min reflects new duration, metrics adjusted | Integration |
| INT-M04 | Change session technology_id -> per-tech metrics adjust | Old tech loses minutes, new tech gains | Integration |
| INT-M05 | Create session with new technology -> tech appears in tech_stats | New tech in array | Integration |

### 6.3 Cache Invalidation

| ID | Scenario | Validation | Type |
|---|---|---|---|
| INT-C01 | Create session -> `sessions:user:{id}` cache flushed | Next listing queries DB | Integration |
| INT-C02 | Create session -> RecalculateMetricsJob -> `analytics:user:{id}` cache flushed | Dashboard fetches new | Integration |
| INT-C03 | Delete session -> both caches flushed | Listing and dashboard updated | Integration |
| INT-C04 | Update non-relevant field (notes) -> analytics doesn't recalculate | Analytics cache intact (optimization) | Integration |
| INT-C05 | Update ended_at -> analytics recalculates | Analytics cache flushed | Integration |
| INT-C06 | Manual recalculate trigger -> cache flushed + recalculated data | Dashboard updated | Integration |

### 6.4 Listener and Job Execution

| ID | Scenario | Validation | Type |
|---|---|---|---|
| INT-LJ01 | StudySessionCreated -> InvalidateSessionCache + DispatchMetricsRecalculation | Both listeners execute | Integration |
| INT-LJ02 | StudySessionUpdated with ended_at -> DispatchMetricsRecalculation with fullRecalc | Job dispatched with fullRecalc = true | Unit (listener) |
| INT-LJ03 | StudySessionUpdated with notes -> DispatchMetricsRecalculation doesn't dispatch fullRecalc | fullRecalc = false (non-relevant field) | Unit (listener) |
| INT-LJ04 | StudySessionDeleted -> DispatchMetricsRecalculation with fullRecalc | fullRecalc = true | Unit (listener) |
| INT-LJ05 | RecalculateMetricsJob executes in transaction | Metrics consistent or complete rollback | Unit (job) |
| INT-LJ06 | RecalculateMetricsJob fails -> failed() logs | Log contains userId and error message | Unit (job) |
| INT-LJ07 | RecalculateMetricsJob unique per user (ShouldBeUnique) | Second dispatch in same interval doesn't create duplicate job | Integration |
| INT-LJ08 | Lua dedup prevents repeated dispatch within window | shouldDispatch returns 0 on second call | Unit (listener + Lua) |
| INT-LJ09 | Lua unavailable -> fail-open -> job dispatches normally | Warning log, job executed | Unit (listener) |

### 6.5 Broadcast and Frontend Update

| ID | Scenario | Validation | Type |
|---|---|---|---|
| INT-B01 | MetricsRecalculated broadcast -> correct channel (dashboard.{userId}) | broadcastOn returns correct PrivateChannel | Unit (event) |
| INT-B02 | MetricsRecalculated -> broadcastAs returns '.metrics.updated' | Frontend listens to correct event | Unit (event) |
| INT-B03 | MetricsRecalculated -> broadcastWith returns { dashboard } | Payload contains complete dashboardData | Unit (event) |
| INT-B04 | MetricsRecalculating broadcast -> frontend shows loading | analyticsStore.isRecalculating = true | Unit (composable) |
| INT-B05 | SessionStarted broadcast -> frontend updates active session | sessionsStore.activeSession populated | Unit (composable) |
| INT-B06 | SessionEnded broadcast -> frontend clears active session | sessionsStore.activeSession = null | Unit (composable) |

### 6.6 Stores <-> Vue Query <-> Backend Compatibility

| ID | Scenario | Validation | Type |
|---|---|---|---|
| INT-SQ01 | Vue Query for dashboard invalidates after session mutation | queryClient.invalidateQueries after POST/PATCH/DELETE | Integration (frontend) |
| INT-SQ02 | Session store and Vue Query don't conflict on data | Single source of truth per domain | Code review |
| INT-SQ03 | Pinia and Vue Query with same endpoint data | Verify no divergence | Code review |

### 6.7 Transactional and Analytics Schema Consistency

| ID | Scenario | Validation | Type |
|---|---|---|---|
| INT-SA01 | Create session -> recalc -> user_metrics.total_sessions = COUNT(*) in public | Numbers match | Integration |
| INT-SA02 | Delete session -> recalc -> user_metrics.total_minutes -= session.duration_min | Numbers match | Integration |
| INT-SA03 | daily_minutes sums with same-day sessions | SUM(duration_min) per day matches | Integration |
| INT-SA04 | technology_metrics sums with per-tech sessions | SUM(duration_min) per tech matches | Integration |

---

## 7. Quality and Regression Coverage

### 7.1 Regression Suite — Existing Features

| ID | Area | Test | Frequency |
|---|---|---|---|
| REG-01 | Auth | Login + Me + Logout works | Every PR |
| REG-02 | Auth | Registration creates user and returns token | Every PR |
| REG-03 | Sessions | Full CRUD (store, show, update, delete) | Every PR |
| REG-04 | Sessions | Start + End works | Every PR |
| REG-05 | Sessions | Concurrent session blocked | Every PR |
| REG-06 | Technologies | Full CRUD (create, list, update, deactivate) | Every PR |
| REG-07 | Analytics | Dashboard returns data | Every PR |
| REG-08 | Analytics | Export returns data | Every PR |
| REG-09 | Frontend | Login form submits and redirects | Every PR |
| REG-10 | Frontend | Route guard blocks without token | Every PR |
| REG-11 | Frontend | 401 interceptor clears session | Every PR |
| REG-12 | Frontend | Stores load state from localStorage | Every PR |

### 7.2 Documented Inconsistency Detection

| ID | Inconsistency | Proposed Test |
|---|---|---|
| DOC-01 | Rate limits in README diverge from implementation | Snapshot test that extracts limits from code and compares with docs |
| DOC-02 | Goals described as full-stack feature but are local-only | Test verifies goals.api.ts doesn't make HTTP requests |
| DOC-03 | Docs mention `docs/` folder that may not exist | CI script that verifies internal links |

### 7.3 Production Stability

| ID | Scenario | Type |
|---|---|---|
| STAB-01 | Post-deploy smoke test: login + dashboard + create session | E2E smoke |
| STAB-02 | Health check returns 200 after deploy | HTTP check |
| STAB-03 | Horizon processing queues (queue not stopped) | Metric verification |
| STAB-04 | Redis responding | Health check |
| STAB-05 | Frontend build without type errors | vue-tsc in CI |

---

## 8. Coverage Matrix by Module

| Module | Unit | Feature/Integration | E2E | Contract | Security | Performance | Resilience |
|---|---|---|---|---|---|---|---|
| **Auth (backend)** | AuthService, TokenService | Login, Register, Logout, Me, ChangePassword, Tokens | F1, F2, F7 | Login/Register/Me response | SEC-A*, SEC-Z06-Z07 | Login under load | Expired token |
| **Sessions (backend)** | StudySessionService, DTOs | CRUD, Start, End, Active, Concurrent | F3, F4 | Session response, list meta | SEC-Z01-Z05 | 1000 listing | — |
| **Technologies (backend)** | TechnologyService | CRUD, Search, Deactivate | F5 | Tech response | SEC-Z04-Z05 | 100 tech search | — |
| **Analytics (backend)** | AnalyticsService, MetricsAggregator | Dashboard, Metrics, TimeSeries, Heatmap, Export, Recalc | F6 | Dashboard response | SEC-Z06 | Dashboard under load | Redis down |
| **Events/Listeners** | Each listener isolated | Pipeline: CRUD -> event -> listener -> job | — | — | — | — | Lua unavailable |
| **Jobs** | RecalculateMetricsJob, GenerateWeeklySummary | Full execution, retry, unique | — | — | — | 5000 sessions | Job fails 3x |
| **Middleware** | SlidingWindow, EnsureJson, SetTimezone, LogApi | Rate limit applied | — | — | SEC-RL* | — | Lua unavailable |
| **Handler** | All match cases | Each status code | — | Error format | SEC-D02 | — | — |
| **Health** | — | HC-01 to HC-07 | — | Health response | — | — | DB/Redis down |
| **Auth (frontend)** | auth.store, auth.api | LoginForm, RegisterForm | F1, F2, F7 | Login/Register types | — | — | 401 loop |
| **Sessions (frontend)** | sessions.store, composables | SessionsView, SessionFocusView | F3, F4 | Session types | — | — | — |
| **Technologies (frontend)** | technologies.store | TechnologiesView | F5 | Tech types | — | — | — |
| **Analytics (frontend)** | analytics.store | DashboardView, charts | F6 | Dashboard types | — | — | WS down |
| **Goals (frontend)** | goals.store | GoalsView | — | — | — | — | localStorage full |
| **WebSocket (frontend)** | useWebSocket | — | F6 | WS event types | SEC-WS* | — | Reverb down |
| **HTTP Client (frontend)** | apiClient, interceptors | — | — | Error format | — | — | 401, 429 |
| **Router/Guards (frontend)** | guards.ts | — | F7 | — | — | — | fetchMe fails |
| **Lua Scripts** | Each script isolated | SlidingWindow, Streak, JobDedup | — | — | — | — | Redis down |
| **DB migrations** | — | Migrations up + down | — | — | Triggers, constraints | — | — |
| **Infra (Docker)** | — | — | Containers start | — | — | — | Container restart |

---

## 9. Prioritization

### CRITICAL Priority

| ID | What to Test | Type | Risk Covered | Rationale |
|---|---|---|---|---|
| P-C01 | Login with valid/invalid credentials | Feature | System access | Entry gate for entire system |
| P-C02 | User registration | Feature | User acquisition | Without registration, no flow works |
| P-C03 | Study session Start/End | Feature | Product core | Main functionality |
| P-C04 | Concurrent session prevention | Feature | Data integrity | Duplicate sessions corrupt metrics |
| P-C05 | Cross-user authorization (sessions) | Feature/Security | Data leakage | Another user's sensitive data |
| P-C06 | Dashboard returns correct data | Feature | Product value | Main visualization screen |
| P-C07 | 401 interceptor in frontend | Unit | UX + security | Infinite loop or ghost session |
| P-C08 | Protected route guard | Unit | Frontend security | Access screens without authentication |
| P-C09 | RecalculateMetricsJob executes correctly | Unit/Integration | Metrics integrity | Metrics are derived — error here propagates to all analytics |
| P-C10 | Cache invalidation after session CRUD | Integration | Stale data | User sees old data on dashboard |

### HIGH Priority

| ID | What to Test | Type | Risk Covered | Rationale |
|---|---|---|---|---|
| P-A01 | Technology CRUD | Feature | Supporting functionality | Sessions depend on technologies |
| P-A02 | Session listing with pagination/filters | Feature | Data navigation | Daily usage |
| P-A03 | Password change with token revocation | Feature | Security | Old tokens must stop working |
| P-A04 | Logout revokes token | Feature | Security | Token can't remain valid |
| P-A05 | Event pipeline: CRUD -> listener -> job dispatch | Integration | Pipeline integrity | If broken, metrics stop updating |
| P-A06 | WebSocket: metrics.updated updates dashboard | Unit/E2E | Real-time UX | Dashboard appears frozen |
| P-A07 | Rate limit on login and register | Feature | Security | Brute force |
| P-A08 | Handler returns correct formats for all statuses | Feature | API contract | Frontend can't parse error correctly |
| P-A09 | Time series and heatmap return data | Feature | Visualization | Empty charts |
| P-A10 | Export returns data in range | Feature | Functionality | Incorrect data exported |
| P-A11 | Auth store persists token in localStorage | Unit | Session persistence | Refresh loses login |
| P-A12 | Private WS channel authorized only for owner | Feature | Real-time security | Cross-user in WS |

### MEDIUM Priority

| ID | What to Test | Type | Risk Covered | Rationale |
|---|---|---|---|---|
| P-M01 | Technology search with GIN index | Feature | Search performance | Slow autocomplete |
| P-M02 | MetricsAggregator calculates streaks | Unit | Streak correctness | Streak is a gamification metric |
| P-M03 | SetUserTimezone middleware | Unit | Timezone correctness | Sessions with wrong time |
| P-M04 | Frontend-only goals persist in localStorage | Unit | Local feature | Goals disappear |
| P-M05 | 45s fallback timer on WebSocket | Unit | UX | Eternal spinner |
| P-M06 | Job dedup via Lua | Unit | Efficiency | Redundant recalc |
| P-M07 | SlidingWindowRateLimit middleware | Unit | Security | More precise rate limit |
| P-M08 | Health check with degraded services | Feature | Observability | False positive in monitoring |
| P-M09 | Payload validation (extra fields ignored) | Feature | Security | Mass assignment |
| P-M10 | Frontend <-> backend type consistency | Contract | Desync | Type error at runtime |
| P-M11 | useSessionTimer correct tick | Unit | UX | Timer shows wrong time |
| P-M12 | Weekly comparison | Feature | Visualization | Wrong data in comparison |

### LOW Priority

| ID | What to Test | Type | Risk Covered | Rationale |
|---|---|---|---|---|
| P-B01 | Frontend bundle size | CI check | Performance | Large bundle = slow loading |
| P-B02 | LogApiRequests middleware | Unit | Observability | Incomplete log |
| P-B03 | EnsureJsonResponse middleware | Unit | Contract | HTML instead of JSON |
| P-B04 | GenerateWeeklySummaryJob | Unit | Secondary feature | Weekly summary |
| P-B05 | Dark/light theme on all components | Visual/Snapshot | UX | Component breaks in theme |
| P-B06 | Base UI components (BaseButton, BaseInput, etc.) | Snapshot | Visual regression | Component changes appearance |
| P-B07 | Formatters and dateUtils | Unit | Display | Number formatted incorrectly |
| P-B08 | Notifications store (in-memory) | Unit | UX | Notification doesn't appear |
| P-B09 | Profile update | Feature | UX | Data not saved |
| P-B10 | Specific year heatmap | Feature | Edge case | Previous year data |

---

## 10. Practical Implementation Suggestion

### 10.1 Tools — Backend

| Tool | Usage | Already Present? |
|---|---|---|
| **PHPUnit 11** | Unit and feature tests | Yes |
| **Larastan/PHPStan** | Static analysis | Yes |
| **Laravel Pint** | Formatting | Yes |
| **Pest** (recommendation) | PHPUnit alternative with fluent syntax | No — migrate gradually |
| **Laravel Dusk** (optional) | Server-side browser testing | No |

### 10.2 Tools — Frontend

| Tool | Usage | Already Present? |
|---|---|---|
| **Vitest** | Unit and integration tests | Yes |
| **@vue/test-utils** | Vue component mounting | Yes |
| **happy-dom** | Simulated DOM for tests | Yes |
| **Cypress** or **Playwright** (recommendation) | E2E tests | No |
| **MSW (Mock Service Worker)** (recommendation) | API mocking for component tests | No |
| **@testing-library/vue** (recommendation) | Accessibility-centered tests | No |

### 10.3 Tools — Contract and Performance

| Tool | Usage |
|---|---|
| **Pact** or **schema snapshot** | API contract tests |
| **k6** or **Artillery** | Basic load tests |
| **Lighthouse CI** | Frontend performance and accessibility |

### 10.4 Tests That Should Run in CI

| Suite | Trigger | Estimated Time | Blocking? |
|---|---|---|---|
| Backend unit + feature (PHPUnit) | Every PR | ~2min | Yes |
| Backend Larastan | Every PR | ~30s | Yes |
| Backend Pint (dry-run) | Every PR | ~10s | Yes |
| Frontend unit + component (Vitest) | Every PR | ~1min | Yes |
| Frontend type-check (vue-tsc) | Every PR | ~30s | Yes |
| Frontend lint (ESLint) | Every PR | ~15s | Yes |
| API contract | Every PR | ~30s | Yes |
| E2E smoke (Cypress/Playwright) | Merge to main | ~5min | Yes for deploy |
| Performance (k6) | Weekly or pre-release | ~10min | No (alert) |

### 10.5 Tests That Can Run Locally

| Suite | Command |
|---|---|
| Backend unit + feature | `make test-back` |
| Frontend unit + component | `make test-front` or `cd frontend && npm run test:run` |
| Frontend coverage | `cd frontend && npm run test:coverage` |
| E2E (against Docker Compose) | `npx cypress run` or `npx playwright test` |
| Contract | Custom script in CI or locally |

### 10.6 Required Mocks, Fixtures, Factories, and Seeds

#### Backend — Factories (already exist, expand)

| Factory | Required Fields | Necessary States |
|---|---|---|
| `UserFactory` | name, email, password, timezone | `withTimezone('America/Sao_Paulo')` |
| `TechnologyFactory` | user_id, name, slug, color, is_active | `inactive()` |
| `StudySessionFactory` | user_id, technology_id, started_at, ended_at | `active()` (no ended_at), `withNotes()`, `withMood()`, `longSession()` (>2h) |

#### Backend — Seeders (already exist, expand)

| Seeder | Purpose |
|---|---|
| `DemoDataSeeder` | Realistic data for development and E2E |
| `TestSeeder` (create) | Minimal and deterministic data for automated tests |
| `PerformanceSeeder` (create) | 5000 sessions + 100 techs for load tests |

#### Frontend — Mocks

| Mock | Purpose |
|---|---|
| `msw/handlers/auth.ts` | Login, register, me, logout mock |
| `msw/handlers/sessions.ts` | Session CRUD mock |
| `msw/handlers/technologies.ts` | Technology CRUD mock |
| `msw/handlers/analytics.ts` | Dashboard, metrics, export mock |
| `fixtures/user.ts` | Deterministic User object |
| `fixtures/session.ts` | StudySession objects in various states |
| `fixtures/technology.ts` | Technology objects |
| `fixtures/dashboard.ts` | Complete dashboard payload |

### 10.7 Test Folder Structure

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
          MetricsAggregatorTest.php         # already exists
      Jobs/
        RecalculateMetricsJobTest.php       # already exists
        GenerateWeeklySummaryJobTest.php
      Listeners/
        DispatchMetricsRecalculationTest.php
        InvalidateSessionCacheTest.php
        BroadcastMetricsRecalculatingTest.php  # already exists
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
        AuthTest.php                         # already exists
        ChangePasswordTest.php
        TokenManagementTest.php
      StudySessions/
        StudySessionCrudTest.php             # already exists
        StudySessionStartEndTest.php
        StudySessionFiltersTest.php
      Technologies/
        TechnologyCrudTest.php               # already exists
        TechnologySearchTest.php
      Analytics/
        AnalyticsDashboardTest.php
        AnalyticsTimeSeriesTest.php
        AnalyticsHeatmapTest.php
        AnalyticsExportTest.php              # already exists
        AnalyticsCacheTest.php               # already exists
        AnalyticsRecalculateTest.php
      Security/
        AuthorizationTest.php                # already exists (expand)
        RateLimitTest.php
        PayloadInjectionTest.php
      Concurrent/
        StudySessionConcurrentTest.php       # already exists
      Health/
        HealthCheckTest.php
      LuaScripts/
        SlidingWindowTest.php                # already exists
        StreakTest.php                        # already exists
        JobDedupTest.php                     # already exists
    Contract/
      UserContractTest.php
      StudySessionContractTest.php
      TechnologyContractTest.php
      DashboardContractTest.php
      ErrorContractTest.php

frontend/
  src/
    __tests__/                               # high-level integration tests
      e2e/                                   # if using embedded Cypress/Playwright
        auth.spec.ts
        sessions.spec.ts
        dashboard.spec.ts
    api/
      __tests__/
        client.spec.ts                       # already exists
        auth.api.spec.ts
        sessions.api.spec.ts
    stores/
      __tests__/
        auth.store.spec.ts                   # already exists
        analytics.store.spec.ts              # already exists
        sessions.store.spec.ts
        technologies.store.spec.ts           # already exists
        goals.store.spec.ts                  # already exists
        notifications.store.spec.ts
    composables/
      __tests__/
        useWebSocket.spec.ts                 # already exists (expand)
        useSessionTimer.spec.ts              # already exists
        useFormValidation.spec.ts            # already exists
        useDebounce.spec.ts                  # already exists
        usePagination.spec.ts                # already exists
        useSort.spec.ts                      # already exists
        useAsync.spec.ts                     # already exists
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
            SessionCard.spec.ts              # already exists (expand)
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
        dateUtils.spec.ts                    # already exists
        formatters.spec.ts                   # already exists
        validators.extended.spec.ts          # already exists
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

## 11. Automation Plan

### 11.1 Phase 1 — Foundation (Weeks 1-2)

**Goal:** Cover CRITICAL priority scenarios.

| Task | Estimate | Result |
|---|---|---|
| Expand AuthTest.php with change-password and tokens | 4h | AUTH-P*, AUTH-T* |
| Create StudySessionStartEndTest.php | 4h | SESS-F01 to F07 |
| Expand AuthorizationTest.php with technologies and delete | 3h | SEC-Z01 to Z07 |
| Create HandlerTest.php (all status codes) | 3h | ERR-* |
| Expand auth.store.spec.ts and add guards.spec.ts | 4h | GUARD-01 to 07 |
| Set up MSW in frontend | 3h | Mock infrastructure |
| Create user, session, technology, dashboard fixtures | 2h | Deterministic data |
| Create complete RecalculateMetricsJob tests | 3h | P-C09 |
| Create cache invalidation tests | 3h | INT-C01 to C06 |

**Estimated total: ~29h**

### 11.2 Phase 2 — Functional Coverage (Weeks 3-4)

**Goal:** Cover HIGH priority scenarios.

| Task | Estimate | Result |
|---|---|---|
| Create TechnologySearchTest.php | 2h | TECH-11 to 13 |
| Create StudySessionFiltersTest.php | 3h | SESS-L01 to L03 |
| Create RateLimitTest.php (all endpoints) | 4h | SEC-RL* |
| Create AnalyticsDashboardTest.php and RecalculateTest.php | 4h | DASH-* |
| Create listener tests (all 7) | 4h | INT-LJ* |
| Create event tests (broadcast) | 3h | INT-B* |
| Create LoginForm.spec.ts and RegisterForm.spec.ts | 4h | P-A11 |
| Expand useWebSocket.spec.ts | 3h | WS-F*, SYNC-* |
| Create sessions.store.spec.ts | 2h | Sessions in frontend |

**Estimated total: ~29h**

### 11.3 Phase 3 — Contract and E2E (Weeks 5-6)

**Goal:** Ensure consistency between layers and user flows.

| Task | Estimate | Result |
|---|---|---|
| Create contract tests in backend (5 resources) | 6h | INT-01 to 07 |
| Set up Cypress or Playwright | 4h | E2E infrastructure |
| Implement 8 E2E flows | 12h | F1 to F8 |
| Create HealthCheckTest.php | 2h | HC-01 to 07 |
| Create PayloadInjectionTest.php | 3h | SEC-V* |

**Estimated total: ~27h**

### 11.4 Phase 4 — Resilience and Performance (Weeks 7-8)

**Goal:** Cover failure scenarios and basic performance metrics.

| Task | Estimate | Result |
|---|---|---|
| Create resilience tests (Redis/Reverb down) | 6h | All resilience scenarios |
| Set up k6 with 3 scenarios | 4h | Basic performance |
| Create PerformanceSeeder | 2h | Load data |
| Middleware tests (SlidingWindow, EnsureJson, Timezone) | 4h | P-M03, P-M07 |
| UI component snapshot tests | 4h | P-B06 |

**Estimated total: ~20h**

---

## 12. CI/CD Execution Plan

The YAML in this section is a **target example / conceptual**. The **actual** pipeline is described in section **1.4** and in `.github/workflows/backend-ci.yml` and `frontend-ci.yml`.

### 12.1 PR Pipeline (every pull request)

```yaml
# .github/workflows/test.yml (conceptual — not the actual repo file)
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
      - vendor/bin/pint --test         # Formatting
      - vendor/bin/phpstan analyse     # Static analysis

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
      - Generate backend contract snapshots
      - Validate against frontend types
```

### 12.2 Merge to Main Pipeline

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
      - Wait for health check OK
      - npx playwright test            # or cypress run
      - docker compose down

  deploy:
    needs: [e2e]
    # ... deploy steps
```

### 12.3 Weekly Pipeline

```yaml
name: Performance + Extended
on:
  schedule:
    - cron: '0 3 * * 1'  # Monday 3am

jobs:
  performance:
    steps:
      - docker compose up -d
      - Performance seed (5000 sessions)
      - k6 run load-test.js
      - Collect and compare metrics
```

---

## 13. Uncovered Risks

### 13.1 Accepted or Partially Covered Risks

| Risk | Reason | Alternative Mitigation |
|---|---|---|
| Complete accessibility (a11y) tests | Not prioritized in this plan | Lighthouse CI + manual review |
| Visual regression tests (screenshots) | Tools like Percy/Chromatic have cost | Component snapshot tests as proxy |
| Real WebSocket performance under load | Hard to simulate with k6 | Production monitoring |
| Real email integration (Mailpit) | Dev environment only | Mock in tests, manual verification |
| Multi-browser E2E (Safari, Firefox) | CI cost | Playwright with browser matrix |
| Mobile responsiveness | Not prioritized in this plan | Media queries + Lighthouse |
| Dependency upgrades (breaking changes) | Not a functional test | Dependabot + CI catch |
| Production queue observability | Horizon UI, not testable via automation | Stopped queue alerts |

### 13.2 Project Gaps That Hinder Testing

| Gap | Impact on Tests | Recommendation |
|---|---|---|
| Goals without backend | Impossible to test sync | Create goals API or document as permanent local-only |
| Authorization not centralized in Policies | Ownership verified differently in each module | Centralize in Policy to standardize 403/404 semantics |
| Pinia + Vue Query coexistence for same data | Hard to know which is source of truth in tests | Define and document responsibility per domain |
| Rate limits must follow `AppServiceProvider` + `api.php` | Rate limit tests may fail if docs are stale | Keep README/checklists aligned with code |
| `findForUser` returns 403 for ownership (not 404) | Facilitates IDOR scan (attacker knows resource exists) | Consider returning 404 for all cases |
| No Zod types for all responses | Contract not validated at runtime in frontend | Expand Zod usage for all critical response parsing |
| Charts in ApexCharts with various wrappers in `components/charts` | Theme/config regressions | Cover with component tests on critical charts and unified theme |

---

## 14. Final Recommendations

### 14.1 Immediate Actions (This Sprint)

1. **Expand existing Feature tests** with the CRITICAL scenarios listed in section 9.
2. **Create `HandlerTest.php`** to ensure all status codes return the correct format.
3. **Maintain and extend `frontend/src/router/__tests__/guards.spec.ts`** as new routes or auth rules are added.
4. **Set up MSW** (roadmap) for more realistic component tests in the frontend — **not** integrated in current CI.
5. **Optional — CI coverage gate:** require 80% backend / 70% frontend **only after** defining baseline and a job that fails the PR (currently CI **does not** enforce this threshold).

### 14.2 Medium-Term Actions (Next Month)

1. **Implement contract tests** using JSON snapshots from Resources.
2. **Set up Playwright** for the 8 critical E2E flows.
3. **Create resilience suites** for Redis down, Reverb down, job failure.
4. **Centralize authorization** in Laravel Policies to standardize behavior and facilitate testing.
5. **Align rate limit documentation** with implementation and create snapshot test.

### 14.3 Long-Term Actions (Next Quarter)

1. **Migrate to Pest** gradually (more expressive syntax, better DX).
2. **Add Lighthouse CI** to monitor performance and accessibility.
3. **Implement load tests** with k6 against staging environment.
4. **Create formal API contract** (OpenAPI/Swagger) derived from Resources and validate automatically.
5. **Consider visual regression testing** with Percy or Chromatic for UI components.
6. **Implement mutation testing** (Infection PHP, Stryker JS) to validate test quality.

### 14.4 Test Suite Health Metrics

**Desired** goals (not all enforced as gates in current GitHub Actions):

| Metric | Desired Goal | Tool / Note |
|---|---|---|
| Backend code coverage | > 80% | PHPUnit `--coverage` (CI artifact; no mandatory minimum) |
| Frontend code coverage | > 70% | Vitest `--coverage` (local/roadmap; CI only runs `test:run`) |
| Total CI time (PR) | < 5min | GitHub Actions |
| Flaky test rate | < 1% | Culture + monitoring |
| E2E tests passing | 100% | Playwright/Cypress when E2E pipeline exists |
| Contract tests passing | 100% | Dedicated step when it exists |

### 14.5 Test Culture

- **Golden rule:** No PR without tests for the changed functionality.
- **Boy Scout Rule:** When touching code without a test, add at least 1 test.
- **PR review should include:** Verify tests cover the new scenario, edge cases, and regressions.
- **Flaky tests:** Zero tolerance — fix or quarantine immediately.
- **Test documentation:** Keep this document updated as the project evolves.

---

## 15. Testar a aplicação em execução (ferramentas open-source)

Diferente dos testes de código (seções 3–7), esta seção cobre como validar a **aplicação rodando de verdade** — via navegador real, scan de segurança dinâmico (DAST) e auditoria de UI/UX. Todas as ferramentas são open-source e sem custo.

| O que | Ferramenta (licença) | Comando | Pré-requisito |
|---|---|---|---|
| Funcionalidade (E2E) | Playwright (Apache 2.0) | `make test-e2e` (ou `npm run test:e2e` em `frontend/`) | app no ar em `:8080`; browsers via `npx playwright install chromium` |
| Funcionalidade (E2E, com UI visível) | Playwright | `make test-e2e-headed` | idem |
| Segurança (DAST) | OWASP ZAP (Apache 2.0) via Docker | `make zap-scan` (full) / `make zap-scan ARGS=--baseline` | app no ar em `:8080`; Docker |
| UI/UX (auditoria) | Lighthouse (Apache 2.0) via `npx` | `make lighthouse` | app no ar em `:8080` |
| Segurança (imagens) | Trivy (Apache 2.0) | já roda no CI (`deploy.yml`) | — |
| Segurança (deps/secrets) | composer audit + npm audit + secrets scan | `make security-audit` / `bash monitoring/check-security.sh` | — |

**Como subir a app completa para testes:**

```bash
make setup          # cria os .env (uma vez)
make dev            # sobe nginx, php-fpm, postgres, redis, math-service, node
docker compose up -d reverb horizon scheduler   # WebSocket/filas (fluxos em tempo real)
```

**Fluxo típico de teste da aplicação:**

1. Subir a app (comando acima) e confirmar com `make health` (espera `status: healthy`).
2. Rodar a suíte E2E: `make test-e2e` — specs em `frontend/e2e/` (auth, navigation, technologies, study-sessions, responsive). Relatório HTML em `frontend/playwright-report/`.
3. Rodar o scan de segurança: `make zap-scan` — relatório em `monitoring/reports/zap-report-<timestamp>.html` (alertas HIGH/CRITICAL merecem revisão).
4. Rodar a auditoria de UI/UX: `make lighthouse` — relatório em `monitoring/reports/lighthouse.html` (scores de performance, acessibilidade, SEO).

**Observações importantes:**

- **WAF do nginx** (`docker/nginx/conf.d/studytrack.conf`) bloqueia user-agents de scanners como `nuclei`, `nikto`, `sqlmap` (403). O OWASP ZAP usa UA próprio e **não** é bloqueado — se usar outras ferramentas DAST com UA desses scanners, será necessário fazer spoof de user-agent.
- Os testes E2E **registram usuários novos** a cada execução (`e2e-test-<timestamp>@example.com`), então o banco dev cresce com o uso; resetar quando necessário com `make fresh`.
- O `global-setup.ts` do Playwright **pula os testes** se a app não estiver no ar — a suíte não falha por ambiente desligado.
- Usuário demo com dados de 6 meses: `dev@studytrack.local` / `password` (seed via `make fresh`).
