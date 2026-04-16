# StudyTrack Pro — agentes especialistas

Este repositório usa **regras Cursor** em `.cursor/rules/` para ativar o “modo” certo conforme os arquivos em edição. Use esta página como **índice** e para **handoff** entre camadas.

## Mapa rápido

| # | Papel | Regra Cursor | Pastas típicas |
|---|--------|----------------|----------------|
| 1 | **Vue 3 — frontend** | `frontend-studytrackpro.mdc` | `frontend/**/*` |
| 1b | **Design system / UI** | `design-frontend-studytrackpro.mdc` | `frontend/.../styles`, `components/ui`, `layout`, `views` |
| 1c | **UI & features (fluxos)** | `subagent-ui-features-studytrackpro.mdc` | `frontend/src/views`, `features` |
| 2 | **Laravel — API** | `backend-studytrackpro.mdc` | `backend/**/*` |
| 3 | **PostgreSQL & analytics** | `postgresql-analytics-studytrackpro.mdc` | `backend/database/**/*`, SQL de init em `docker/postgres` |
| 4 | **Redis & WebSockets** | `redis-websocket-studytrackpro.mdc` | `redis-scripts/**/*`, configs Reverb/Horizon/broadcast no `backend` |
| 5 | **Infra & Docker** | `infra-docker-studytrackpro.mdc` | `docker/**/*`, `docker-compose*.yml`, `Makefile`, `.github/workflows` |
| — | **Integração & debug** | `integracao-debug-studytrackpro.mdc` | full-stack, sintomas ponta a ponta |

Prompts longos e detalhes: `docs/prompt-agente-*.md` e `docs/agents/`.

## Quando envolver outro agente

- Contrato novo (API ↔ tipos) → **Laravel** + **Vue** antes de migration/cache.
- Lentidão no dashboard → rastrear origem: **Postgres** (query/plano) → **Laravel** → **Redis** → **Vue** (render/bundle).
- Eventos em tempo real → **Redis/WebSocket** + **Laravel** (canais, listeners) + **Vue** (Echo, cleanup).
- Deploy/CI quebrando → **Infra** coordena; migrations rodam no fluxo do backend.

## Como pedir no chat

Prefira prefixar o objetivo, por exemplo: *“como agente Postgres, otimize a query do heatmap”* ou *“como Infra, ajuste health check no compose”* — as regras já reforçam o contexto pelos globs.
