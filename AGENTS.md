# StudyTrack Pro — Specialist Agents

This repository uses **Cursor rules** in `.cursor/rules/` to activate the right "mode" based on the files being edited. Use this page as an **index** and for **handoff** between layers.

## Quick Map

| # | Role | Cursor Rule | Typical Folders |
|---|------|-------------|-----------------|
| 1 | **Vue 3 — frontend** | `frontend-studytrackpro.mdc` | `frontend/**/*` |
| 1b | **Design system / UI** | `design-frontend-studytrackpro.mdc` | `frontend/.../styles`, `components/ui`, `layout`, `views` |
| 1c | **UI & features (flows)** | `subagent-ui-features-studytrackpro.mdc` | `frontend/src/views`, `features` |
| 2 | **Laravel — API** | `backend-studytrackpro.mdc` | `backend/**/*` |
| 3 | **PostgreSQL & analytics** | `postgresql-analytics-studytrackpro.mdc` | `backend/database/**/*`, init SQL in `docker/postgres` |
| 4 | **Redis & WebSockets** | `redis-websocket-studytrackpro.mdc` | `redis-scripts/**/*`, Reverb/Horizon/broadcast configs in `backend` |
| 5 | **Infra & Docker** | `infra-docker-studytrackpro.mdc` | `docker/**/*`, `docker-compose*.yml`, `Makefile`, `.github/workflows` |
| — | **Integration & debug** | `integracao-debug-studytrackpro.mdc` | full-stack, end-to-end symptoms |

Long prompts and details: `docs/prompt-agente-*.md` and `docs/agents/`.

## When to Involve Another Agent

- New contract (API ↔ types) → **Laravel** + **Vue** before migration/cache.
- Dashboard slowness → trace origin: **Postgres** (query/plan) → **Laravel** → **Redis** → **Vue** (render/bundle).
- Real-time events → **Redis/WebSocket** + **Laravel** (channels, listeners) + **Vue** (Echo, cleanup).
- Deploy/CI breaking → **Infra** coordinates; migrations run in the backend flow.

## How to Request in Chat

Prefer prefixing the objective, for example: *"as Postgres agent, optimize the heatmap query"* or *"as Infra agent, fix the health check in compose"* — the rules already reinforce context through globs.
