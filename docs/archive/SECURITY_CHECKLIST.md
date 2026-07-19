# Security Checklist — StudyTrack Pro

> Security points identified in the 2026-06-23 audit.
> Fix before deploying to production.

## CRITICAL

### 1. Hardcoded secrets in `backend/.env`
- **File:** `backend/.env:74-84`
- **Issue:** OAuth credentials (Google, Discord) and YouTube API key in plaintext.
- **Risk:** Leakage if `.gitignore` is modified or `git add .` is accidental.
- **Fix:** Use only placeholders in `.env.example`. In production, use system environment variables or a secret manager (Vault, AWS SSM).

### 2. OAuth tokens in User model `$fillable`
- **File:** `backend/app/Models/User.php:37-42`
- **Issue:** `discord_token`, `google_token`, `discord_refresh_token`, etc. are in `$fillable`.
- **Risk:** `User::create($request->all())` can inject arbitrary tokens via mass assignment.
- **Fix:** Remove tokens from `$fillable`; use `forceFill` or direct update in services.

### 3. Debug mode in production
- **File:** `backend/app/Exceptions/Handler.php:72`
- **Issue:** `config('app.debug') ? $e->getMessage()` exposes internal details if `APP_DEBUG=true`.
- **Risk:** Information disclosure (SQL queries, file paths, stack traces).
- **Fix:** Never return `$e->getMessage()` in production; use generic messages.

### 4. Direct `env()` in controller
- **File:** `backend/app/Http/Controllers/V1/OAuthController.php:56`
- **Issue:** `env('FRONTEND_URL')` returns `null` after `php artisan config:cache`.
- **Fix:** Use `config('app.frontend_url')` and create an entry in `config/app.php`.

## MEDIUM

### 5. Shared rate limit across heterogeneous endpoints
- **File:** `backend/routes/api.php:68`
- **Issue:** Logout, update profile, change password, CRUD sessions — all in the `throttle:30,1` bucket.
- **Risk:** An attack on one endpoint drains the quota of others.
- **Fix:** Separate into distinct buckets by category.

### 6. CORS — add security headers
- **Issue:** Missing `Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options` in production.
- **Fix:** Configure security header middleware in Nginx or Laravel.

### 7. Session cookie — SameSite and Secure
- **File:** `backend/.env:42`
- **Issue:** `SESSION_COOKIE` doesn't define `SameSite=Lax/Strict` or `Secure=true`.
- **Fix:** Configure in `config/session.php` for production.

### 8. Horizon — IP/email protection
- **File:** `backend/app/Providers/AppServiceProvider.php:57-73`
- **Issue:** `HORIZON_ADMIN_EMAILS` not configured in `.env` — any authenticated user can access Horizon.
- **Fix:** Define admin emails in production `.env`.

## LOW

### 9. Password hashing — check rounds
- **Issue:** `bcrypt()` uses default rounds (10). In production, consider 12+.
- **Fix:** Configure `BCRYPT_ROUNDS` in production `.env`.

### 10. YouTubeService — cache without security invalidation
- **File:** `backend/app/Services/YouTubeService.php:180-191`
- **Issue:** Global `cache()` helper always exists; fallback never executes.
- **Fix:** Improve cache error handling.

### 11. Logs — don't log tokens/passwords
- **Issue:** Some logs may capture headers with Authorization.
- **Fix:** Configure `LogApiRequests` to sanitize sensitive headers.

---

**Last updated:** 2026-06-23
**Status:** Pending — implement before production
