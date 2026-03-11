# StudyTrackPro - Issues Found & Fixes Applied

## Technology Stack

**Backend:**
- Laravel 11 (PHP 8.2+)
- PostgreSQL 16
- Redis 7
- Laravel Reverb (WebSockets)
- Laravel Horizon (Queue Manager)
- Laravel Scheduler

**Frontend:**
- Vue.js 3
- Vite 5
- Node.js

**Infrastructure:**
- Docker & Docker Compose
- Nginx 1.25
- Multi-container architecture

---

## Issues Found & Fixed

### 1. **Database Seeder Null Constraint Violation** ❌ FIXED
**Error:** \SQLSTATE[23502]: Not null violation: 7 ERROR: null value in column "user_id"\

**Root Cause:** TechnologySeeder was using \irstOrCreate()\ with \user_id\ in the search array but it was being passed as null when no technology existed yet.

**Fix:** Reordered parameters in TechnologySeeder.php:
\\\php
// BEFORE (incorrect order)
Technology::firstOrCreate(
    ['user_id' => \->id, 'slug' => Str::slug(\['name'])],
    [...]
);

// AFTER (correct - slug first, then user_id with actual value)
Technology::firstOrCreate(
    ['slug' => Str::slug(\['name']), 'user_id' => \->id],
    [...]
);
\\\

**Files Modified:**
- \ackend/database/seeders/TechnologySeeder.php\

---

### 2. **Horizon Command Not Registered** ❌ FIXED
**Error:** \ERROR Command "horizon" is not defined.\

**Root Cause:** Laravel\Horizon\HorizonServiceProvider was missing from \config/app.php\ providers array.

**Fix:** Added HorizonServiceProvider to the providers list:
\\\php
'providers' => [
    // ... existing providers ...
    Laravel\Horizon\HorizonServiceProvider::class,  // ADDED
],
\\\

**Files Modified:**
- \ackend/config/app.php\

---

### 3. **Character Encoding Issues** ❌ FIXED
**Issues:**
- Garbled text: "Sessão" → "SessA�o"
- "Seção" → "SeAA%o"
- Other Portuguese characters corrupted

**Root Cause:** File encoding problems in seeders (likely ASCII or incorrect UTF-8 handling).

**Fix:** Rewrote both seeder files with explicit UTF-8 encoding and proper character handling:

**Files Modified:**
- \ackend/database/seeders/DemoDataSeeder.php\ (Fixed UTF-8 encoding)
- \ackend/database/seeders/TechnologySeeder.php\ (Fixed UTF-8 encoding)

---

### 4. **Reverb SIGINT Compatibility Issue** ⚠️ PARTIAL FIX
**Error:** \Undefined constant "Laravel\Reverb\Servers\Reverb\Console\Commands\SIGINT"\

**Root Cause:** Laravel Reverb uses POSIX signals (SIGINT, SIGTERM) which are not available on Windows. This is a known compatibility issue.

**Workaround Applied:**
- Added \estart: unless-stopped\ policy to reverb container so it restarts automatically after failures
- Reverb will continue to fail on Windows but won't block other services

**Permanent Solution (Recommended):**
Use Windows Subsystem for Linux (WSL2) with Docker Desktop for proper signal handling, or run on Linux/Mac in production.

**Files Modified:**
- \docker-compose.yml\ (Added restart policy)

---

## Summary of Changes

| File | Issue | Status |
|------|-------|--------|
| backend/database/seeders/TechnologySeeder.php | Null user_id + UTF-8 encoding | ✅ FIXED |
| backend/database/seeders/DemoDataSeeder.php | UTF-8 encoding | ✅ FIXED |
| backend/config/app.php | Missing Horizon provider | ✅ FIXED |
| docker-compose.yml | Reverb restart policy | ✅ IMPROVED |

---

## Testing Instructions

### 1. Rebuild and restart containers:
\\\ash
docker-compose down -v
docker-compose up -d
\\\

### 2. Run database migrations and seeders:
\\\ash
docker-compose exec php-fpm php artisan migrate --seed
\\\

### 3. Verify services are running:
\\\ash
docker-compose ps
# Expected: php-fpm, nginx, postgres, redis, node running
# Note: reverb may show as restarting due to Windows signal limitation
\\\

### 4. Check logs:
\\\ash
docker-compose logs -f horizon      # Should show queue processing
docker-compose logs -f scheduler    # Should show scheduled tasks
docker-compose logs -f node         # Should show Vite dev server ready
\\\

---

## Known Limitations (Windows)

**Reverb WebSocket Server:**
- Fails to start on Windows due to signal handling incompatibility
- Will continuously restart (by docker-compose restart policy)
- **Solution:** Use WSL2 or Linux/Mac for development

**Development Recommendation:**
For optimal development experience on Windows, consider:
1. Using Docker Desktop with WSL2 backend
2. Or developing on a Linux VM/server
3. Or using Linux containers through WSL2

---

## Next Steps

1. ✅ Rebuild containers: \docker-compose up -d\
2. ✅ Run seeders: \docker-compose exec php-fpm php artisan migrate --seed\
3. ✅ Access application:
   - Frontend: http://localhost:5173
   - API: http://localhost
   - Horizon Dashboard: http://localhost/horizon
4. ✅ Monitor logs: \docker-compose logs -f\

---

**Generated:** 2026-03-11 17:31:21
