# StudyTrack Pro – Frontend

Frontend Vue 3 + TypeScript do **StudyTrack Pro**, aplicação para acompanhamento de sessões de estudo e métricas.

## Stack

- **Vue 3** (Composition API, `<script setup>`)
- **TypeScript**
- **Vue Router**
- **Pinia**
- **Vite**
- **ApexCharts** (vue3-apexcharts, gráficos)
- **Axios** (HTTP)

## Estrutura

```
src/
├── api/           # Cliente HTTP e módulos por domínio
├── assets/        # Estilos globais, tokens, utilitários
├── components/    # Componentes reutilizáveis (ui, layout, charts, onboarding)
├── composables/   # Lógica reutilizável (useToast, usePagination, etc.)
├── constants/     # Constantes e mensagens
├── features/      # Módulos por domínio (dashboard, sessions, technologies, goals, notifications)
├── router/        # Rotas e guards
├── stores/        # Pinia stores
├── types/         # Tipos TypeScript (domínio, API, filtros)
├── utils/         # Funções puras (formatters, validators, dateUtils)
└── views/         # Páginas (Dashboard, Sessões, Metas, Export, Ajuda, etc.)
```

## Scripts

- `npm run dev` – servidor de desenvolvimento
- `npm run build` – build de produção
- `npm run preview` – preview do build
- `npm run test` – testes (Vitest)
- `npm run lint` – ESLint

## Design system

Tokens e componentes base estão documentados em:

- `docs/DESIGN_SYSTEM.md`
- `docs/TOKENS_REFERENCIA.md`
- `docs/COMPONENTES_UI.md`

Variáveis CSS em `src/assets/styles/variables.css`. Tema escuro via `[data-theme='dark']`.

## Backend

O frontend espera uma API Laravel em `/api/v1`. Configure o proxy no Vite ou a variável de ambiente para a URL base da API.

## Testes

Testes unitários com Vitest em `**/__tests__/*.spec.ts`. Execute com `npm run test`.
