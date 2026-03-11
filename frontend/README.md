# StudyTrack Pro – Frontend

Interface Vue 3 + TypeScript do **StudyTrack Pro** para acompanhamento de sessões de estudo e métricas.

---

## Stack

| Tecnologia | Uso |
|------------|-----|
| **Vue 3** | Composition API, `<script setup>` |
| **TypeScript 5.4** | Tipagem estática |
| **Vite 5** | Build e dev server |
| **Pinia** | Estado global (auth, sessions, analytics, technologies, ui) |
| **Vue Router** | Rotas, guards de autenticação |
| **TanStack Query** | Cache de dados da API (dashboard) |
| **PrimeVue** | Componentes UI (Toast, Dialog, etc.) |
| **ApexCharts** | Gráficos (linha, barra, pizza, donut, heatmap) |
| **Axios** | Cliente HTTP |
| **Laravel Echo + Pusher** | WebSocket (Reverb) |
| **Zod** | Validação de schemas |
| **VueUse** | Composables utilitários |

---

## Estrutura

```
src/
├── api/                    # Cliente HTTP e módulos por domínio
│   ├── client.ts           # Axios + interceptors (token, 401, 429)
│   ├── endpoints.ts        # Constantes de URLs
│   ├── queryKeys.ts        # Chaves TanStack Query
│   └── modules/            # auth.api, sessions.api, technologies.api, analytics.api
├── assets/                 # Estilos globais
│   └── styles/
│       ├── main.css
│       └── variables.css   # Design tokens (cores, spacing, breakpoints)
├── components/
│   ├── layout/             # AppLayout, AppSidebar, AppTopBar
│   ├── ui/                 # BaseButton, BaseCard, BaseModal, etc.
│   ├── charts/             # BarChart, LineChart, PieChart, DonutChart, HeatmapChart
│   ├── onboarding/         # OnboardingBanner
│   └── ApiToastInit.vue    # Integração API → Toast
├── composables/            # useToast, useWebSocket, useApexChartTheme, etc.
├── constants/              # Mensagens, constantes
├── features/               # Módulos por domínio
│   ├── auth/               # LoginForm, RegisterForm
│   ├── dashboard/          # KpiCards, HeatmapWidget, TimeSeriesWidget, etc.
│   ├── sessions/           # SessionCard, SessionTimer, SessionFilters
│   ├── technologies/       # TechnologyCard, TechnologyForm, TechnologyPicker
│   ├── goals/              # GoalList, GoalCard, GoalForm
│   └── notifications/     # NotificationCenter
├── router/
│   ├── index.ts            # Configuração
│   ├── guards.ts           # Auth guard (redirect, fetchMe)
│   └── routes/             # auth, dashboard, sessions, technologies, etc.
├── stores/                 # auth, sessions, analytics, technologies, goals, ui
├── types/                  # domain.types, api.types, websocket.types
├── utils/                  # formatters, validators, dateUtils
└── views/                  # Dashboard, Sessions, Technologies, Goals, Export, etc.
```

---

## Rotas principais

| Rota | Descrição |
|------|-----------|
| `/login` | Login |
| `/register` | Registro |
| `/` | Dashboard |
| `/sessions` | Lista de sessões |
| `/sessions/focus` | Modo foco (timer) |
| `/technologies` | Tecnologias |
| `/technologies/:id` | Detalhe + sessões |
| `/goals` | Metas |
| `/export` | Exportar dados |
| `/reports` | Relatórios |
| `/settings` | Configurações |
| `/profile` | Perfil |
| `/help` | Ajuda |

---

## Design system

**Tokens** em `src/assets/styles/variables.css`:
- Cores (primary, success, text, bg)
- Spacing, radius, shadows
- Breakpoints (375px, 480px, 640px, 768px, 1024px, 1280px, 1440px)
- Tema escuro via `[data-theme='dark']`

Evite valores hardcoded; use variáveis CSS.

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

### Com Docker

Do diretório raiz:

```bash
make dev
# Frontend dev em http://localhost:5173
# Ou build para produção: cd frontend && npm install && npm run build
```

### Local

```bash
cd frontend
npm install
cp .env.example .env   # Ajuste VITE_API_URL e VITE_REVERB_* se necessário
npm run dev
```

---

## Variáveis de ambiente

| Variável | Descrição |
|----------|-----------|
| `VITE_API_URL` | Base URL da API (vazio = same-origin) |
| `VITE_REVERB_HOST` | Host do Reverb |
| `VITE_REVERB_PORT` | Porta do Reverb |
| `VITE_REVERB_SCHEME` | http ou https |
| `VITE_REVERB_APP_KEY` | Chave do Reverb |
| `VITE_REVERB_ENABLED` | false para desabilitar WebSocket |

---

## WebSocket

O composable `useWebSocket` conecta ao canal privado `dashboard.{userId}` e escuta:
- `.metrics.updated` — dashboard atualizado
- `.metrics.recalculating` — recálculo iniciado
- `.session.started` — sessão iniciada
- `.session.ended` — sessão encerrada

Quando WebSocket está desconectado, o dashboard usa polling de fallback.

---

## Backend

O frontend espera a API Laravel em `/api/v1`. Em dev com Docker, o Vite pode fazer proxy para o Nginx.
