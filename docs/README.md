# Documentation — StudyTrack Pro

Central index for repository documentation. The READMEs in `backend/`, `frontend/`, and `docker/` cover per-package setup; here are the cross-cutting materials.

---

## Technical

| Document | Description |
|----------|-------------|
| [technical/DOCUMENTACAO_TECNICA.md](technical/DOCUMENTACAO_TECNICA.md) | Architecture, stack, frontend, backend, infra, flows, and attention points |
| [technical/RESUMO_ARQUITETURAL_STUDYTRACK_PRO.md](technical/RESUMO_ARQUITETURAL_STUDYTRACK_PRO.md) | Dense summary: stack, routes, modules, PG/Redis/queues, events, WS, frontend, and session→metrics flow |
| [technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md](technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md) | End-to-end execution order: Laravel, Vue, guards, Axios, WebSocket, events, and broadcast |
| [technical/DOCUMENTACAO_TECNICA_LUA.md](technical/DOCUMENTACAO_TECNICA_LUA.md) | Redis Lua, OpenResty at the edge, PL/Lua in PostgreSQL, scripts in `redis-scripts/` |

---

## Didactic (Study)

| Document | Description |
|----------|-------------|
| [didatico/README.md](didatico/README.md) | Study material index |
| [didatico/GUIA_SEGURANCA_PERFORMANCE_CORRECAO.md](didatico/GUIA_SEGURANCA_PERFORMANCE_CORRECAO.md) | Security, performance, and correctness in the context of this repository |

---

## Testing

| Document | Description |
|----------|-------------|
| [testing/ESTRATEGIA_TESTES.md](testing/ESTRATEGIA_TESTES.md) | Test pyramid, module matrix, CI, and risks |

What **GitHub Actions** actually executes (PHPUnit, Pint, PHPStan; Vitest, ESLint, build) is described in **section 1.4** of that document; high coverage goals, mandatory MSW, or Playwright as a *gate* are **roadmap / recommendations**, not current CI failure behavior.

---

## Operations, Deploy, and Checklists

| Document | Description |
|----------|-------------|
| [operations/README.md](operations/README.md) | Detailed index of the `operations/` folder |
| [operations/DEPLOY_SECURITY_PASSO_A_PASSO.md](operations/DEPLOY_SECURITY_PASSO_A_PASSO.md) | Production security |
| [operations/ENV-VARS.md](operations/ENV-VARS.md) | Environment variables |
| [operations/GOALS-FRONTEND-ONLY.md](operations/GOALS-FRONTEND-ONLY.md) | Frontend-only goals |

---

## API

| Artifact | Description |
|----------|-------------|
| [api/StudyTrack_API_Collection.postman.json](api/StudyTrack_API_Collection.postman.json) | Postman collection for API v1 |

---

## Agent Prompts (AI)

Files in [agents/](agents/) — context for area specialization (backend, frontend, design, fullstack, integration). Operational index: [operations/AGENTS.md](operations/AGENTS.md).

---

## Historical Reference / Planning

Support documents — see the note in [reference/README.md](reference/README.md).

| File | Description |
|------|-------------|
| [reference/StudyTrack_Arquitetura_Completa.txt](reference/StudyTrack_Arquitetura_Completa.txt) | System architecture overview |
| [reference/StudyTrack_Endpoints_REST.txt](reference/StudyTrack_Endpoints_REST.txt) | REST API endpoint list |
| [reference/StudyTrack_Estrutura_Pastas.txt](reference/StudyTrack_Estrutura_Pastas.txt) | Project directory structure |
| [reference/StudyTrack_Modelagem_BD.txt](reference/StudyTrack_Modelagem_BD.txt) | Database modeling |
| [reference/StudyTrack_Eventos_Jobs.txt](reference/StudyTrack_Eventos_Jobs.txt) | Events, Listeners, and Jobs |
| [reference/StudyTrack_Estrategia_Cache.txt](reference/StudyTrack_Estrategia_Cache.txt) | Redis cache strategy |
| [reference/StudyTrack_Dashboard.txt](reference/StudyTrack_Dashboard.txt) | Dashboard specifications |
| [reference/StudyTrack_Checklist.txt](reference/StudyTrack_Checklist.txt) | Development checklist |
| [reference/StudyTrack_Plano12Semanas.txt](reference/StudyTrack_Plano12Semanas.txt) | 12-week study plan |
| [reference/StudyTrack_Testes.txt](reference/StudyTrack_Testes.txt) | Testing strategy |

---

## Frontend (Design System and UX)

Index: [../frontend/docs/README.md](../frontend/docs/README.md).

---

<p align="center">
  <a href="../README.md">← Back to main README</a>
</p>
