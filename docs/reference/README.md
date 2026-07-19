# Historical Reference (`StudyTrack_*.txt`)

These files are old notes and plans from the project. **They may contain outdated details** (routes, limits, class names).

For decisions and numbers aligned with the current code, use:

- [`../technical/DOCUMENTACAO_TECNICA.md`](../technical/DOCUMENTACAO_TECNICA.md)
- [`../../backend/README.md`](../../backend/README.md)
- [`../../backend/routes/api.php`](../../backend/routes/api.php)
- [`../../backend/app/Providers/AppServiceProvider.php`](../../backend/app/Providers/AppServiceProvider.php)

## Common Inconsistencies (Conceptual Substitutions)

When reading the `.txt` files, interpret old terms as follows (aligned with the current repository):

| Legacy Text in `.txt` | Actual State in Repo |
|------------------------|----------------------|
| **Chart.js** / `chart.js` imports | **ApexCharts** + **vue3-apexcharts** (`frontend/src/components/charts/`) |
| **Socket.io** / **Socket.io-client** | **Laravel Reverb** + **Laravel Echo** + **pusher-js** (real-time) |
| **RouteServiceProvider** for rate limit | **`AppServiceProvider`** (`RateLimiter::for(...)`) + `throttle:*` assignment in **`routes/api.php`** |
| **`Route::apiResource('study-sessions', …)`** | **Explicit** routes in `api.php`: CRUD, `POST …/start`, `PATCH …/{id}/end`, `GET …/active` (not a "pure" resource) |
| **Nginx** as the only proxy | **OpenResty** image in Docker (`docker/nginx/`); see [`../../docker/README.md`](../../docker/README.md) |
| Endpoints without `/api/v1` prefix | Versioned API: **`/api/v1`** prefix (e.g., `POST /api/v1/auth/login`); health at `/api/health`, `/health`, `/up` |
| **Laravel Telescope** as acquired data | **Not** in `composer.json`; debug via **logs**, **`LogApiRequests`**, **Horizon**, **`queue:failed`**; Telescope only if optionally installed in dev |

Some point-inconsistencies in the originals: old login limits (5/min), relative paths in tables without repeating `/api/v1`.
