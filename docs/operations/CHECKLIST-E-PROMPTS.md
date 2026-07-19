# Checklist and Prompt Examples

- **Debug (when something breaks):** use the quick checklist and diagnostic prompt in [DEBUG-CHECKLIST-E-PROMPT.md](DEBUG-CHECKLIST-E-PROMPT.md).
- **API Throttles:** table at the end of this file.
- **Environment Variables:** `docs/ENV-VARS.md` and `backend/.env.example` / `frontend/.env.example`.
- **WebSocket:** private channels `dashboard.{userId}`; events `.session.started`, `.session.ended`, `.metrics.updated`, `.metrics.recalculating`. The frontend expects `session.technology.slug` in `.session.started` and `dashboard` (DashboardData object) in `.metrics.updated`.

## Pre-Delivery Checklist

Use this checklist when implementing or reviewing changes in StudyTrackPro.

### Backend (Laravel)

- [ ] Thin controller (delegates to Service; no business logic)
- [ ] Validation in Form Request (never in controller or service)
- [ ] Database access only via Repository (interface in `Contracts/` + binding)
- [ ] DTO with `readonly` properties when applicable
- [ ] Events named in past tense; dispatched by Service
- [ ] Fast Listeners (cache invalidation or Job dispatch)
- [ ] Job with `ShouldBeUnique` for recalculation/idempotent operations
- [ ] New route with rate limiting in `api.php` (auth, search, sensitive, export, recalculate, health)
- [ ] Cache with tags for granular invalidation
- [ ] WebSocket broadcast: authorized channel in `channels.php`
- [ ] Feature test covering the HTTP flow; Unit test for Service when applicable
- [ ] API contract (payload + Resource) aligned with frontend

### Frontend (Vue)

- [ ] Types in `types/`; HTTP calls via modules in `api/`
- [ ] Store/composable doesn't call non-existent APIs (e.g., Goals is localStorage only)
- [ ] Error handling and loading/empty states
- [ ] Accessibility (aria, labels, contrast)

### General

- [ ] Migrations: don't modify already-executed ones; new ones in `transactional/` or `analytics/`
- [ ] README and docs updated if the change affects setup or decisions
- [ ] Postman collection updated if there's a new endpoint

---

## Prompt Usage Examples

Use these examples when requesting tasks for the Composer (project agents).

### Backend

- *"Implement GET /analytics/export endpoint with start and end (date) parameters, max interval validation of 366 days, and Form Request."*
- *"Add specific rate limit for the export route: 30 req/min per user."*
- *"Ensure the SessionStarted event sends the technology slug in the payload for the frontend."*
- *"Unify the seeders: a single demo user (dev@) with techs and sessions; document the order in DatabaseSeeder."*

### Frontend

- *"Adjust useWebSocket to use the technology slug from the backend instead of using the id in place of the slug."*
- *"Document that Goals is frontend-only (localStorage) and that there's no goals API; update the README."*

### Full-stack / Integration

- *"Include the GET /analytics/export endpoint in the Postman collection with parameter description and throttle."*
- *"Add Feature test for the export (auth, response structure, parameter validation, and max interval)."*
- *"Add Unit test for AnalyticsService.getExportData delegating to the repository."*

### Documentation and Quality

- *"Create a checklist (tests, contracts, migrations, README) and prompt examples for the agents in docs/."*
- *"Document the API throttles (auth, search, sensitive, export, recalculate, health) in a reference file."*

---

## Throttle Reference (API)

| Name | Usage | Limit |
|------|-------|-------|
| `login` | POST login (`api.php`) | 3/min per IP |
| `register` | POST register | 5/min per IP |
| `sensitive` | Password change, etc. | 5/min per user |
| `search` | Technology search, active session | 120/min per user |
| `export` | Analytics export | 30/min per user |
| `recalculate` | Metrics recalculation | 2/min per user |
| `health` | Healthcheck | 300/min per IP |
| `throttle.sliding` | Session mutations (Lua) | See `routes/api.php` |
| Default (60,1) | General reads | 60/min per user |
| Default (30,1) | Writes (sessions, techs, etc.) | 30/min per user |
