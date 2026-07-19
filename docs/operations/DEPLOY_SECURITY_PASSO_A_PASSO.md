# Step-by-Step — Production Security (StudyTrackPro)

Checklist to apply the measures from [SECURITY_AUDIT.md](SECURITY_AUDIT.md) in the production environment.
Do not commit production `.env` or passwords to the repository.

---

## 1. Environment Variables (Backend)

On the server, edit the production `.env` (e.g., `backend/.env` or whatever your deploy uses).

| Step | Variable | Action |
|------|----------|--------|
| 1.1 | `APP_ENV` | Set to `production`. |
| 1.2 | `APP_DEBUG` | Set to `false`. |
| 1.3 | `APP_URL` | Use HTTPS, e.g., `https://api.yourdomain.com`. |
| 1.4 | `APP_KEY` | Generate with `php artisan key:generate` if it doesn't exist yet. |
| 1.5 | `CORS_ALLOWED_ORIGINS` | List frontend origins separated by comma, e.g., `https://app.yourdomain.com`. No `*`. |
| 1.6 | `DB_PASSWORD` | Use a strong password. Generate with: `openssl rand -base64 32`. |
| 1.7 | `REDIS_PASSWORD` | Use a strong password (same as `requirepass` in Redis, if used). Generate with: `openssl rand -base64 32`. |
| 1.8 | `HORIZON_ADMIN_EMAILS` | Emails (comma-separated) that can access `/horizon`. E.g., `admin@yourdomain.com`. |

---

## 2. Redis

| Step | Action |
|------|--------|
| 2.1 | Generate a password: `openssl rand -base64 32`. |
| 2.2 | In backend `.env`, set `REDIS_PASSWORD=<generated_password>`. |
| 2.3 | In `docker/redis/redis.conf`, set `requirepass <same_password>` (or use a variable/secret in your orchestrator). |
| 2.4 | Restart Redis after changing `redis.conf`. |

---

## 3. HTTPS (Nginx / Server)

| Step | Action |
|------|--------|
| 3.1 | Obtain an SSL certificate (Let's Encrypt, Cloudflare, or your provider). |
| 3.2 | Configure Nginx to listen on port 443 and use the certificate. |
| 3.3 | Redirect HTTP (80) to HTTPS (301). |
| 3.4 | In `.env`, `APP_URL` and `VITE_*` should use `https://`. |

**Nginx redirect example (before the main `server` block):**

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## 4. Security Headers (Nginx)

If they are not yet in the server block serving HTTPS, add:

| Step | Header | Suggested Value |
|------|--------|-----------------|
| 4.1 | Strict-Transport-Security | `max-age=31536000; includeSubDomains` (only with HTTPS active) |
| 4.2 | X-Content-Type-Options | `nosniff` |
| 4.3 | X-Frame-Options | `SAMEORIGIN` |
| 4.4 | X-XSS-Protection | `1; mode=block` |
| 4.5 | Referrer-Policy | `strict-origin-when-cross-origin` |
| 4.6 | Permissions-Policy | `geolocation=(), microphone=(), camera=()` |

The project already includes several of these in [docker/nginx/nginx.conf](../../docker/nginx/nginx.conf) and [docker/nginx/conf.d/studytrack.conf](../../docker/nginx/conf.d/studytrack.conf) (OpenResty). In production, enable HSTS when SSL is in use.

---

## 5. Already Fixed in Code (Just Verify)

No changes needed; just confirm that the production version is current:

| Item | File | Verification |
|------|------|--------------|
| CORS | `backend/config/cors.php` | `allowed_origins` uses `[]` when `CORS_ALLOWED_ORIGINS` is not defined. |
| Sanctum | `backend/config/sanctum.php` | `expiration => 1440` (tokens expire in 24h). |
| Login Rate Limit | `backend/routes/api.php` + `AppServiceProvider` | Login routes use `throttle:login` (3 req/min). |

---

## 6. After Changing .env and Redis

| Step | Command / Action |
|------|------------------|
| 6.1 | `php artisan config:clear` and `php artisan cache:clear`. |
| 6.2 | Restart workers (Horizon/queue) to load the new `.env`. |
| 6.3 | Test login, an API call, and the Horizon dashboard (with an authorized user). |

---

## 7. Dependency Audit (Recommended)

In both backend and frontend, run periodically:

```bash
# Backend
cd backend && composer audit

# Frontend (frontend folder)
npm audit
```

Fix critical/high vulnerabilities before considering the deploy secure.

---

## Quick Summary

1. **Production .env:** `APP_DEBUG=false`, `APP_URL=https://...`, `CORS_ALLOWED_ORIGINS` defined, strong passwords (DB and Redis), `HORIZON_ADMIN_EMAILS`.
2. **Redis:** `requirepass` in `redis.conf` and `REDIS_PASSWORD` in `.env`.
3. **HTTPS:** Certificate + 80→443 redirect; URLs in `.env` with `https://`.
4. **Nginx:** Security headers (including HSTS when on HTTPS).
5. **Code:** CORS, Sanctum, and throttle already adjusted; keep code updated.
6. **After:** Clear config/cache, restart workers, and run `composer audit` / `npm audit`.
