# Reverse Engineering Checklist — StudyTrackPro

Guide for studying the project and recreating each part by hand, in suggested order (foundations → layers → integration).

---

## Part 1 — What to Study (Concepts and Patterns)

### 1.1 Overall Architecture

- [ ] **Data flow:** Frontend → API (REST) → Controller → Service → Repository → Model/DB; and Event → Listener → Job/Broadcast.
- [ ] **Backend modules:** Auth, StudySessions, Technologies, Analytics (each with Service, DTOs, Repository with interface).
- [ ] **Event-driven:** Past events (e.g., `StudySessionCreated`), dispatched by the Service; Listeners for cache and jobs; Broadcast to Reverb.
- [ ] **Dual schema in PostgreSQL:** `public` (transactional: users, technologies, study_sessions) and `analytics` (aggregated metrics).
- [ ] **API contract:** Laravel Resources ↔ TypeScript types in `frontend/src/types/`; WebSocket payloads in `websocket.types.ts`.

### 1.2 Backend (Laravel)

- [ ] **Thin Controller:** Only receives request, calls Service, returns Resource/response; no business logic.
- [ ] **Form Request:** Validation in classes in `Http/Requests/` (Register, Store, Update, Export, etc.); never in the controller.
- [ ] **Repository pattern:** Interface in `Contracts/`, Eloquent implementation; binding in `RepositoryServiceProvider`.
- [ ] **DTOs:** Readonly objects for input/output between Controller and Service (e.g., `RegisterDTO`, `StudySessionDTO`).
- [ ] **Traits:** `HasUuid` in `BaseModel`; there are `HasAuditLog`, `HasCacheInvalidation`, `HasApiResponse` in `app/Traits/` — check actual usage in each model/controller.
- [ ] **Rate limiting:** Named limiters in `AppServiceProvider` (`login`, `register`, `search`, `sensitive`, `recalculate`, `export`, `health`); routes in `routes/api.php` (incl. `throttle.sliding` on `study-sessions` mutations).
- [ ] **Cache with tags:** Tag-based invalidation (e.g., user sessions) in Listeners.
- [ ] **Sanctum:** Tokens for SPA; protected routes with `auth:sanctum`.
- [ ] **Reverb:** Private channels in `channels.php` (`dashboard.{userId}`); events with defined payload for the frontend.

### 1.3 Frontend (Vue 3 + TypeScript)

- [ ] **Composition API + `<script setup>`:** Standard in components and views.
- [ ] **Pinia:** Stores by domain (auth, sessions, technologies, goals, analytics, notifications, ui); no calls to non-existent APIs (Goals = localStorage).
- [ ] **API modules:** One folder/file per domain (auth.api, sessions.api, etc.); use of the `client` axios with interceptors (token, 401, 429).
- [ ] **Types:** `api.types.ts`, `domain.types.ts`, `websocket.types.ts`, etc.; aligned with Resources and events.
- [ ] **Design system:** Tokens in `variables.css` (colors, spacing, radius, shadows, typography, motion); used in components, no loose values.
- [ ] **Folder structure:** `ui/` (domain-agnostic), `layout/`, `features/` (by domain), `views/`, `composables/`, `stores/`, `api/`, `types/`.
- [ ] **Router:** Auth guard; root layout with children; `meta.title` for page title.
- [ ] **WebSocket:** `useWebSocket` composable (Echo, private channel, listeners, store updates, cleanup in `onUnmounted`).

### 1.4 Integration and Quality

- [ ] **End-to-end flow:** E.g., "Start session" → POST start → Event → Listener → Broadcast → frontend listens and updates store/UI.
- [ ] **Project checklists:** [CHECKLIST-E-PROMPTS.md](CHECKLIST-E-PROMPTS.md), [../../frontend/docs/CHECKLIST-FRONTEND.md](../../frontend/docs/CHECKLIST-FRONTEND.md); throttles in the same file's table.
- [ ] **Environment variables:** [ENV-VARS.md](ENV-VARS.md), `backend/.env.example` and `frontend/.env.example`.

---

## Part 2 — What to Recreate (In Practice)

Suggestion: recreate on a branch or parallel project, in the order below. Check off each item as you complete it.

### 2.1 Backend Foundation

- [ ] **Migrations (transactional):** Study the order and content of `database/migrations/transactional/` (extensions, users, technologies, study_sessions, tokens, indexes, triggers). Recreate a "new" migration (e.g., an auxiliary table) following the pattern.
- [ ] **Models:** `BaseModel` (UUID, audit), `User`, `Technology`, `StudySession`. Recreate a lean model with UUID and a relationship (e.g., Technology hasMany StudySessions).
- [ ] **Repository:** Interface + Eloquent (e.g., `TechnologyRepositoryInterface` + `EloquentTechnologyRepository`). Register in the provider. Recreate a `find(int|string)` method and a `getAllForUser()`.
- [ ] **DTO:** A readonly class (e.g., `TechnologyDTO` with id, name, slug, etc.). Recreate a creation DTO (e.g., `StoreTechnologyDTO`).
- [ ] **Form Request:** A class (e.g., `StoreTechnologyRequest`). Recreate a validation with rules and messages.
- [ ] **Resource:** A class (e.g., `TechnologyResource`) that formats the model to JSON. Recreate a Resource with conditional attributes.
- [ ] **Service:** A method that uses Repository and returns DTO/Resource (e.g., `TechnologyService::create`). Recreate a "list" method with optional filter.
- [ ] **Controller:** A GET route and a POST route that call the Service and return Resource/JsonResponse. Recreate the index/store pair for a simple resource.
- [ ] **Route + throttle:** Register in `api.php` with `v1` prefix, `auth:sanctum` middleware, and a named throttle. Recreate a protected route with throttle 60,1.

### 2.2 Events and Jobs (Backend)

- [ ] **Event:** A class (e.g., `StudySessionCreated`) with minimal payload. Recreate a "SomethingCreated" event with an ID.
- [ ] **Listener:** A listener that invalidates cache (tag) or dispatches a job. Recreate a listener that just logs or invalidates a tag.
- [ ] **Job:** A job that runs on a queue (e.g., `RecalculateMetricsJob`). Recreate a "DummyJob" that processes an ID and uses `ShouldBeUnique` if applicable.
- [ ] **Registration:** `EventServiceProvider`: event → listener; queue configured (Horizon). Recreate the event-listener pair and run with `queue:work`.

### 2.3 WebSocket (Backend)

- [ ] **Channel:** `routes/channels.php` — private channel `dashboard.{userId}` with authorization by `$user->id`. Recreate the authorization rule by reading the current code.
- [ ] **Broadcast in listener:** A listener that uses `broadcast(new SessionStarted(...))` for the user's channel. Recreate a simple broadcast event and send a fixed payload.
- [ ] **Config:** `config/broadcasting.php` (reverb); REVERB_* variables in `.env`. Ensure the health check includes Reverb if applicable.

### 2.4 Analytics Schema (Backend)

- [ ] **Analytics migrations:** Structure in `database/migrations/analytics/` (schema, metrics tables). Study one table (e.g., `daily_minutes`) and recreate a new migration in the same schema.
- [ ] **Analytics Models:** `Analytics/DailyMinutes`, etc., with `$connection`/schema. Recreate a model that points to the `analytics` schema.
- [ ] **AnalyticsService + repository:** How the service aggregates and uses the repository. Recreate a "getLast7Days" method that returns data from the repository.

### 2.5 Frontend — API and Types

- [ ] **axios client:** `baseURL`, request interceptor (token), response interceptor (401 redirect, 429 toast). Recreate `client.ts` and a `getApiErrorMessage` helper.
- [ ] **Types:** A type module (e.g., `Technology`, `StudySession`) mirroring the API. Recreate interfaces for a resource (e.g., Technology + TechnologyForm).
- [ ] **API module:** A file (e.g., `technologies.api.ts`) with functions that call the client (getAll, getById, create, update, delete). Recreate a module for a minimal resource.

### 2.6 Frontend — Store and Composables

- [ ] **Pinia Store:** State, getters, actions that call the API module and handle error/loading. Recreate a minimal store (e.g., list + fetchList).
- [ ] **Composable:** A composable (e.g., `useTechnologies`) that uses the store and exposes data + actions. Recreate a composable that calls an action and returns refs.
- [ ] **useWebSocket:** Echo connection, private channel subscribe, listeners (session.started, metrics.updated, etc.), store updates, and cleanup. Recreate only the subscribe + one listener that updates a local ref.

### 2.7 Frontend — UI and Design System

- [ ] **variables.css:** Colors, spacing, radius, shadows, typography, motion, dark theme. Recreate a minimal tokens file (5 colors, 3 spacings, 1 radius).
- [ ] **Base component:** A component in `ui/` (e.g., BaseButton) with props, slots, variants. Recreate a button with primary/secondary variant and size.
- [ ] **Layout:** AppLayout, AppSidebar, route structure with layout. Recreate a layout with sidebar and content area.
- [ ] **Page:** A view that uses layout, store/composable, UI components, and handles loading/error/empty states. Recreate a "List of X" page with a table or cards.

### 2.8 Frontend — Router and Auth

- [ ] **Routes:** Structure in `router/routes/` (auth, dashboard, sessions, etc.) and assembly in `index.ts`. Recreate a route file with two routes (list + detail) and meta.title.
- [ ] **Guard:** `setupAuthGuard`: check `requiresAuth`, redirect to login, store "redirect" after login. Recreate the guard by reading the current one.
- [ ] **Login flow:** LoginView → auth.api.login → auth store (token + user) → redirect. Recreate the flow in a minimal screen (form + call + redirect).

### 2.9 End-to-End Integration

- [ ] **Full CRUD:** One entity (e.g., Technologies): list in frontend, create, edit, delete; backend with Controller + Service + Repository + Form Request + Resource. Recreate the flow for a new "Tags" entity (just id and name) in the backend and a frontend page.
- [ ] **Study session:** "Start" in frontend → POST start → SessionStarted event → broadcast → frontend receives and updates (e.g., "active session" banner). Reproduce the flow with a "Simulate start" button and a listener that shows a toast.
- [ ] **Dashboard + analytics:** GET dashboard call in the frontend, data usage (widgets/charts). Recreate a widget that calls `analytics.api.getDashboard()` and displays a number.

### 2.10 Tests and Documentation

- [ ] **Feature test (backend):** A test that calls an API route (auth, payload, status). Recreate a test for GET list and one for POST create with validation.
- [ ] **Unit test (backend):** A Service or Repository method test. Recreate a test that mocks the repository and verifies the service return.
- [ ] **Postman Collection:** Endpoints documented with variables and examples. Add a new endpoint to the existing collection.
- [ ] **README / ENV:** Setup instructions and variable list. Update README with a "Run Reverb" step and ENV with a new variable used in the checklist.

---

## Part 3 — Suggested Study Order (Per Day/Week)

| Phase | Focus | Checklist Items |
|-------|-------|-----------------|
| 1 | Backend: models and data access | 2.1 (migrations, models, repository, DTO, form request, resource, service, controller, route) |
| 2 | Backend: events and queues | 2.2 (event, listener, job, registration) |
| 3 | Backend: WebSocket and analytics | 2.3 and 2.4 |
| 4 | Frontend: network and state | 2.5 and 2.6 (client, types, API module, store, composable, useWebSocket) |
| 5 | Frontend: UI and routes | 2.7 and 2.8 (variables, component, layout, page, routes, guard, login) |
| 6 | Integration and quality | 2.9 and 2.10 (E2E CRUD, session flow, dashboard, tests, Postman, docs) |

---

## Quick References in the Repository

- **API Routes:** `backend/routes/api.php`
- **WebSocket Channels:** `backend/routes/channels.php`
- **Events/Listeners:** `backend/app/Events/`, `backend/app/Listeners/`, `backend/app/Providers/EventServiceProvider.php`
- **Modules:** `backend/app/Modules/` (Auth, StudySessions, Technologies, Analytics)
- **Resources:** `backend/app/Http/Resources/`
- **Frontend API:** `frontend/src/api/` (client, modules, queryKeys)
- **Types:** `frontend/src/types/` (api.types.ts, websocket.types.ts)
- **Design Tokens:** `frontend/src/assets/styles/variables.css`
- **Router:** `frontend/src/router/index.ts`, `frontend/src/router/guards.ts`, `frontend/src/router/routes/`
- **Official Checklists:** [CHECKLIST-E-PROMPTS.md](CHECKLIST-E-PROMPTS.md), [../../frontend/docs/CHECKLIST-FRONTEND.md](../../frontend/docs/CHECKLIST-FRONTEND.md)
- **Env:** [ENV-VARS.md](ENV-VARS.md), `backend/.env.example`, `frontend/.env.example`
- **Technical:** [../technical/DOCUMENTACAO_TECNICA.md](../technical/DOCUMENTACAO_TECNICA.md)
