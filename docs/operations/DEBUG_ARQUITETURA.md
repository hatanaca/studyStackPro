# Architecture Debug — StudyTrackPro

End-to-end diagnostic guide: Frontend → Nginx → Laravel → PostgreSQL/Redis → Reverb → Frontend.

---

## Application Flow

```
Browser (Vue 3)
   │ HTTP  GET/POST /api/v1/*        (Bearer token)
   ▼
Nginx :80
   │ FastCGI /api/*                  → php-fpm:9000
   │ Proxy WS /app/*                 → reverb:8080
   │ Static  /*                      → /frontend/index.html
   ▼
php-fpm (Laravel 11)
   │ Middleware: EnsureJsonResponse, auth:sanctum, SetUserTimezone, LogApiRequests, named throttles, throttle.sliding (sessions)
   │ FormRequest → Controller → Service → Repository → Eloquent → PostgreSQL
   │ Event → Listener → Job (queue: metrics) → Redis → Horizon → RecalculateMetricsJob
   │                   → ShouldBroadcast → Reverb
   ▼
Reverb :8080  →  private channel: private-dashboard.{userId}
   ▼
Browser (Laravel Echo / Pusher-JS)
   └─ .metrics.updated, .metrics.recalculating, .session.started, .session.ended
```

---

## Debug Prompt (Use in Composer / AI)

> Paste the following as initial context when a bug crosses layers:

```
StudyTrackPro project context:
- Frontend: Vue 3 + TypeScript, Pinia, Laravel Echo (Reverb), Axios (Bearer token)
- Backend: Laravel 11, Sanctum 4, Reverb 1, Horizon 5, PostgreSQL 16, Redis 7
- Infra: Docker (nginx:80 → php-fpm:9000 / reverb:8080), Vite proxy in dev

Observed symptom:
[DESCRIBE HERE: URL, HTTP method, response received, Pinia store state, expected vs received WS event]

Suspected layers:
[ ] Redis (cache/queues/Reverb)
[ ] CORS / Auth (401 / preflight)
[ ] Laravel (500 / 422 / business logic)
[ ] WebSocket (channel not receiving event)
[ ] Frontend (stale store / race condition)

Request: trace the origin, list affected layers, and propose a fix across all of them.
```

---

## Layer-by-Layer Diagnostic Checklist

### 1. Infrastructure / Docker

```bash
# Check running containers
docker compose ps

# Logs for each service
docker compose logs nginx --tail=50
docker compose logs php-fpm --tail=50
docker compose logs reverb --tail=50
docker compose logs horizon --tail=50

# Test Redis connectivity (with password)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" ping
# Expected: PONG

# Queues: the key name in Redis depends on REDIS_PREFIX (e.g., studytrackpro_database_)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" KEYS "*queues*metrics*"
# Or via Artisan (recommended)
docker compose exec php-fpm php artisan queue:monitor metrics --max=1000
```

**Checklist:**
- [ ] All containers `Up`
- [ ] Redis responds `PONG` with the `redis.conf` password
- [ ] `REDIS_PASSWORD` in `.env` matches `requirepass` in `docker/redis/redis.conf`
- [ ] Ports `80` and `5173` accessible on host

---

### 2. CORS

```bash
# Test preflight for the API
curl -X OPTIONS http://localhost/api/v1/auth/login \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Authorization, Content-Type" \
  -v 2>&1 | grep -i "access-control"

# Expected: Access-Control-Allow-Origin: http://localhost:5173
```

**Checklist:**
- [ ] `CORS_ALLOWED_ORIGINS=http://localhost:5173` in `.env`
- [ ] HTTP response contains `Access-Control-Allow-Origin` with the correct origin
- [ ] `supports_credentials=false` in `config/cors.php` (Bearer token, not cookie)

---

### 3. Authentication (Sanctum Bearer Token)

```bash
# Login — should return { success, data: { user, token, token_type } }
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user@example.com","password":"password"}' | jq .

# Register — should return { success, data: { user, token, token_type } } (same as login)
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password","password_confirmation":"password"}' | jq .

# Use the returned token to access a protected route
TOKEN="<paste token here>"
curl -X GET http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .
```

**Checklist:**
- [ ] Login returns `data.token` (not just in header)
- [ ] Register returns `data.token` and `data.user` (not just `data` with the user)
- [ ] `/auth/me` returns `200` with valid token; `401` without token or with expired token
- [ ] `EnsureJsonResponse` middleware active: auth errors return JSON, not HTML redirect

---

### 4. API Endpoints

```bash
TOKEN="<your token>"

# Health
curl http://localhost/api/health | jq .

# Technologies
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/v1/technologies | jq .

# Active session
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/v1/study-sessions/active | jq .

# Start session
curl -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"technology_id":"<uuid>"}' \
  http://localhost/api/v1/study-sessions/start | jq .

# Dashboard analytics
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/v1/analytics/dashboard | jq .
```

**Expected response checklist:**
- [ ] All return `{ success: true, data: {...} }` on `200`
- [ ] Validation errors return `422` with `{ success: false, error: { code, message, details } }`
- [ ] Rate limit returns `429` (not `500`)
- [ ] Unauthenticated routes return `401` JSON (not HTML redirect)

---

### 5. Queues / Horizon

```bash
# Horizon status
docker compose exec php-fpm php artisan horizon:status

# Failed jobs
docker compose exec php-fpm php artisan queue:failed

# Retry failures
docker compose exec php-fpm php artisan queue:retry all

# Monitor jobs in real time
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" MONITOR
# Filter: ^"LPUSH\|RPOP"
```

**Checklist:**
- [ ] Horizon `running` (not `paused` or `inactive`)
- [ ] `metrics` queue without accumulated jobs (after ending a session, the job should process in ~2s)
- [ ] No jobs in `queue:failed`
- [ ] `RecalculateMetricsJob` completes and dispatches `MetricsRecalculated` event

---

### 6. Redis Cache (Tags per User)

```bash
# List cache keys (prefix comes from REDIS_PREFIX in .env)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" \
  KEYS "*analytics*"

# Check TTL of a key (replace with the real name returned by KEYS)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" \
  TTL "<key>"

# Clear cache manually (in dev)
docker compose exec php-fpm php artisan cache:clear
```

**Checklist:**
- [ ] Cache invalidates after creating/updating/deleting a session (`InvalidateSessionCache` listener)
- [ ] Tags `['analytics', 'user:{userId}']` are cleared before recalculation
- [ ] Cache is repopulated by `UpdateCacheWithFreshData` after `MetricsRecalculated`

---

### 7. WebSocket (Reverb)

```bash
# Verify Reverb is running
docker compose logs reverb --tail=20

# Test private channel authentication
TOKEN="<your token>"
USER_ID="<user uuid>"
curl -X POST http://localhost/api/broadcasting/auth \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"socket_id\":\"123.456\",\"channel_name\":\"private-dashboard.${USER_ID}\"}" | jq .
# Expected: { "auth": "local-key:...<signature>" }
```

**In browser DevTools (Network tab > WS):**
1. Filter by `app/` — should show an active WebSocket connection
2. In the Messages tab, observe bidirectional frames
3. After creating/ending a session, check if a frame with the `.metrics.updated` event arrives

**Checklist:**
- [ ] WebSocket connection established (status `101 Switching Protocols`)
- [ ] `private-dashboard.{userId}` channel authenticated successfully
- [ ] `VITE_REVERB_APP_KEY` matches `REVERB_APP_KEY` in backend
- [ ] Events listened with leading dot (`.session.started`, `.metrics.updated`) — the dot omits the app namespace in Laravel Echo
- [ ] `forceTLS: false` on HTTP / `forceTLS: true` on HTTPS
- [ ] `wsPort` and `wssPort` point to the correct port (nginx `:80` in production, `:5173` via Vite proxy in dev)

---

### 8. Frontend (Vue DevTools + Network)

**Vue DevTools — Pinia tab:**
- `auth` → `token` populated, `user` with id
- `sessions` → `activeSession` updates on session start; `null` on end
- `analytics` → `dashboard` updates after `MetricsRecalculated`; `isRecalculating: true` during the job

**Network — requests to watch:**

| User Action | Expected Request | Expected Response |
|---|---|---|
| Login | `POST /api/v1/auth/login` | `200 { data: { user, token } }` |
| Register | `POST /api/v1/auth/register` | `201 { data: { user, token } }` |
| Start session | `POST /api/v1/study-sessions/start` | `200 { data: StudySession }` |
| End session | `PATCH /api/v1/study-sessions/:id/end` | `200 { data: StudySession }` |
| Dashboard | `GET /api/v1/analytics/dashboard` | `200 { data: DashboardData }` |

**Checklist:**
- [ ] Axios interceptor injects `Authorization: Bearer <token>` on all protected routes
- [ ] `401` redirects to `/login` and clears localStorage
- [ ] `429` shows rate limit toast (not silent crash)
- [ ] `analytics` store is not overwritten by TanStack Query's `refetchOnWindowFocus` after WebSocket update (check handler order)

---

## Symptom → Root Cause → Fix Map

| Symptom | Most Likely Cause | How to Confirm | Fix |
|---|---|---|---|
| `500` on any endpoint | Redis without password / no connection | `docker compose exec redis redis-cli -a <password> ping` | Align `REDIS_PASSWORD` with `requirepass` in `redis.conf` |
| `401` even with valid token | Token expired (1440 min) or `EnsureJsonResponse` missing | Check token `created_at` via `/auth/tokens` | Re-login; confirm middleware |
| CORS blocked (`preflight` 403) | `CORS_ALLOWED_ORIGINS` empty or wrong origin | `curl -X OPTIONS` with `Origin` header | Set `CORS_ALLOWED_ORIGINS=http://localhost:5173` |
| Metrics don't update on session end | `array_keys()` bug in `DispatchMetricsRecalculation` | Check jobs in `metrics` queue; check `fullRecalc` | **Already fixed**: use `$event->changedFields` directly |
| WebSocket won't connect | Port 8080 not exposed / `VITE_REVERB_PORT` wrong | `curl ws://localhost:8080/app/` | Use port `80` via nginx; `VITE_REVERB_PORT=80` |
| Private channel unauthorized (`403` on broadcasting/auth) | Token not sent in Echo header or wrong `authEndpoint` | Check `POST /api/broadcasting/auth` request in Network | Confirm `auth.headers.Authorization` in Echo config |
| Dashboard "freezes" on session delete (no spinner) | `BroadcastMetricsRecalculating` missing in `StudySessionDeleted` | Check events on WS channel after delete | **Already fixed**: listener added to `EventServiceProvider` |
| Register creates account but doesn't log in | `authApi.register` didn't return token | Check `POST /api/v1/auth/register` response | **Already fixed**: backend and frontend aligned |
| `wssPort: 443` on HTTP | Hardcoded bug in both ternary branches | Inspect Echo config in DevTools | **Already fixed**: `wssPort` uses the configured port |
| Jobs accumulating in `metrics` queue | Horizon stopped or Redis unreachable | `php artisan horizon:status` + `redis-cli ping` | Restart Horizon; fix Redis password |
| `session_count` undefined in goals | Backend doesn't return the field in `/analytics/time-series` | Log `d.session_count` in `useGoalProgress` | Include `session_count` in `AnalyticsRepository` query |

---

## End-to-End Validation Commands (Smoke Test)

```bash
# 1. Register user
RESP=$(curl -s -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"Debug","email":"debug@test.com","password":"password123","password_confirmation":"password123"}')
echo $RESP | jq .
TOKEN=$(echo $RESP | jq -r '.data.token')
USER_ID=$(echo $RESP | jq -r '.data.user.id')

# 2. Fetch technologies
curl -s -H "Authorization: Bearer $TOKEN" http://localhost/api/v1/technologies | jq '.data[0]'

# 3. Create technology
TECH=$(curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"TypeScript","color":"#3178C6"}' \
  http://localhost/api/v1/technologies)
TECH_ID=$(echo $TECH | jq -r '.data.id')
echo "Tech ID: $TECH_ID"

# 4. Start session
SESSION=$(curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"technology_id\":\"$TECH_ID\"}" \
  http://localhost/api/v1/study-sessions/start)
SESSION_ID=$(echo $SESSION | jq -r '.data.id')
echo "Session ID: $SESSION_ID"

# 5. End session
curl -s -X PATCH -H "Authorization: Bearer $TOKEN" \
  http://localhost/api/v1/study-sessions/$SESSION_ID/end | jq .

# 6. Check dashboard (wait ~3s for the job to process)
sleep 3
curl -s -H "Authorization: Bearer $TOKEN" \
  http://localhost/api/v1/analytics/dashboard | jq '.data.user_metrics'

# 7. Check WebSocket channel authorization
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"socket_id\":\"123.456\",\"channel_name\":\"private-dashboard.$USER_ID\"}" \
  http://localhost/api/broadcasting/auth | jq .
```

**Expected result at step 6:** `today_minutes` > 0 and `today_sessions` = 1
**Expected result at step 7:** `{ "auth": "local-key:..." }` (not `403`)

---

## Environment Variables — Alignment Checklist

| Variable | `backend/.env` | `frontend/.env` | `docker-compose.yml` | Notes |
|---|---|---|---|---|
| `REDIS_PASSWORD` | `<YOUR_REDIS_PASSWORD>` | — | — | Must match `requirepass` in `redis.conf` |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173` | — | — | In production: real frontend URL |
| `REVERB_APP_KEY` | `local-key` | — | — | Must match `VITE_REVERB_APP_KEY` |
| `VITE_REVERB_APP_KEY` | — | `local-key` | — | Must match `REVERB_APP_KEY` |
| `VITE_REVERB_HOST` | — | `localhost` | `localhost` | In production: domain without protocol |
| `VITE_REVERB_PORT` | — | `8080` (`.env.example`) | — | With current Docker, Reverb listens on **8080** on the internal network; Nginx exposes WebSocket on **`/app/`** port **80**. If the browser can't reach `localhost:8080`, use **`VITE_REVERB_PORT=80`** (same host as the API) — you may need to configure `wsPath` in Echo (not in `useWebSocket.ts` by default; align with Reverb docs if connection fails). |
| `VITE_REVERB_SCHEME` | — | `http` | `http` | In production: `https` / `wss` |
| `VITE_API_URL` | — | `` (empty = same-origin) | `` | Vite proxy in dev; in prod usually empty or public API URL |
| `BROADCAST_CONNECTION` | `reverb` | — | — | Requires `REVERB_*` filled in backend |
