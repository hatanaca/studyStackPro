# StudyTrack Pro — Completed Security Fixes

**Reference date:** 2026-03-11
**Status:** historical log of priority changes handled in the repository.

> **Note:** Do not commit `.env` or `.env.production` files with real secrets. Use `backend/.env.example` as a template and generate keys with `php artisan key:generate` and passwords with `openssl`.

---

## Critical — Handled

### 1. CORS

- **File:** `backend/config/cors.php`
- **Behavior:** origins from `CORS_ALLOWED_ORIGINS`; no fallback to `*` in production.

### 2. APP_DEBUG

- **Production:** `APP_DEBUG=false` in the deploy environment.
- **Development:** `APP_DEBUG=true` is acceptable locally.

### 3. Database Password

- **Production:** use a strong value (e.g., 32+ random characters), never `secret`.

### 4. Redis Authentication

- **Files:** `docker/redis/redis.conf`, `REDIS_*` variables in backend, alignment with `docker-compose.yml`.

---

## High Severity — Handled

### 5. Sanctum Token Expiration

- **File:** `backend/config/sanctum.php`
- **Field:** `expiration` (minutes), per project policy.

### 6. Authentication Rate Limiting

- **Files:** `backend/app/Providers/AppServiceProvider.php`, `backend/routes/api.php`
- **Named limiters:** `login` (3/min per IP), `register` (5/min per IP), among others — see current code.

### 7. HTTPS / WSS in Production

- `APP_URL` and `REVERB_SCHEME` should reflect HTTPS/WSS in production.

### 8. Horizon Access

- **File:** `backend/app/Providers/AppServiceProvider.php` (`Horizon::auth` callback)
- **Configuration:** `HORIZON_ADMIN_EMAILS` in `config/app.php` / `.env`.

---

## Medium Severity — Handled

### 9. Security Headers

- **Proxy:** `docker/nginx/nginx.conf` and `docker/nginx/conf.d/studytrack.conf` (OpenResty), including Lua reinforcement where applicable.

### 10. Non-root User in PHP (When Applicable)

- **File:** `docker/php/Dockerfile` — review `USER` and `storage` permissions.

### 11. Docker Network

- **File:** `docker-compose.yml` — internal `app` network; only the proxy exposes public ports by default.

### 12. Redis Configuration

- **File:** `docker/redis/redis.conf` — `requirepass`, memory limits, and eviction policy per environment.

---

## Typically Involved Files

| File | Notes |
|------|-------|
| `backend/config/cors.php` | CORS |
| `backend/config/sanctum.php` | Tokens |
| `backend/app/Providers/AppServiceProvider.php` | Rate limits, Horizon |
| `backend/routes/api.php` | Per-route throttle |
| `docker/nginx/*.conf` | Headers, TLS (when active) |
| `docker/redis/redis.conf` | Redis auth |
| `docker-compose.yml` | Network and services |

---

## Production Next Steps

- [ ] Real domains in `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`, `APP_URL`
- [ ] TLS certificates and HTTP → HTTPS redirect
- [ ] `HORIZON_ADMIN_EMAILS` defined
- [ ] Periodic `composer audit` and `npm audit`
- [ ] HSTS after TLS is stable
- [ ] PostgreSQL backups and monitoring

---

## Summary

| Area | Direction |
|------|-----------|
| CORS | Restricted by configuration |
| Debug | Disabled in production |
| Credentials | Strong and outside Git |
| Redis | Authenticated |
| Tokens | With configured expiration |
| Login/register | Strict throttle (see code) |
| Horizon | Only authorized emails |

For an updated operational checklist, see [DEPLOY_SECURITY_PASSO_A_PASSO.md](DEPLOY_SECURITY_PASSO_A_PASSO.md).
