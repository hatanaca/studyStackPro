# Security Audit — StudyTrack Pro

**Original report date:** 2026-03-11
**Last document review:** aligned with repository code (Laravel 11, Sanctum, Docker/OpenResty).

This file summarizes **typical risks** and what **is already reflected in the code** versus what **always depends on the environment** (production `.env`, TLS, secrets).

---

## 1. State in Code (Reference)

| Topic | Where to Check | Expected Behavior in Repo |
|-------|----------------|---------------------------|
| CORS | `backend/config/cors.php` | No fallback to `*` when `CORS_ALLOWED_ORIGINS` is empty |
| Sanctum Tokens | `backend/config/sanctum.php` | `expiration` defined (e.g., `1440` minutes) |
| Login/Register Rate Limit | `backend/app/Providers/AppServiceProvider.php` | `login` (3/min IP) and `register` (5/min IP) limiters |
| API Rate Limit | `backend/routes/api.php` | `throttle:*` and `throttle.sliding` groups on session mutations |
| Lua Fail-open | `backend/config/services.php` | `rate_limit.fail_open` (env `RATE_LIMIT_FAIL_OPEN`) |
| Horizon | `AppServiceProvider` + `config/app.php` | Access via `HORIZON_ADMIN_EMAILS` |
| HTTP Edge | `docker/nginx/conf.d/studytrack.conf` | OpenResty + headers; revoked token validation (see `DOCUMENTACAO_TECNICA_LUA.md`) |

---

## 2. Exclusively Depends on Production / Deploy

These points **cannot** be "fixed" just by versioned code:

| Risk | Mitigation |
|------|------------|
| `APP_DEBUG=true` in production | `APP_DEBUG=false` in server `.env` |
| HTTP URLs | `APP_URL`, `REVERB_SCHEME`/`wss`, TLS certificates |
| Weak passwords | Strong `DB_PASSWORD`, `REDIS_PASSWORD`; `requirepass` on Redis |
| Open CORS | `CORS_ALLOWED_ORIGINS` with real origins (no `*`) |
| Secrets in Git | Never commit `.env` with real values |
| Vulnerable dependencies | `composer audit`, `npm audit` in CI and locally |

Step-by-step: [DEPLOY_SECURITY_PASSO_A_PASSO.md](DEPLOY_SECURITY_PASSO_A_PASSO.md).

---

## 3. OWASP — Quick Reference (2023)

| ID | Topic | Notes for This Project |
|----|-------|------------------------|
| A01 | Broken Access Control | Ownership in services; Horizon restricted by email; private WS channels |
| A02 | Cryptographic Failures | HTTPS in production; tokens with expiration; authenticated Redis |
| A03 | Injection | Eloquent + Form Requests; maintain validation on new endpoints |
| A04 | Insecure Design | Review "fail-open" decisions at the edge and in Lua rate limit |
| A05 | Security Misconfiguration | Production `.env`, TLS headers, debug disabled |
| A06 | Vulnerable Components | Periodic Composer/npm audits |
| A07 | Authentication Failures | Throttles on login/register; Sanctum |
| A09 | Logging / Monitoring | `LogApiRequests`, health checks; evolve toward centralized aggregation |

---

## 4. Recommended Actions (Ongoing)

1. Keep [DEPLOY_SECURITY_PASSO_A_PASSO.md](DEPLOY_SECURITY_PASSO_A_PASSO.md) synchronized with what the server actually uses.
2. Run `composer audit` and `npm audit` before releases.
3. Periodically review `docs/technical/DOCUMENTACAO_TECNICA_LUA.md` after OpenResty/Redis/PL-Lua changes.
4. Log incidents and fixes in [SECURITY_FIXES_COMPLETED.md](SECURITY_FIXES_COMPLETED.md) (without exposing secrets).

---

**Summary:** the repository includes several **defense-in-depth** measures in the code; **secure production** requires correct `.env`, TLS, Redis, and deploy policies — do not deploy with example values.
