# Applied Fixes (Summary History)

Notes on issues that have already been handled in **StudyTrack Pro**. For the current state of the code, prefer tests (`make test`) and technical documentation at [../technical/DOCUMENTACAO_TECNICA.md](../technical/DOCUMENTACAO_TECNICA.md).

---

## Stack (Reference)

- **Backend:** Laravel 11, PHP 8.2+, PostgreSQL 16, Redis 7, Sanctum, Reverb, Horizon
- **Frontend:** Vue 3, Vite 5, TypeScript
- **Infra:** Docker Compose, OpenResty (Nginx), PHP-FPM, Node (dev)

---

## Items Already Addressed in the Repository (Examples)

1. **Seeders / PostgreSQL** — Adjustments to seeders to respect `NOT NULL` and demo users (see `backend/database/seeders/`).
2. **Horizon** — `Laravel\Horizon\HorizonServiceProvider` registered in `backend/config/app.php` when Horizon is installed.
3. **UTF-8 Encoding** — Project PHP files must be UTF-8 (accents in messages and seeders).
4. **CORS, Sanctum, throttles** — See `backend/config/cors.php`, `backend/config/sanctum.php`, `AppServiceProvider`, `routes/api.php`.
5. **Metrics / queues** — `RecalculateMetricsJob` on the `metrics` queue; supervision in `config/horizon.php`.

---

## Where to Track New Fixes

- Commits and PRs in Git
- [ERROS-CORRIGIDOS.md](ERROS-CORRIGIDOS.md) (if maintained)
- Feature tests in `backend/tests/Feature/`

*Older versions of this file contained code blocks with corrupted formatting; the content was condensed to avoid incorrect or illegible information.*
