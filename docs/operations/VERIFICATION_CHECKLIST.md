# Verification Checklist — Security (Deploy)

Use this file as a **manual script** on the server or in the release pipeline. It does not replace the code: always confirm files in Git.

> **Important:** Do not commit production `.env` or examples with real secrets. Generate values with `openssl rand` and `php artisan key:generate`.

---

## Before Going to Production

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` with `https://`
- [ ] `CORS_ALLOWED_ORIGINS` with explicit origins (no `*`)
- [ ] `DB_PASSWORD` and `REDIS_PASSWORD` strong; `requirepass` in Redis aligned
- [ ] `HORIZON_ADMIN_EMAILS` defined
- [ ] `config/cors.php` and `config/sanctum.php` match current repository version
- [ ] TLS terminated at the proxy (Nginx/OpenResty) and HTTP → HTTPS redirect
- [ ] `composer audit` and `npm audit` with no unhandled critical vulnerabilities

---

## Reference Files in Repo

| Area | Path |
|------|------|
| CORS | `backend/config/cors.php` |
| Sanctum | `backend/config/sanctum.php` |
| Rate limits | `backend/app/Providers/AppServiceProvider.php`, `backend/routes/api.php` |
| Proxy / headers | `docker/nginx/nginx.conf`, `docker/nginx/conf.d/studytrack.conf` |
| Redis | `docker/redis/redis.conf` |

---

## Related Documentation

- [SECURITY_AUDIT.md](SECURITY_AUDIT.md) — overview and OWASP
- [DEPLOY_SECURITY_PASSO_A_PASSO.md](DEPLOY_SECURITY_PASSO_A_PASSO.md) — step-by-step
- [SECURITY_FIXES_COMPLETED.md](SECURITY_FIXES_COMPLETED.md) — change log (no secrets)

**"Before/after" scores or generated keys** should not be copied from this repository to the real environment — they are illustrative only if they appear in old history.
