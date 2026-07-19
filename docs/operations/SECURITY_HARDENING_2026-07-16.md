# Security Hardening — 2026-07-16

## Scope

Fixing 19 security vulnerabilities and bugs identified in a complete codebase scan, covering nginx/OpenResty, Redis, Laravel backend, Vue 3 frontend, and Docker.

---

## Index

1. [Applied Security Concepts](#1-applied-security-concepts)
2. [WAF and Edge Security (Nginx/OpenResty)](#2-waf-and-edge-security)
3. [Rate Limiting at the Edge Layer](#3-rate-limiting-at-the-edge-layer)
4. [Persistence and Resilience (Redis)](#4-persistence-and-resilience)
5. [Code Sandbox (Web Worker)](#5-code-sandbox)
6. [Iframe Sandbox (HTML/CSS Preview)](#6-iframe-sandbox)
7. [WebSocket Reconnection](#7-websocket-reconnection)
8. [Token Blacklist and TTL](#8-token-blacklist)
9. [Dockerfile Hardening](#9-dockerfile-hardening)
10. [PostgreSQL Backup](#10-postgresql-backup)
11. [Tests and Config](#11-tests-and-config)
12. [Critical Bug: bootstrap/app.php](#12-critical-bug-bootstrap)

---

## 1. Applied Security Concepts

### Fail-Open vs Fail-Closed

Two fault tolerance paradigms in security systems:

| Paradigm | Behavior | When to Use |
|----------|----------|-------------|
| **Fail-Open** | If the security component fails, the request is **allowed** | Public routes (login, register, health) — availability > security |
| **Fail-Closed** | If the security component fails, the request is **blocked** | Authenticated routes — security > availability |

**Before**: Bearer token validation in nginx used fail-open on all Redis failure points (connection, authentication, query). An offline Redis allowed requests without a token.

**After**: All token validation failures now return `503 Service Unavailable` (fail-closed). Public routes remain fail-open (they don't go through validation).

### Defense in Depth

Multiple independent security layers, so that a failure in one doesn't compromise the system:

```
Client → WAF (Lua) → Rate Limit (Nginx) → Auth Token (Lua+Redis) → Laravel (Sanctum) → Database
```

Each layer provides protection even if the previous layer fails.

### Principle of Least Privilege

Each component receives only the minimum necessary permissions:
- Sandbox containers run as `nobody` / `sandbox`
- PHP-FPM runs as `www-data`
- Volumes mounted as `:ro` (read-only) whenever possible
- Iframe preview without `allow-same-origin`

### Exponential Backoff

Retry strategy with progressive delay to prevent server overload during recovery:

```
attempt_n = min(base × 2^(n-1), max_delay)
```

Applied in WebSocket reconnection: 1s → 2s → 4s → 8s → ... → 60s (cap), 10 attempts.

---

## 2. WAF and Edge Security

### 2.1 What is WAF

**Web Application Firewall (WAF)** — firewall that filters HTTP/HTTPS traffic by inspecting requests for malicious patterns. Unlike a network firewall (IP/port), the WAF understands the HTTP protocol and can block SQL injection, XSS, path traversal, etc.

### 2.2 Implementation in the Project

The WAF is implemented in **Lua** on OpenResty (nginx), running in two phases:

#### Phase 1: `rewrite_by_lua_block` (global — all traffic)

```lua
-- User-Agent blocking
local blocked_agents = { "sqlmap", "nikto", "masscan", "zgrab", "nmap", "dirbuster", "nuclei" }

-- SQLi pattern blocking in URI
local blocked_uri_patterns = {
    "union select", "union all select", "1=1", "1=2",
    "' or '1'='1", "' or '1'='2", "' or 1=",
    "admin' --", "exec xp_", "pg_sleep", "waitfor delay",
}
```

#### Phase 2: `access_by_lua_block` (API-specific `/api/`)

```lua
-- POST body inspection
if method == "POST" or method == "PUT" or method == "PATCH" then
    ngx.req.read_body()
    local body = ngx.req.get_body_data()
    -- Same patterns + NoSQL injection ($ne, $gt, $where)
end

-- Bearer token validation vs Redis blacklist
-- (fail-closed: Redis unavailable → 503)
```

### 2.3 Why in nginx and not just Laravel?

- **Edge layer**: blocks before consuming PHP-FPM resources
- **Redis blacklist**: revoked tokens are blocked before any PHP processing
- **Latency**: Lua in OpenResty is ~10x faster than PHP for simple validations

### 2.4 Applied Fixes

| Item | Before | After |
|------|--------|-------|
| Fail-open on Redis | request passed | returns 503 |
| SQLi patterns | 3 patterns | 11 patterns |
| POST body | not inspected | inspected for SQLi/NoSQL |
| Log severity | `WARN` (fail-open) | `ERR` (fail-closed) |

---

## 3. Rate Limiting at the Edge Layer

### 3.1 Concept

**Rate Limiting** — controlling the rate of requests a client can make within a time interval. Prevents:
- Brute force on login/register
- DDoS attacks on expensive endpoints (recalculate, export, code/execute)
- API abuse

### 3.2 Implementation

```nginx
# Zone definitions (shared memory)
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;   # 10 req/s
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;  # 5 req/min

# Application in API location
location ^~ /api/ {
    limit_req zone=api burst=20 nodelay;
    ...
}
```

### 3.3 Burst and Nodelay

- **Burst**: number of requests that can exceed the rate before being blocked (smooths spikes)
- **Nodelay**: requests within the burst are processed immediately (no queue)

### 3.4 Why Two Rate Limit Layers?

| Layer | Location | Rate | Purpose |
|-------|----------|------|---------|
| Nginx | Edge | 10r/s, 5r/m | Protect PHP-FPM from DDoS |
| Laravel | App | 30-60 req/min, sliding 10 | Fine-grained control per endpoint + user |

Laravel's rate limit is more granular (per logged-in user, per endpoint), but nginx's protects PHP-FPM before even processing the request.

---

## 4. Persistence and Resilience

### 4.1 Redis AOF

**Append-Only File (AOF)** — Redis persistence mechanism that records every write operation in a sequential log.

| Mode | Safety | Performance |
|------|--------|-------------|
| `appendfsync always` | Zero data loss | Slow (fsync per write) |
| `appendfsync everysec` | ≤ 1s data loss | Good (fsync per second) |
| `appendfsync no` | Variable data loss | Fast (OS decides) |

**Choice**: `appendfsync everysec` — best cost-benefit. Critical data (token blacklist, Horizon queues) loses at most 1s on crash.

### 4.2 Data That Now Persists

- Revoked token blacklist (security: revoked tokens survive restarts)
- Horizon queues (jobs are not lost)
- Analytics cache (regenerable, but avoids recalculation spike post-restart)
- Sessions (users don't lose their session)

### 4.3 Data That Remains Non-Persistent (Intentional)

- Dashboard/heatmap cache — regenerable in 5-15 min
- Rate limit counters — reset acceptable
- Job dedup locks — short window (seconds)

---

## 5. Code Sandbox

### 5.1 Concept

**Sandbox** — isolated environment for executing untrusted code safely. Restricts what the code can access (network, filesystem, browser APIs).

### 5.2 Implementation: Web Worker

The JavaScript executor (`js-executor.worker.ts`) uses a **Web Worker** — a separate browser thread without access to DOM, `window`, `document`, or storage.

```typescript
// Explicitly blocked APIs
const blockedGlobals = [
  'fetch', 'XMLHttpRequest', 'importScripts',
  'navigator', 'location', 'history',
  'localStorage', 'sessionStorage', 'indexedDB', 'openDatabase',
]
```

### 5.3 Additional Protections

#### Constructor Chain Escape

Classic sandbox escape technique:

```javascript
// User code attempts to escape:
const f = (function(){}).constructor.constructor
return f('return this')()  // ← gets the global object

// Protection: regex removes .constructor.constructor(
preventSandboxEscape(code)  // replaces with /*blocked*/(
```

#### Forced Timeout

Infinite loops are the most common risk in code execution. A 5s `setTimeout` signals timeout:

```typescript
const timeoutId = setTimeout(() => {
  timedOut = true
}, MAX_EXECUTION_MS)  // 5000ms
```

### 5.4 Applied Fixes

| Protection | Before | After |
|------------|--------|-------|
| Constructor chain | Not blocked | Blocked via regex |
| Timeout | Didn't exist | 5s with signaling |
| Size limit | Backend only | Also in worker |
| setTimeout/Interval | Accessible via globalThis | Passed as argument (controlled) |

---

## 6. Iframe Sandbox

### 6.1 Concept

The HTML `sandbox` attribute on an iframe imposes restrictions on the loaded content:

| Value | Effect |
|-------|--------|
| (empty) | Maximum restriction: no scripts, no forms, no navigation, no origin access |
| `allow-scripts` | Allows JavaScript, but no parent origin access |
| `allow-same-origin` | Allows access to origin cookies/storage (dangerous without `allow-scripts`) |
| `allow-forms` | Allows form submission |

### 6.2 The Problem

```html
<!-- BEFORE: dangerous -->
<iframe sandbox="allow-same-origin"></iframe>
```

This allowed the iframe to read the main domain's cookies if scripts were enabled. Without `allow-scripts`, it doesn't execute JavaScript, but the iframe still has access to the origin.

### 6.3 The Fix

```html
<!-- AFTER: safe for preview -->
<iframe sandbox="allow-scripts"></iframe>
```

Scripts can run (necessary for HTML preview), but the iframe has no access to cookies, localStorage, or the parent.

---

## 7. WebSocket Reconnection

### 7.1 Concept

**WebSocket** — persistent bidirectional connection between client and server. Unlike HTTP, the connection stays open and either side can send data. Used by Laravel Reverb for real-time notifications (dashboard updates, session started/ended).

### 7.2 The Problem

If the WebSocket connection dropped (unstable network, server restart, timeout), the frontend detected the disconnection but never attempted to reconnect. The dashboard stayed "silent" until the user reloaded the page.

### 7.3 The Fix: Exponential Backoff

```typescript
function scheduleReconnect() {
  if (!reconnectUserId || reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) return

  const delay = Math.min(
    RECONNECT_BASE_DELAY_MS * Math.pow(2, reconnectAttempts),  // 1s → 2s → 4s → ...
    60_000  // cap at 60s
  )
  reconnectAttempts++

  setTimeout(() => {
    if (reconnectUserId) connectWebSocket(reconnectUserId)
  }, delay)
}
```

| Attempt | Delay | Accumulated Time |
|---------|-------|------------------|
| 1 | 1s | 1s |
| 2 | 2s | 3s |
| 3 | 4s | 7s |
| 4 | 8s | 15s |
| 5 | 16s | 31s |
| 6-10 | 60s (cap) | ~5 min |
| 11+ | Stop | — |

### 7.4 Why Exponential Backoff?

- **Fair**: doesn't overload the server with simultaneous reconnections
- **Effective**: resolves most temporary failures (network, restart) in the first seconds
- **Limited**: 10 attempts (~8.5 min total) to avoid indefinite retries

---

## 8. Token Blacklist and TTL

### 8.1 Concept

**Token Blacklist** — list of revoked tokens (logout, password change) stored in Redis. When nginx receives a request with a Bearer token, it checks if it's in the blacklist before passing to Laravel.

### 8.2 TTL (Time-To-Live)

Time an item remains in Redis before being automatically removed.

**Before**:
```php
return 60 * 60 * 24 * 365;  // 1 year
```

**After**:
```php
return 60 * 60 * 24 * 7;  // 7 days
```

### 8.3 Why 7 Days?

- The Sanctum default is `expiration = 1440 min` (24h). Tokens with expiration calculate dynamic TTL based on `expires_at`.
- The 365-day fallback only applies to tokens **without** `expires_at`. If the admin configures `SANCTUM_EXPIRATION` as empty, tokens never expire and stay 1 year in the blacklist.
- 7 days is more than enough for any valid token — after 7 days, even if the token wasn't in the blacklist, it has already expired.

---

## 9. Dockerfile Hardening

### 9.1 Alpine: apk upgrade

**Alpine Linux** uses `apk` as its package manager. Docker base images freeze specific package versions at build time. Over time, CVEs are discovered and fixed in newer versions.

```dockerfile
# Before:
FROM alpine:3.19
RUN apk add --no-cache bash

# After:
FROM alpine:3.21
RUN apk upgrade --no-cache && apk add --no-cache bash
```

`apk upgrade --no-cache` updates all installed packages to the latest available versions, fixing known CVEs.

### 9.2 Specific Fixes

| Image | Problem | Fix |
|-------|---------|-----|
| `code-sandbox-bash` | Alpine 3.19 (musl, OpenSSL vulnerabilities) | Alpine 3.21 + apk upgrade |
| `code-sandbox-sql` | `sqlite:alpine` didn't exist | `alpine/sqlite` (community-maintained) |
| `code-sandbox-php` | No apk upgrade | `apk upgrade --no-cache` |
| `studystackpro-nginx` | Outdated base | Build with `--no-cache` pulled OpenSSL 3.3.3→3.3.7, curl 8.12→8.14, musl 1.2.5→1.2.11 |

### 9.3 Full Rebuild

```bash
make rebuild-all
# Executes:
#   1. npm ci + npm build (frontend)
#   2. docker compose build --no-cache (nginx, php-fpm, postgres, redis, node)
#   3. docker compose -f docker/code-sandbox/... build --no-cache (sandboxes)
```

---

## 10. PostgreSQL Backup

### 10.1 Concept

**pg_dump** — PostgreSQL utility for exporting a database to a SQL file. Can be used for:
- Disaster recovery backup
- Server migration
- Snapshot before destructive changes

### 10.2 Implementation

**Script** (`docker/postgres/backup.sh`):
```bash
pg_dump -U "$DB_USER" -d "$DB_NAME" --no-owner --no-acl | gzip > "$FILENAME"
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +7 -delete
```

- `--no-owner --no-acl`: portable across environments
- `gzip`: compression (~10:1 for text data)
- Rotation: 7-day retention

**Sidecar** (`docker-compose.yml`):
```yaml
pg-backup:
    image: postgres:16
    profiles: [backup]  # Doesn't start by default
    entrypoint: |
      sh -c "echo '0 3 * * * /usr/local/bin/backup.sh /backups' > /etc/crontabs/root && crond -f -l 2"
    volumes:
      - pg_backup_data:/backups
```

### 10.3 Usage

```bash
make backup           # Immediate manual backup
make backup-start     # Start scheduled service (daily 3am)
make backup-restore   # Restore instructions
```

---

## 11. Tests and Config

### 11.1 Hardcoded Passwords in Tests

**Problem**: E2E tests had a hardcoded fixed password `TestPassword123!`. Although secure for testing, the practice makes it difficult to:
- Run tests against different environments (each with its own password)
- Version without exposing credentials
- Rotate passwords without changing code

**Fix**:
```typescript
// Before:
const TEST_PASSWORD = 'TestPassword123!'

// After:
const TEST_PASSWORD = process.env.E2E_TEST_PASSWORD || 'TestPassword123!'
```

### 11.2 window.Pusher

**Problem**: `window.Pusher = Pusher` was overwritten on every `connectWebSocket()` call. In development with Vite HMR (hot module replacement), imports are re-executed, and the global variable was redefined — potentially causing conflicts with previous Echo instances.

**Fix**:
```typescript
if (!window.Pusher) {
    window.Pusher = Pusher
}
```

---

## 12. Critical Bug: bootstrap/app.php

### 12.1 Symptom

After rebuilding containers, the API returned:
```
Fatal error: Class "config" does not exist
```

### 12.2 Root Cause

The `backend/bootstrap/app.php` file had been modified in a previous fix to replace `env('APP_ENV')` with `config('app.env')`, arguing that `env()` doesn't work with `php artisan config:cache` (Laravel caches config values and `env()` returns `null`).

However, **context** is crucial:

```
env() always works
config() only works after the ConfigRepository is registered in the container
```

The `bootstrap/app.php` runs during the **initial Laravel container setup** — before the `ConfigRepository` is registered. Calling `config()` at this point causes the error because the `config` binding doesn't exist in the container yet.

### 12.3 The Fix

```php
// ❌ DOESN'T WORK (config repository isn't ready)
if (config('app.env') === 'testing') { ... }

// ✅ WORKS (environment variables are already loaded)
if (env('APP_ENV') === 'testing') { ... }
```

We also fixed the same issue with `config('trusted_proxies.proxies')`:

```php
// ❌ Doesn't work
$trusted = config('trusted_proxies.proxies');

// ✅ Works (reads directly from .env)
$trustedProxies = env('TRUSTED_PROXIES');
```

### 12.4 Lesson Learned

`env()` and `config()` are not interchangeable in all contexts:

| Function | Bootstrap | Runtime | With config:cache |
|----------|-----------|---------|-------------------|
| `env()` | ✅ Works | ✅ Works | ❌ Returns null |
| `config()` | ❌ Doesn't work | ✅ Works | ✅ Works |

Practical rule: use `env()` in `bootstrap/app.php` and `config()` in controllers/services/providers.
