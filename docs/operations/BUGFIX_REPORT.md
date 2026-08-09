# Bug Fix Report - StudyStack Pro

Date: 2026-07-16

## Summary

Two complete codebase scans identified **58 bugs/vulnerabilities** in total.
**36 bugs fixed** (22 in the first scan + 14 in the second).

---

## Scan 1 — 22 Fixes

### CRITICAL

#### 1. OAuth tokens removed from `$fillable` (User.php)
**File:** `backend/app/Models/User.php`
**Issue:** OAuth tokens were in `$fillable`, allowing accidental mass assignment.
**Fix:** Tokens removed from `$fillable`. `SocialAuthService` now uses `forceFill()`.

#### 2. Docker Sandbox with hardening (DockerSandboxService.php)
**File:** `backend/app/Modules/CodeExecution/Services/DockerSandboxService.php`
**Fix:** Added `--pids-limit 50`, `--user nobody`, `--security-opt no-new-privileges`.

#### 3. Internal errors not exposed (DockerSandboxService.php)
**Fix:** Generic message returned. Detailed error only in log.

### HIGH

#### 4. CSRF state validation enabled (OAuthController.php)
**Fix:** `stateless()` removed. Socialite now validates the state parameter.

#### 5. TOCTOU race condition removed (StudySessionService.php)
**Fix:** Redundant check removed. Database trigger is the guard.

#### 6. Timezone middleware safe for Octane (SetUserTimezone.php)
**Fix:** Previous timezone saved and restored in the `finally` block.

#### 7. Horizon fail-closed (RestrictHorizonToIps.php + AppServiceProvider.php)
**Fix:** Returns 403 when no IPs configured.

### MEDIUM

#### 8-13. Goals reactivity, useConfirm, Player localStorage, MiniPlayer leak, Metrics dedup
Fixed as documented in the previous section.

### LOW

#### 14-22. RedisLuaService, UpdateTechnologyRequest, WeeklySummaryJob, etc.
Fixed as documented in the previous section.

---

## Scan 2 — 14 Fixes

### HIGH

#### 23. LinkedIn disconnect() silently failing (LinkedInController.php)
**File:** `backend/app/Http/Controllers/V1/LinkedInController.php:106`
**Issue:** `$user->update()` ignored non-fillable fields (`linkedin_token`, etc).
**Fix:** Replaced with `$user->forceFill([...])->save()`.

#### 24. env() in bootstrap/app.php breaking with config cache (bootstrap/app.php)
**File:** `backend/bootstrap/app.php:42,50`
**Issue:** `env('APP_ENV')` and `env('TRUSTED_PROXIES')` returned `null` when config was cached.
**Fix:** Replaced with `config('app.env')` and `config('trusted_proxies.proxies')`. Created `config/trusted_proxies.php`.

#### 25. Redis token blacklist not working (TokenService.php)
**File:** `backend/app/Modules/Auth/Services/TokenService.php:20,53`
**Issue:** Blacklist stored token in plaintext, but Sanctum hashed before looking up.
**Fix:** Token is now hashed with `hash('sha256', $token->token)` before storing in Redis.

### MEDIUM

#### 26. SocialAuthService double-hashing password (SocialAuthService.php)
**File:** `backend/app/Modules/Auth/Services/SocialAuthService.php:46`
**Issue:** `bcrypt(Str::random(32))` + `hashed` cast = double-hash password.
**Fix:** Replaced with `Str::random(60)` (`hashed` cast applies hash automatically).

#### 27. OAuth callback without error logging (OAuthController.php)
**File:** `backend/app/Http/Controllers/V1/OAuthController.php:58-62`
**Issue:** Exceptions were swallowed without logging, making debugging impossible.
**Fix:** Added `Log::error()` with provider and message.

#### 28-30. Broadcast listeners could fail a request (3 files)
**Files:** `BroadcastSessionStarted.php`, `BroadcastSessionEnded.php`, `BroadcastMetricsRecalculating.php`
**Issue:** Broadcast failure propagated an exception and rolled back session creation.
**Fix:** Wrapped in try/catch with Log::warning.

#### 31. InvalidateSessionCache without try/catch (InvalidateSessionCache.php)
**Issue:** Redis failure propagated an exception.
**Fix:** Wrapped in try/catch with Log::warning.

#### 32. changePassword without transaction (AuthService.php)
**File:** `backend/app/Modules/Auth/Services/AuthService.php:79-89`
**Issue:** `revokeMany()` + `updatePassword()` without transaction. Failure at step 2 left tokens revoked without password change.
**Fix:** Wrapped in `DB::transaction()`.

#### 33. Handler.php leaking errors in debug mode
**File:** `backend/app/Exceptions/Handler.php:75`
**Issue:** `$e->getMessage()` returned when `app()->isLocal()`. If `APP_DEBUG=true` accidentally in production, it would leak internal information.
**Fix:** Generic message always returned in API.

### LOW

#### 34. App.vue without error boundary
**File:** `frontend/src/App.vue`
**Issue:** Render error on any route crashed the entire SPA.
**Fix:** Added `onErrorCaptured` with fallback UI.

#### 35. main.ts without global error handler
**File:** `frontend/src/main.ts`
**Fix:** Added `app.config.errorHandler`.

#### 36. MiniPlayer drag listeners leak on unmount
**File:** `frontend/src/components/player/MiniPlayer.vue:80`
**Issue:** If component unmounted during drag, `document` listeners were not removed.
**Fix:** `onBeforeUnmount` now calls `onDragEnd()` if `dragging` is active.

---

## Modified Files (Total: 26)

### Backend (18 files)
- `backend/app/Models/User.php`
- `backend/app/Modules/CodeExecution/Services/DockerSandboxService.php`
- `backend/app/Http/Controllers/V1/OAuthController.php`
- `backend/app/Http/Controllers/V1/LinkedInController.php`
- `backend/app/Modules/StudySessions/Services/StudySessionService.php`
- `backend/app/Http/Middleware/SetUserTimezone.php`
- `backend/app/Http/Middleware/RestrictHorizonToIps.php`
- `backend/app/Modules/Auth/Services/SocialAuthService.php`
- `backend/app/Modules/Auth/Services/AuthService.php`
- `backend/app/Modules/Auth/Services/TokenService.php`
- `backend/app/Listeners/StudySession/DispatchMetricsRecalculation.php`
- `backend/app/Listeners/StudySession/BroadcastSessionStarted.php`
- `backend/app/Listeners/StudySession/BroadcastSessionEnded.php`
- `backend/app/Listeners/StudySession/BroadcastMetricsRecalculating.php`
- `backend/app/Listeners/StudySession/InvalidateSessionCache.php`
- `backend/app/Services/RedisLuaService.php`
- `backend/app/Http/Requests/Technologies/UpdateTechnologyRequest.php`
- `backend/app/Jobs/GenerateWeeklySummaryJob.php`
- `backend/app/Providers/AppServiceProvider.php`
- `backend/app/Exceptions/Handler.php`
- `backend/bootstrap/app.php`
- `backend/config/trusted_proxies.php` (new)

### Frontend (10 files)
- `frontend/src/stores/goals.store.ts`
- `frontend/src/composables/useConfirm.ts`
- `frontend/src/stores/player.store.ts`
- `frontend/src/components/player/MiniPlayer.vue`
- `frontend/src/components/ui/SafeSvg.vue`
- `frontend/src/api/sanctum.ts`
- `frontend/src/views/Dashboard/DashboardView.vue`
- `frontend/src/views/share/ShareView.vue`
- `frontend/src/features/share/components/ShareButton.vue`
- `frontend/src/main.ts`
- `frontend/src/App.vue`
- `frontend/src/features/sessions/composables/useStudySessions.ts`
- `frontend/src/stores/ui.store.ts`

---

## Recommended Manual Actions

1. **Rotate credentials** — If `.env` was already committed to git, rotate all keys
2. **Clean git history** — Use BFG Repo-Cleaner to remove `.env` from history
3. **Test OAuth flow** — Removal of `stateless()` requires full flow testing
4. **Verify Horizon** — Confirm `HORIZON_ALLOWED_IPS` is configured in production
5. **Test LinkedIn disconnect** — Now uses `forceFill()`, verify it works
6. **Verify config cache** — Run `php artisan config:cache` and test CSRF + trusted proxies
7. **Test token blacklist** — Verify revoked tokens are properly blocked in Redis
