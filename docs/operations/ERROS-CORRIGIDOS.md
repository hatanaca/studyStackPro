# Fixed Errors Log — StudyTrackPro

This document records errors identified and fixed in the project, with a brief explanation of the cause and solution. Use it as history and reference to avoid regressions.

---

## How to Record a New Error

For each fix, add an entry in the **Fix Log** section with:

- **Date** (optional): when it was fixed
- **Description**: what was wrong (symptom)
- **Cause**: why it happened
- **Fix**: what was changed (files and changes)
- **How to avoid**: tip to prevent the problem from recurring

---

## Fix Log

### 1. Empty APP_KEY on first setup (documentation)

| Field | Detail |
|-------|--------|
| **Description** | Anyone who copies only `.env.example` to `.env` in the backend ends up with `APP_KEY=` empty. Laravel requires an application key; without it, the application may fail at runtime (session, encryption, etc.). |
| **Cause** | The `.env.example` didn't make it explicit that generating the key after copying the file is mandatory. |
| **Fix** | A comment was added to the `APP_KEY` line in `backend/.env.example` stating: *"Generate a key after copying to .env: php artisan key:generate"*. This way, the developer knows the next step. |
| **File(s)** | `backend/.env.example` |
| **How to avoid** | On first setup, after `cp .env.example .env`, run `php artisan key:generate`. In automated workflows (e.g., `make setup`), include this command. |

---

### 2. Backend tests fail with "Connection refused" when running outside Docker

| Field | Detail |
|-------|--------|
| **Description** | When running `php artisan test` directly on the machine (outside Docker), all Feature tests and several Unit tests fail with `SQLSTATE[08006] connection to server at "127.0.0.1", port 5432 failed: Connection refused`. |
| **Cause** | The `phpunit.xml` sets `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, and `DB_DATABASE=studytrack_test`. If PostgreSQL is not running locally (or the test database doesn't exist), the connection fails. The project uses PostgreSQL-specific features (schema `analytics`, UUIDs), so switching to SQLite for tests is not feasible. |
| **Fix** | Documentation: backend tests **require** PostgreSQL. The recommended way is to run tests **inside the Docker environment**, where Postgres is already available: `make test-back` (which creates the `studytrack_test` database and runs `php artisan test` in the container). If you want to run `php artisan test` on the host, you need PostgreSQL on 127.0.0.1:5432 and the `studytrack_test` database (e.g., `createdb studytrack_test`). |
| **File(s)** | `docs/operations/ERROS-CORRIGIDOS.md`, `Makefile` (already contains `test-db-setup` and `test-back`). |
| **How to avoid** | Use `make test` or `make test-back` with the Docker stack running (`make dev`). Or document in README/contributing that backend tests require Postgres. |

---

### 3. Vue warning "onMounted/onUnmounted called when there is no active component instance" in useSessionTimer test

| Field | Detail |
|-------|--------|
| **Description** | In the `useSessionTimer` composable test, calling `useSessionTimer()` directly in the test file (outside a component) causes Vue to emit warnings: *"onMounted is called when there is no active component instance"* and the same for `onUnmounted`. The composable uses these lifecycle hooks internally. |
| **Cause** | Vue lifecycle hooks can only be registered during a component's `setup()`. Calling the composable in the test body doesn't provide a component instance, so Vue warns. |
| **Fix** | The test was changed to mount a wrapper component (`defineComponent` that uses `useSessionTimer()` in `setup()`). This way, the composable runs in a component context and the hooks are properly associated. A default mock of `sessionsApi.getActive()` was also added in `beforeEach` (response without active session), so the `onMounted` calling `fetchActive()` doesn't break during destructuring when the mock wasn't defined in the first test. |
| **File(s)** | `frontend/src/composables/__tests__/useSessionTimer.spec.ts` |
| **How to avoid** | When testing composables that use `onMounted`, `onUnmounted`, or other lifecycle hooks, run the composable inside a component and use `mount()` from Vue Test Utils; don't call the composable directly in describe/it. |

---

### 4. SessionFilters broke if `modelValue` was undefined (usage without v-model)

| Field | Detail |
|-------|--------|
| **Description** | The `SessionFilters` component accessed `props.modelValue.date_from`, `props.modelValue.date_to`, etc. without handling the case where the parent didn't pass `modelValue` (e.g., `<SessionFilters />` without `v-model`). This could cause a runtime error when accessing a property of `undefined`. |
| **Cause** | The `modelValue` prop was required in the type, but in Vue the parent may not pass the prop; at runtime `modelValue` can be `undefined`. |
| **Fix** | The prop was made optional with `withDefaults(..., { modelValue: () => ({}) })` and all accesses now use optional chaining: `props.modelValue?.date_from ?? ''`, etc. The emit type for `update:modelValue` was explicitly typed as the filter object. |
| **File(s)** | `frontend/src/features/sessions/components/SessionFilters.vue` |
| **How to avoid** | In components that receive `modelValue` for v-model, consider default values and optional chaining when the component may be used without binding. |

---

### 5. TechDistributionWidget: selected index out of bounds when metrics change

| Field | Detail |
|-------|--------|
| **Description** | When selecting a chart slice (pie/donut) and then the metrics data changes (e.g., fewer technologies), the index stored in `selectedSlice` could become greater than or equal to the new `slices.value.length`. Calls to `getAngles(selectedSlice)` and `slicePct(selectedSlice)` accessed `slices.value[i]` and threw an error (undefined). |
| **Cause** | There was no verification that the selected index was still valid after the slice list was recalculated. |
| **Fix** | A guard for out-of-range index was added in `getAngles` and `slicePct` (safe return). A `watch` on `slices.value.length` was added that resets `selectedSlice` and `phase` when the index goes out of bounds. |
| **File(s)** | `frontend/src/features/dashboard/components/TechDistributionWidget.vue` |
| **How to avoid** | When storing an index in a reactive list, revalidate or reset when the list changes (watch on length or the list itself). |

---

### 6. truncate(): slice with negative length when maxLength < 3

| Field | Detail |
|-------|--------|
| **Description** | The `truncate(text, maxLength)` function used `text.slice(0, maxLength - 3) + '...'`. For `maxLength` 2 or 1, this became `slice(0, -1)` or `slice(0, -2)`, truncating from the end instead of respecting the limit and potentially producing unexpected results. |
| **Cause** | Direct use of `maxLength - 3` without ensuring the first argument of `slice` wasn't negative. |
| **Fix** | Used `Math.max(0, maxLength - 3)` for the length to keep before the ellipsis. Unit tests for `truncate` (including `maxLength` 2 and 3) were added in `formatters.spec.ts`. |
| **File(s)** | `frontend/src/utils/formatters.ts`, `frontend/src/utils/__tests__/formatters.spec.ts` |
| **How to avoid** | In truncation functions, ensure indices/lengths are never negative (e.g., `Math.max(0, n)`). |

---

## Static Analysis Performed (No Critical Errors)

The repository analysis checked:

- **Lint**: No lint errors reported in `backend/app` and `frontend/src`.
- **Imports**: No broken imports found (components, composables, types, and utilities exist and match the imports).
- **API Contract**: Routes in `backend/routes/api.php` (prefix `v1`) and modules in `frontend/src/api/` are aligned. Goals is frontend-only (localStorage), as documented in `docs/operations/GOALS-FRONTEND-ONLY.md`.
- **Migrations**: Syntax and order of migrations (transactional and analytics) are consistent.
- **CORS**: `backend/config/cors.php` uses empty `allowed_origins` when `CORS_ALLOWED_ORIGINS` is not defined — set the variable for allowed origins (see [ENV-VARS.md](ENV-VARS.md)).
- **Authentication**: Frontend uses Bearer token; `withCredentials: false` on the Axios client and `supports_credentials => false` in CORS are consistent.

**Recommendation**: Run tests after changes. In the backend use `make test-back` (with Docker) or ensure PostgreSQL + `studytrack_test` database if running `php artisan test` on the host. In the frontend: `cd frontend && npm run test:run`. Also run `npm run type-check` in the frontend before committing.

---

## How to Run Tests

| Environment | Command | Notes |
|-------------|---------|-------|
| Backend (recommended) | `make test-back` | Requires Docker (`make dev`). Creates `studytrack_test` and runs PHPUnit in the container. |
| Backend (on host) | `cd backend && php artisan test` | Requires PostgreSQL on 127.0.0.1:5432 and `studytrack_test` database. |
| Frontend | `cd frontend && npm run test:run` | Vitest; doesn't depend on external services. |
| All | `make test` | Backend (Docker) + frontend. |

---

## Quick Summary

| # | Problem | Status |
|---|---------|--------|
| 1 | Empty APP_KEY on first setup (missing instruction) | Fixed (documentation in `.env.example`) |
| 2 | Backend tests fail with Connection refused (PostgreSQL) | Documented (use `make test-back` or have local Postgres) |
| 3 | Vue lifecycle warning in useSessionTimer test | Fixed (wrapper component + default mock) |
| 4 | SessionFilters broke with undefined modelValue | Fixed (optional prop + optional chaining) |
| 5 | TechDistributionWidget index out of bounds | Fixed (guard in getAngles/slicePct + watch) |
| 6 | truncate() with maxLength < 3 | Fixed (Math.max(0, …) + tests) |

*Last updated: bug search of all types, TechDistributionWidget fixes, formatters, and logging in March 2025.*
