# Prompt: Implement Backend Improvements for StudyTrackPro

Use this prompt when you want the Composer (or another agent) to **execute** improvements in the StudyTrackPro backend. Tasks are prioritized. You can request "implement all high-priority items" or "implement only item X".

**Context:** Laravel 11, PHP 8.2+, modules in `app/Modules/` (Auth, StudySessions, Technologies, Analytics), routes in `routes/api.php`, events/listeners/jobs for cache and Reverb. Backend agent rules: `docs/agents/prompt-agente-backend-studytrackpro.md`.

---

## Agent Instructions

You must implement the backend improvements listed below in the indicated order (or only those the user requests). For each item:

1. **Don't break** existing contracts with the frontend (API payloads and WebSocket events); if you need to change a response or event, document and notify about frontend impact.
2. Keep existing **tests** passing and add tests for new or changed functionality.
3. Follow project conventions: Services orchestrate rules, Repositories access data, Events/Listeners for side effects (cache, broadcast, jobs).

---

## High Priority Improvements

### 1. Goals API in Backend (or Explicit Decision Not to Have One)

**Objective:** The frontend has Goals stores, views, and routes; currently there is no Goals API in the backend. Either implement the API or disable/remove Goals in the frontend and document the decision.

**Option A — Implement Goals API:**

- Create migration(s) for goals (e.g., `goals` table: user_id, type [daily/weekly], target value in minutes, period, etc.).
- Create `Goal` model (or equivalent), with scopes and relationships.
- Create `app/Modules/Goals/` module: Repository Contract, EloquentGoalRepository, GoalService, DTOs.
- Register routes in `api.php`: list, create, update, delete goals (authenticated with `auth:sanctum`).
- Create Controller (e.g., `GoalsController`), Form Request for validation.
- Dispatch events if needed (e.g., GoalCreated for analytics); integrate with metrics calculation if "daily goal" is used on dashboard.
- Add Feature tests for Goals CRUD and Unit tests for GoalService.
- Document in README or docs that Goals is now persisted in the backend.

**Option B — Don't Implement (Goals frontend-only):**

- Document in README or docs that Goals is local only (localStorage/store) with no backend persistence.
- Ensure the frontend doesn't call non-existent endpoints (remove or mock `goals.api.ts` if it makes real calls).

**Deliverable:** Either functional API + tests + docs, or explicit docs + frontend adjusted to not depend on a Goals API.

---

### 2. Update Postman Collection with Analytics Export Endpoint

**Objective:** The API documentation (Postman) should include the actual analytics export endpoint.

**Action:**

- Locate the Postman collection in `docs/` (e.g., `StudyTrack_API_Collection.postman.json` or similar).
- Add a request for `GET /api/v1/analytics/export` (or the correct path) with documented parameters: period (date_from, date_to), format (csv, xlsx, etc.), if applicable.
- Include required environment variables (token, base URL) and response/headers example.
- If another documentation format exists (OpenAPI, Markdown), update it too.

**Deliverable:** Postman collection updated and committed.

---

### 3. Feature/Unit Tests for Analytics Export Endpoint

**Objective:** The export endpoint should not lack test coverage.

**Action:**

- Identify the controller and service handling the export (e.g., `AnalyticsController@export`, `AnalyticsService`).
- Create or complete Feature tests: authenticated call with valid parameters; expected format return (CSV/Excel); parameter validation (invalid dates, max period); unauthenticated user returns 401.
- If there's file generation logic in the Service, add Unit tests for that logic.
- Ensure tests pass with `php artisan test` or `make test`.

**Deliverable:** Tests committed and verification that `make test` (backend) passes.

---

### 4. Align WebSocket Event Payloads with Frontend

**Objective:** Broadcast events (SessionStarted, SessionEnded, MetricsUpdated, etc.) must send exactly the structure expected by the frontend (types in `websocket.types.ts`).

**Action:**

- List broadcast events in the backend (BroadcastSessionStarted, BroadcastSessionEnded, BroadcastMetricsUpdate, BroadcastMetricsRecalculating).
- Check the current structure of each event (class properties or payload).
- Compare with what the frontend expects (e.g., `technology.slug` vs `technology.id`); adjust the backend to send necessary fields (e.g., include `slug` in the SessionStarted technology object).
- Document in an Event comment or README the structure of each event for future reference.

**Deliverable:** Events adjusted; tests verifying the payload (optional); doc or comment with the structure.

---

### 5. Unify Demo Data Seeders

**Objective:** Avoid duplication (e.g., two DemoDataSeeds or duplicate entries) and have a single entry point for demo data.

**Action:**

- Identify all seeders that create demo data (e.g., DemoDataSeeder, GenericTwoMonthsDailyStudySeeder, StudySpreadsheetUserSeeder).
- Decide on a single flow: for example, `DatabaseSeeder` calls `DemoDataSeeder`, which in turn calls other necessary seeders in the correct order.
- Remove or deprecate duplicate seeders; ensure `php artisan db:seed` (or `make fresh`) produces a consistent state without data duplication.
- Document in README how to run only demo data, if applicable.

**Deliverable:** Seeders reorganized; README updated; no active duplicate seeders.

---

## Medium Priority Improvements

### 6. OpenAPI/Swagger API Documentation

**Objective:** Have an OpenAPI (Swagger) spec generated or maintained for the API, in addition to the Postman collection.

**Action:**

- Evaluate using Scramble, L5-Swagger, or controller/resource annotations to generate OpenAPI.
- Configure spec generation (e.g., `/api/documentation` route or `openapi.yaml` file).
- Ensure main endpoints (auth, technologies, sessions, analytics, export) are documented with parameters and responses.
- Optional: use the spec to generate TypeScript types in the frontend.

---

### 7. Documented Rate Limiting and Throttles

Document in README or docs which routes have throttle (e.g., login 5 req/min, export 10 req/min), so integrators and frontend know the expected behavior on 429.

---

### 8. E2E or Integration Tests for Critical Flows

Evaluate adopting Laravel Dusk or integration tests covering: login → dashboard → start session → receive WebSocket event → end session. This can be done in conjunction with the frontend (Playwright/Cypress) or backend only simulating a client.

---

### 9. Documented Environment Variables

**Objective:** A single reference point for backend variables (and, if possible, frontend).

**Action:**

- Ensure `.env.example` contains all variables needed to run the API, Reverb, Horizon, Redis, PostgreSQL, etc., with description in comments.
- Optional: create `docs/env.md` or a README section listing each variable and its purpose.

---

## Low Priority Improvements

### 10. PHPStan Level 6 or Higher

Raise the PHPStan level in `phpstan.neon` to 6 (or more) and fix reported issues, improving type safety in the backend.

### 11. Laravel Telescope in Local Environment (Optional)

The project does **not** include Telescope by default. If the team wants a debug UI: `composer require laravel/telescope --dev`, publish assets, restrict to `local`/IPs and **never** expose in production without hardening. Already used alternative: logs, `LogApiRequests`, Horizon, and `php artisan queue:failed`.

### 12. Command or Job to Clean Old Data

If data retention is needed (e.g., old sessions or logs), consider an Artisan command or scheduled job to archive or delete data per defined policy.

---

## Pre-Completion Checklist

- [ ] `php artisan test` (or `make test` in backend) passes.
- [ ] No API contract or WebSocket event broken without documenting and (if possible) notifying the frontend.
- [ ] Migrations run in order without error (`php artisan migrate`).
- [ ] README or docs updated when the improvement changes behavior or setup.

---

## How to Use This Prompt

- **Implement all (high):** "Implement all high-priority improvements from docs/agents/prompt-implementar-melhorias-backend.md."
- **Implement one item:** "Implement only item 2 (Postman) from docs/agents/prompt-implementar-melhorias-backend.md."
- **Implement by topic:** "Implement the documentation-related items (2, 6, and 9) from docs/agents/prompt-implementar-melhorias-backend.md."

Include relevant files in context (e.g., `routes/api.php`, `app/Events/`, `app/Http/Controllers/V1/AnalyticsController.php`, Postman collection) so the agent has immediate reference.
