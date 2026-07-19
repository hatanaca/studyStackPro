# Environment Variables

Use the `.env.example` files as a base and copy them to `.env` for the first setup (`make setup` does this).

## Backend (`backend/.env.example`)

| Variable | Usage |
|----------|-------|
| `APP_*` | Name, environment, key, debug, application URL |
| `DB_*` | PostgreSQL connection (host, port, database, user, password) |
| `REDIS_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` | Redis for cache, queues, and sessions |
| `REVERB_*` | Laravel Reverb (WebSocket): app key, host, port, scheme |
| `SANCTUM_STATEFUL_DOMAINS` | Domains for authentication cookies |
| `CORS_ALLOWED_ORIGINS` | Allowed origins for CORS (e.g., `http://localhost:5173`) |
| `HORIZON_ADMIN_EMAILS` | Emails (comma-separated) that can access the Horizon dashboard |
| `RATE_LIMIT_FAIL_OPEN` | When `true`, sliding rate limit (Lua) failure allows the request to proceed (see `config/services.php`) |

The `backend/.env.example` file also includes `VITE_*` keys to align builds in Docker or monorepo documentation. **The Vite build primarily reads from `frontend/.env` / `frontend/.env.example`** — keep values consistent between both if using duplicated variables.

## Frontend (`frontend/.env.example`)

| Variable | Usage |
|----------|-------|
| `VITE_API_URL` | API base URL (empty = relative proxy in dev) |
| `VITE_REVERB_ENABLED` | `true`/`false` to enable/disable WebSocket |
| `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`, `VITE_REVERB_APP_KEY` | Reverb server connection |

After changing backend variables that the frontend uses, a frontend rebuild (`npm run build`) is required so that `VITE_*` values are embedded.
