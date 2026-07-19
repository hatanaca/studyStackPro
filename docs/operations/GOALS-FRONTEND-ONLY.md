# Goals — Frontend Only

The **goals** (objectives for minutes per week, sessions per week, streak) are a **frontend-only** feature in StudyTrackPro.

## Decision

- **There is no** Goals endpoint in the Laravel API (`/api/v1/goals`).
- The frontend persists goals in **localStorage** (key `studytrack.goals`).
- The Pinia store (`goals.store`) and the `api/modules/goals.api.ts` module only read and write in the user's browser.

## Rationale

- Allows launching the goals feature without changing the backend or migrations.
- Goals are per device/browser; there is no cross-device synchronization.
- If backend support is needed in the future (multi-device, reports), the following will be required:
  - migration for the `goals` table,
  - Goals module in the backend (Repository, Service, Controller),
  - CRUD routes and tests,
  - then update `goals.api.ts` to use `apiClient` instead of localStorage.

## Current Contract (Frontend)

- **Types:** `Goal`, `CreateGoalPayload`, `UpdateGoalPayload` in `types/goals.types.ts`.
- **Local API:** `goalsApi.list()`, `goalsApi.create(payload)`, `goalsApi.update(id, payload)`, `goalsApi.delete(id)` in `api/modules/goals.api.ts` (all operate on localStorage).
- **Routes:** `/goals` (GoalsView), Dashboard widget.

## Checklist for Future Goals API

If implementing Goals in the backend:

- [ ] `goals` migration (user_id, type, target_value, current_value, status, start_date, end_date, JSON meta).
- [ ] `Goal` model, enum for type/status.
- [ ] `app/Modules/Goals/` module (Repository, Service, DTOs).
- [ ] GET/POST/PUT/DELETE routes with throttle and auth.
- [ ] Form Requests and Resources.
- [ ] Feature tests and update `goals.api.ts` to call the API.
