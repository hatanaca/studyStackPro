# StudyTrackPro Project Agents

This file describes the specialized agents available for the Cursor Composer.

---

## StudyTrackPro Frontend Specialist Agent (Vue 3 + TypeScript)

**When to use:** UI improvements, Vue components, frontend TypeScript, UX, Laravel backend integration, WebSocket (Reverb), debugging, and production-quality feature implementation.

**How to activate in Composer:**

1. Include files from the `frontend/` folder in context (e.g., a `.vue` or `.ts` from the application), or
2. Open a file in `frontend/` before opening the Composer.

This automatically applies the **Frontend StudyTrackPro** rule and the Composer acts as the specialist agent.

**Rule:** `.cursor/rules/frontend-studytrackpro.mdc`
**Full prompt:** `docs/agents/prompt-agente-frontend-studytrackpro.md`

**Agent scope:**

- Vue 3 (Composition API, `<script setup>`), TypeScript 5.4, Vite 5, Pinia, Vue Router, Axios, ApexCharts (vue3-apexcharts), Laravel Echo
- Improvements, best practices, debugging, and implementation; proactive consultant (evolution suggestions: TanStack Query, VeeValidate+Zod, VueUse, Radix Vue, etc.)
- UI/UX: layout, states (loading/error/empty), accessibility, design tokens (`variables.css`)
- API contracts with Laravel 11; WebSocket channels via Reverb; `api/`, `components/ui`, `layout/`, `composables/`, `stores/`, `types/` structure

---

## StudyTrackPro Frontend Design Specialist Agent (UI/UX + Design System)

**When to use:** visual and experience decisions; design system (`variables.css`); visual hierarchy and identity; base components in `ui/`; accessibility and micro-interactions; screen audits and interface improvement proposals.

**How to activate in Composer:**

1. Include files from `frontend/src/assets/styles/`, `frontend/src/components/ui/`, `frontend/src/components/layout/`, `frontend/src/views/`, or components in `frontend/src/features/` in context, or
2. Open a file in one of these folders before opening the Composer.

This allows the **Design Frontend StudyTrackPro** rule to be applied and the Composer acts as an interface design specialist (UI/UX + design system).

**Rule:** `.cursor/rules/design-frontend-studytrackpro.mdc`
**Full prompt:** `docs/agents/prompt-agente-design-frontend-studytrackpro.md`

**Agent scope:**

- Design system: tokens in `variables.css` (colors, typography, spacing, shadows, motion); audit before proposing; no hardcoded values.
- Base components: Button, Timer, Card, Badge, Chart, EmptyState, ProgressBar/Ring — domain-agnostic, accessible, with variants and states.
- Intentional aesthetic direction (avoid generic); visual hierarchy; density with breathing room; purposeful animations; a11y and responsiveness (375px–1440px).
- Layouts and views: Dashboard, Timer/active session, history, profile — each screen with a clear experience objective.

---

## StudyTrackPro UI & Features Sub-Agent (visual + product experience)

**When to use:** screen and flow polish, empty/error/loading states, micro-interactions with product impact, small feature improvements **in the UI** (without changing API contracts alone). Complements the **Design** agent (tokens and base components) and the **Frontend** agent (logic, stores, integration).

**How to activate in Composer:**

1. Include files from `frontend/src/views/` or `frontend/src/features/` in context, or
2. Open a file in these folders before opening the Composer.

**Rule:** `.cursor/rules/subagent-ui-features-studytrackpro.mdc`
**Full prompt:** `docs/agents/prompt-subagente-ui-features-studytrackpro.md`

**Sub-agent scope:**

- **UX + feature** intersection: empty states with CTAs, messages, skeletons, clearer session/dashboard flows.
- Respect design system (`variables.css`); only deep visual decisions → Design agent.
- Don't implement new endpoints without alignment; can describe API needs for the backend/fullstack agent.

---

## StudyTrackPro Backend Specialist Agent (Laravel 11 + PHP 8.2)

**When to use:** API improvements, performance, security, modeling, migrations, queues (Horizon), events, listeners, jobs, WebSocket (Reverb), backend bug debugging, and production-quality feature implementation.

**How to activate in Composer:**

1. Include files from the `backend/` folder in context (e.g., a controller, service, migration, or route), or
2. Open a file in `backend/` before opening the Composer.

This automatically applies the **Backend StudyTrackPro** rule and the Composer acts as the specialist agent.

**Rule:** `.cursor/rules/backend-studytrackpro.mdc`
**Full prompt:** `docs/agents/prompt-agente-backend-studytrackpro.md`

**Agent scope:**

- Laravel 11, PHP 8.2+, Sanctum 4, Reverb 1, Horizon 5, PostgreSQL 16 (public + analytics schemas), Redis 7, PHPUnit
- Improvements, best practices, debugging, and implementation; proactive consultant (suggestions: Laravel Data, Pest, Laravel Actions, Enums, PHPStan, Telescope optional in dev, etc.)
- Event-driven architecture; Auth, StudySessions, Technologies, Analytics modules; lightweight CQRS; tagged cache; rate limiting; stable API contracts for frontend

---

## StudyTrackPro Full-Stack Agent

**When to use:** tasks involving both backend and frontend, API, events, migrations, tests, or infra; maintaining consistency across layers and following project architecture and conventions.

**How to activate in Composer:**

1. Include files from `backend/`, `frontend/`, `docker/`, or `docs/` in context (e.g., API routes, controllers, stores, migrations), or
2. Open a file in one of these folders before opening the Composer.

This allows the **Full-Stack StudyTrackPro** rule to be applied and the Composer acts as the specialist agent for the project as a whole.

**Rule:** `.cursor/rules/fullstack-studytrackpro.mdc`
**Full prompt:** `docs/agents/prompt-agente-fullstack-studytrackpro.md`

**Agent scope:**

- Stack: Vue 3 + TypeScript, Laravel 11, PostgreSQL (public + analytics), Redis, Reverb, Horizon, Docker
- Event-driven architecture, modules (Auth, StudySessions, Technologies, Analytics), REST API, WebSocket
- Conventions: Services/DTOs/Repositories in backend; Pinia, api modules, and design tokens in frontend
- Maintain stable API contracts; indicate front and back impact when proposing changes

---

## StudyTrackPro Integration & Debug Full-Stack Specialist Agent

**When to use:** integration errors (500, wrong data on screen, WebSocket not firing, stale Pinia store, slow query, failing job); tracing bugs end-to-end; ensuring DB, API, cache, queues, Reverb, and frontend are synchronized; validating complete flows after fixes.

**How to activate in Composer:**

1. Include files from `backend/`, `frontend/`, `docker/`, or `docs/` related to debugging, API routes, stores, listeners, WebSocket, or API types in context, or
2. Open a file in one of these folders before opening the Composer (especially when investigating a cross-layer bug).

This allows the **Integration & Debug StudyTrackPro** rule to be applied and the Composer acts as a specialist in tracing the problem origin and fixing it across all affected layers.

**Rule:** `.cursor/rules/integracao-debug-studytrackpro.mdc`
**Full prompt:** `docs/agents/prompt-agente-integracao-debug-studytrackpro.md`

**Agent scope:**

- System view as a single flow: Frontend → API → Backend → PostgreSQL/Redis/Queue → Reverb → Frontend.
- Methodology: classify symptom → trace complete flow → isolate layer → fix across all layers → validate end-to-end.
- Tools by layer: PostgreSQL (EXPLAIN, triggers), Laravel (logs, `LogApiRequests`, Horizon, `queue:failed`; Telescope only if installed in dev), Redis (MONITOR, tags), Reverb (channels, logs), Vue (DevTools, storeToRefs, Network/WS).
- Aligned contracts: API Resource ↔ TypeScript types; WebSocket events ↔ channels.php + listener + composable + store; cache: same tags in storage and invalidation.
- Pre-delivery checklist: flow traced, origin identified, fix propagated, tests and end-to-end validation.
