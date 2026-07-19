<p align="center">
  <h1 align="center">🎨 StudyTrack Pro — Frontend</h1>
  <p align="center">
    <em>Vue 3 + TypeScript interface for tracking study sessions and metrics</em>
  </p>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Vue-3.5-4FC08D?logo=vue.js&logoColor=white" alt="Vue 3.5" />
  <img src="https://img.shields.io/badge/TypeScript-5.4-3178C6?logo=typescript&logoColor=white" alt="TypeScript" />
  <img src="https://img.shields.io/badge/Vite-6-646CFF?logo=vite&logoColor=white" alt="Vite 6" />
  <img src="https://img.shields.io/badge/Pinia-2-FFD859?logo=pinia&logoColor=white" alt="Pinia" />
</p>

<p align="center">
  <a href="#stack">Stack</a> •
  <a href="#structure">Structure</a> •
  <a href="#routes">Routes</a> •
  <a href="#design-system">Design System</a> •
  <a href="#scripts">Scripts</a>
</p>

---

## Stack

<table>
<tr>
<td><strong>Core</strong></td>
<td><strong>State & Data</strong></td>
<td><strong>UI & Visualization</strong></td>
</tr>
<tr>
<td>

- Vue 3.5 (Composition API)
- `<script setup>`
- TypeScript 5.4
- Vite 6

</td>
<td>

- Pinia (global stores)
- TanStack Query (API cache)
- Vue Router (navigation)
- Axios (HTTP client)

</td>
<td>

- PrimeVue (components)
- ApexCharts (charts)
- Zod (validation)
- VueUse (composables)

</td>
</tr>
</table>

### Additional Dependencies

| Package | Usage |
|---------|-------|
| `fabric` | Canvas for image manipulation |
| `jspdf` | PDF generation |
| `vue-flow` | Diagrams and visual flows |
| `laravel-echo` | WebSocket via Reverb |

---

## Structure

```
src/
├── api/                        # HTTP client and modules
│   ├── client.ts               # Axios + interceptors (token, 401, 429)
│   ├── endpoints.ts            # URL constants
│   ├── queryKeys.ts            # TanStack Query keys
│   └── modules/                # auth, sessions, technologies, analytics
├── assets/
│   └── styles/
│       ├── main.css
│       └── variables.css       # Design tokens (colors, spacing, breakpoints)
├── components/
│   ├── layout/                 # AppLayout, AppSidebar, AppTopBar
│   ├── ui/                     # BaseButton, BaseCard, BaseModal, etc.
│   ├── charts/                 # BarChart, LineChart, PieChart, HeatmapChart
│   └── onboarding/             # OnboardingBanner
├── composables/                # useToast, useWebSocket, useApexChartTheme
├── constants/                  # Messages, constants
├── features/                   # Modules by domain
│   ├── auth/                   # LoginForm, RegisterForm
│   ├── dashboard/              # KpiCards, HeatmapWidget, TimeSeriesWidget
│   ├── sessions/               # SessionCard, SessionTimer, SessionFilters
│   ├── technologies/           # TechnologyCard, TechnologyForm, TechnologyPicker
│   ├── goals/                  # GoalList, GoalCard, GoalForm
│   └── notifications/          # NotificationCenter
├── router/
│   ├── index.ts
│   ├── guards.ts               # Auth guard
│   └── routes/                 # auth, dashboard, sessions, technologies
├── stores/                     # auth, sessions, analytics, technologies, goals, ui
├── types/                      # domain.types, api.types, websocket.types
├── utils/                      # formatters, validators, dateUtils
└── views/                      # Dashboard, Sessions, Technologies, Goals
```

---

## Routes

| Route | Description | Auth |
|-------|-------------|------|
| `/login` | Login | ❌ |
| `/register` | Registration | ❌ |
| `/` | Dashboard | ✅ |
| `/sessions` | Session list | ✅ |
| `/sessions/focus` | Focus mode (timer) | ✅ |
| `/technologies` | Technologies | ✅ |
| `/technologies/:id` | Detail + sessions | ✅ |
| `/goals` | Goals | ✅ |
| `/export` | Export data | ✅ |
| `/reports` | Reports | ✅ |
| `/settings` | Settings | ✅ |
| `/profile` | Profile | ✅ |
| `/help` | Help | ✅ |

---

## Design System

**Tokens** in `src/assets/styles/variables.css`:

| Category | Examples |
|----------|----------|
| **Colors** | primary, success, text, bg, border |
| **Spacing** | xs, sm, md, lg, xl |
| **Border radius** | sm, md, lg, full |
| **Shadows** | sm, md, lg |
| **Breakpoints** | 375px, 480px, 640px, 768px, 1024px, 1280px, 1440px |
| **Dark theme** | `[data-theme='dark']` |

> ⚠️ Avoid hardcoded values; use CSS variables.

---

## WebSocket

The `useWebSocket` composable connects to the private channel `dashboard.{userId}`:

| Event | Description |
|-------|-------------|
| `.metrics.updated` | Dashboard updated |
| `.metrics.recalculating` | Recalculation started |
| `.session.started` | Session started |
| `.session.ended` | Session ended |

> When WebSocket is disconnected, the dashboard uses fallback polling.

---

## Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Development server (Vite) |
| `npm run build` | Production build |
| `npm run preview` | Build preview |
| `npm run test` | Vitest (watch) |
| `npm run test:run` | Vitest (single run) |
| `npm run test:coverage` | Coverage |
| `npm run type-check` | TypeScript verification |
| `npm run lint` | ESLint |
| `npm run format` | Prettier |

---

## Installation

### Docker

```bash
make dev
# Frontend dev: http://localhost:5173
```

### Local

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

---

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `VITE_API_URL` | API base URL | same-origin |
| `VITE_REVERB_HOST` | Reverb host | localhost |
| `VITE_REVERB_PORT` | Reverb port | 80 |
| `VITE_REVERB_SCHEME` | http or https | http |
| `VITE_REVERB_APP_KEY` | Reverb key | — |
| `VITE_REVERB_ENABLED` | Enable WebSocket | true |

---

<p align="center">
  <a href="../README.md">← Back to main README</a>
</p>
