# Quick Debug Checklist and Diagnostic Prompt — StudyTrackPro

This document consolidates a **repeatable checklist** for when something breaks in the environment and a **prompt template** to use in Cursor for complete diagnostic sessions.

---

## 1. Quick Debug Checklist

Whenever something fails, run in order:

1. **Containers**
   - `docker compose ps` → all services `healthy` (or `running` where there's no healthcheck).
   - If any are `unhealthy` or `Exit`, inspect with `docker compose logs <service>`.

2. **API Health**
   - Test manually: `GET http://localhost/health` or `GET http://localhost/api/health` (depending on your route).
   - Should return 200 with `status: "healthy"` and `services: { database, redis, queue, websocket }` ok.

3. **Environment Variables**
   - Validate critical vars in the `.env` files used by containers/host:
     - **URL/origin:** `APP_URL`, `VITE_API_URL`, `CORS_ALLOWED_ORIGINS` (e.g., `http://localhost:5173` in dev).
     - **DB:** `DB_HOST=postgres`, `DB_PASSWORD` matching the Postgres container.
     - **Redis:** `REDIS_HOST=redis`, `REDIS_PASSWORD` matching `requirepass` in `docker/redis/redis.conf`.
     - **Reverb:** `REVERB_HOST=reverb` (on Docker network), `REVERB_PORT=8080`; in frontend, in dev behind Nginx: `VITE_REVERB_HOST=localhost`, `VITE_REVERB_PORT=80`.

4. **Frontend and Network**
   - Access frontend dev at `http://localhost:5173` (or production at `http://localhost`).
   - Open DevTools → **Network** and **Console** tabs and reproduce the failing flow; note errors (4xx/5xx, CORS, WS).

5. **Service Logs**
   - `docker compose logs php-fpm` (Laravel, DB, Redis errors).
   - `docker compose logs horizon` (failing jobs, Redis connection).
   - `docker compose logs reverb` (binding, port, connections).

After this, if the problem isn't obvious, use the **full debug prompt** below in a new Cursor session.

---

## 2. Full Debug Prompt Template

Copy the block below into Cursor and fill in the *[describe here]* section with the symptom (e.g., "API returning 500 on /api/v1/study-sessions", "WebSocket not updating dashboard", "Docker won't start postgres").

```text
You are a full-stack specialist agent for the StudyTrackPro project.

Project context:
- Frontend: Vue 3 + TypeScript in `frontend/`, using central axios in `frontend/src/api/client.ts`, Pinia stores, composables (`useWebSocket` in `frontend/src/composables/useWebSocket.ts`) and REST API `/api/v1/*`.
- Backend: Laravel 11 in `backend/`, with modules in `backend/app/Modules/*`, routes in `backend/routes/api.php` and `backend/routes/channels.php`, queues with Redis/Horizon and broadcasting with Reverb.
- Infra: Docker with `nginx`, `php-fpm`, `node` (Vite), `postgres`, `redis`, `horizon`, `reverb`, defined in `docker-compose.yml` and configs in `docker/`.

Debug objective for this session:
- Identify and fix issues in: [describe here: for example, API returning 500 on /api/v1/study-sessions, WebSocket not updating dashboard, Docker won't start postgres, etc.]

Instructions for you (agent):
1. Explore first:
   - Env files: `.env`, `.env.example`, `backend/.env`, `backend/.env.example`, `frontend/.env`, `frontend/.env.example`.
   - Docker: `docker-compose.yml`, `docker-compose.dev.yml`, files in `docker/`.
   - Front-back integration: `frontend/src/api/client.ts`, `frontend/src/composables/useWebSocket.ts`, `frontend/src/api/modules/*`, `backend/routes/api.php`, `backend/routes/channels.php`, configs in `backend/config/{cors.php,broadcasting.php,queue.php,database.php,horizon.php}`.

2. Build a diagnosis:
   - List inconsistencies in environment variables (APP_URL, VITE_API_URL, CORS_ALLOWED_ORIGINS, DB_*, REDIS_*, REVERB_*, VITE_REVERB_*).
   - Point out obvious Docker issues (missing services, wrong ports, host conflicts).
   - Verify the frontend is calling the right URLs (baseURL, paths, auth header) and the backend responds correctly.

3. Propose fixes:
   - For each issue, suggest specific changes to `.env`, `docker-compose.yml`, Laravel configs, or TS code.
   - Prioritize fixes that get the environment running without errors, then HTTP integration, then WebSocket/queue.

4. Validate:
   - Simulate the test sequence I should run (basic `docker compose` commands, HTTP calls, login flow, study session creation, dashboard viewing) to confirm everything works.

Always explain in a direct and objective manner, and propose ready-to-use commands when it makes sense.
```

---

## 3. Quick References

- **API Routes:** `backend/routes/api.php` (prefix `v1`: auth, technologies, study-sessions, analytics).
- **Health:** `GET /health` (web) and `GET /api/health` (api); validates DB, Redis, queue, and WebSocket.
- **CORS:** `backend/config/cors.php` uses `CORS_ALLOWED_ORIGINS` (comma-separated); in dev include `http://localhost:5173`.
- **WebSocket:** private channel `dashboard.{userId}`; auth at `POST /api/broadcasting/auth` with `Authorization: Bearer <token>`; Nginx proxy at `location /app/` for Reverb.
- **Integration and debug agent:** rule at `.cursor/rules/integracao-debug-studytrackpro.mdc` and prompt at `docs/agents/prompt-agente-integracao-debug-studytrackpro.md`.
