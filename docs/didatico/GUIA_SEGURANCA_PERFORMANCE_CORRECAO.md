# Didactic Guide: Security, Performance, and Correctness — StudyTrack Pro

**Objective:** serve as study material on how the repository handles common risks, where to look in the code, and what depends on the environment (`.env`, TLS, deploy).

**Audience:** those learning full-stack (Vue + Laravel) or reviewing the project.

---

## 1. Mind Map: Three Layers

| Layer | What It Means | Where to See in Repo |
|-------|---------------|----------------------|
| **Security** | Authentication, authorization, abuse limits, sensitive data | Sanctum, throttles, Form Requests, ownership in services |
| **Performance** | Latency, CPU/IO cost, avoiding unnecessary work | Redis/cache, jobs, `LogApiRequests` in `terminate`, yield in PDF |
| **Correctness** | Correct behavior in edge cases (0 min, network, 401) | Tests, Axios guards, `sessionValidated`, composables |

Related documents: [operations/SECURITY_AUDIT.md](../operations/SECURITY_AUDIT.md), [technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md](../technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md).

---

## 2. Security (Project-Applied Summary)

### 2.1 Authentication and Tokens (Frontend)

- The **Sanctum JWT/token** is stored in `localStorage` (`auth.store.ts`). This is common in SPAs; in environments with strong zero-XSS requirements, evaluate httpOnly cookies + CSRF (trade-off with SPA).
- The **Axios client** (`api/client.ts`) injects `Authorization: Bearer`, prevents 401 storms with `handlingUnauthorized`, and blocks requests with "unvalidated" token until `fetchMe` completes (`SESSION_NOT_READY`), aligned with the route guard.

**Concept:** *session fixation / dead token* — if the user has `user` cached but the token expired, the guard forces validation on the API before releasing protected routes.

### 2.2 Laravel API

- Sensitive routes use `auth:sanctum` and **rate limiting** by group (`routes/api.php`): login/register more restricted, reads 60/min, writes with throttle and sliding window on session mutations.
- **Validation** goes through Form Requests; prevents mass assignment and unexpected data in controllers.
- **CORS** (`config/cors.php`): no `*` by default — origins come from `CORS_ALLOWED_ORIGINS` (comma-separated list). In production, set real origins.

### 2.3 WebSocket (Reverb)

- **Private** channel `dashboard.{userId}`; broadcast authentication with the same Bearer. The `useWebSocket.ts` composable only connects with `sessionValidated` and disconnects on logout.

### 2.4 Logging

- `LogApiRequests` logs method, path, `user_id`, status, and duration — it does **not** log request bodies (reduces accidental PII leakage in logs).

---

## 3. Performance (Patterns Used)

| Area | Idea | Project Example |
|------|------|-----------------|
| API | Don't block the client with heavy logging | Log in `terminate()` after response |
| Frontend | Don't freeze the UI on large reports | `setTimeout(0)` every N lines in PDF (`usePdfGenerator.ts`) |
| Backend | Limit bursts | Throttle + `SlidingWindowRateLimit` on write routes |
| Data | Cache and jobs | Documented in cache and jobs reference in `docs/reference/` |

**Note:** "Performance" also includes **perceived UX**: spinners, empty states, and TanStack Query invalidation after WebSocket events prevent stale data on screen.

---

## 4. Correctness (Bugs and Edge Cases)

### 4.1 Fixed Example: Zero Duration in PDF

In PDF reports, `duration_min === 0` should not be treated as "no data". The local `formatDuration` function in `usePdfGenerator.ts` distinguishes `null` (absent) from `0` (zero minutes).

### 4.2 Automated Tests

- Frontend: Vitest for guards, stores, composables (`frontend/src/**/__tests__`).
- Backend: PHPUnit for contracts, security, and Lua/Redis integration when applicable.

See [testing/ESTRATEGIA_TESTES.md](../testing/ESTRATEGIA_TESTES.md).

---

## 5. Code Change Checklist

1. **New endpoint:** Form Request + policy/ownership in service + appropriate throttle.
2. **New data in UI:** type in `types/`, optional Zod schema if it exists in the flow.
3. **Secret or URL:** only in `.env`, never commit real values.
4. **Large list on client:** consider pagination already used in session listings.

---

## 6. Useful External References

- [OWASP Top 10](https://owasp.org/Top10/) — common language for risks.
- [Laravel Sanctum](https://laravel.com/docs/sanctum) and [CORS](https://laravel.com/docs/routing#cors) documentation.

This guide does not replace professional audit or pentest; it consolidates what the repository already documents and implements for learning purposes.
