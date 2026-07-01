<p align="center">
  <h1 align="center">🎨 StudyTrack Pro — Frontend</h1>
  <p align="center">
    <em>Interface Vue 3 + TypeScript para acompanhamento de sessões de estudo e métricas</em>
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
  <a href="#estrutura">Estrutura</a> •
  <a href="#rotas">Rotas</a> •
  <a href="#design-system">Design System</a> •
  <a href="#scripts">Scripts</a>
</p>

---

## Stack

<table>
<tr>
<td><strong>Core</strong></td>
<td><strong>Estado & Dados</strong></td>
<td><strong>UI & Visualização</strong></td>
</tr>
<tr>
<td>

- Vue 3.5 (Composition API)
- `<script setup>`
- TypeScript 5.4
- Vite 6

</td>
<td>

- Pinia (stores globais)
- TanStack Query (cache API)
- Vue Router (navegação)
- Axios (HTTP client)

</td>
<td>

- PrimeVue (componentes)
- ApexCharts (gráficos)
- Zod (validação)
- VueUse (composables)

</td>
</tr>
</table>

### Dependências Adicionais

| Pacote | Uso |
|--------|-----|
| `fabric` | Canvas para manipulação de imagens |
| `jspdf` | Geração de PDF |
| `vue-flow` | Diagramas e fluxos visuais |
| `laravel-echo` | WebSocket via Reverb |

---

## Estrutura

```
src/
├── api/                        # Cliente HTTP e módulos
│   ├── client.ts               # Axios + interceptors (token, 401, 429)
│   ├── endpoints.ts            # Constantes de URLs
│   ├── queryKeys.ts            # Chaves TanStack Query
│   └── modules/                # auth, sessions, technologies, analytics
├── assets/
│   └── styles/
│       ├── main.css
│       └── variables.css       # Design tokens (cores, spacing, breakpoints)
├── components/
│   ├── layout/                 # AppLayout, AppSidebar, AppTopBar
│   ├── ui/                     # BaseButton, BaseCard, BaseModal, etc.
│   ├── charts/                 # BarChart, LineChart, PieChart, HeatmapChart
│   └── onboarding/             # OnboardingBanner
├── composables/                # useToast, useWebSocket, useApexChartTheme
├── constants/                  # Mensagens, constantes
├── features/                   # Módulos por domínio
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

## Rotas

| Rota | Descrição | Auth |
|------|-----------|------|
| `/login` | Login | ❌ |
| `/register` | Registro | ❌ |
| `/` | Dashboard | ✅ |
| `/sessions` | Lista de sessões | ✅ |
| `/sessions/focus` | Modo foco (timer) | ✅ |
| `/technologies` | Tecnologias | ✅ |
| `/technologies/:id` | Detalhe + sessões | ✅ |
| `/goals` | Metas | ✅ |
| `/export` | Exportar dados | ✅ |
| `/reports` | Relatórios | ✅ |
| `/settings` | Configurações | ✅ |
| `/profile` | Perfil | ✅ |
| `/help` | Ajuda | ✅ |

---

## Design System

**Tokens** em `src/assets/styles/variables.css`:

| Categoria | Exemplos |
|-----------|----------|
| **Cores** | primary, success, text, bg, border |
| **Espaçamento** | xs, sm, md, lg, xl |
| **Border radius** | sm, md, lg, full |
| **Shadows** | sm, md, lg |
| **Breakpoints** | 375px, 480px, 640px, 768px, 1024px, 1280px, 1440px |
| **Tema escuro** | `[data-theme='dark']` |

> ⚠️ Evite valores hardcoded; use variáveis CSS.

---

## WebSocket

O composable `useWebSocket` conecta ao canal privado `dashboard.{userId}`:

| Evento | Descrição |
|--------|-----------|
| `.metrics.updated` | Dashboard atualizado |
| `.metrics.recalculating` | Recálculo iniciado |
| `.session.started` | Sessão iniciada |
| `.session.ended` | Sessão encerrada |

> Quando WebSocket está desconectado, o dashboard usa polling de fallback.

---

## Scripts

| Comando | Descrição |
|---------|-----------|
| `npm run dev` | Servidor de desenvolvimento (Vite) |
| `npm run build` | Build de produção |
| `npm run preview` | Preview do build |
| `npm run test` | Vitest (watch) |
| `npm run test:run` | Vitest (single run) |
| `npm run test:coverage` | Cobertura |
| `npm run type-check` | Verificação TypeScript |
| `npm run lint` | ESLint |
| `npm run format` | Prettier |

---

## Instalação

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

## Variáveis de Ambiente

| Variável | Descrição | Default |
|----------|-----------|---------|
| `VITE_API_URL` | Base URL da API | same-origin |
| `VITE_REVERB_HOST` | Host do Reverb | localhost |
| `VITE_REVERB_PORT` | Porta do Reverb | 80 |
| `VITE_REVERB_SCHEME` | http ou https | http |
| `VITE_REVERB_APP_KEY` | Chave do Reverb | — |
| `VITE_REVERB_ENABLED` | Habilitar WebSocket | true |

---

<p align="center">
  <a href="../README.md">← Voltar ao README principal</a>
</p>
