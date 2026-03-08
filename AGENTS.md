# Agentes do Projeto StudyTrackPro

Este arquivo descreve os agentes especializados disponíveis para o Composer do Cursor.

---

## Agente Especialista Frontend StudyTrackPro (Vue 3 + TypeScript)

**Quando usar:** melhorias de interface, componentes Vue, TypeScript no frontend, UX, integração com o backend Laravel, WebSocket (Reverb), debug e implementação de funcionalidades com qualidade de produção.

**Como ativar no Composer:**

1. Inclua no contexto arquivos da pasta `frontend/` (por exemplo um `.vue` ou `.ts` da aplicação), ou
2. Abra um arquivo em `frontend/` antes de abrir o Composer.

Com isso, a regra **Frontend StudyTrackPro** é aplicada automaticamente e o Composer atua como o agente especialista.

**Regra:** `.cursor/rules/frontend-studytrackpro.mdc`  
**Prompt completo:** `docs/prompt-agente-frontend-studytrackpro.md`

**Escopo do agente:**

- Vue 3 (Composition API, `<script setup>`), TypeScript 5.4, Vite 5, Pinia, Vue Router, Axios, Chart.js, Laravel Echo
- Melhorias, boas práticas, debug e implementação; consultor proativo (sugestões de evolução: TanStack Query, VeeValidate+Zod, VueUse, Radix Vue, etc.)
- UI/UX: layout, estados (loading/erro/vazio), acessibilidade, design tokens (`variables.css`)
- Contratos de API com Laravel 11; canais WebSocket via Reverb; estrutura `api/`, `components/ui`, `layout/`, `composables/`, `stores/`, `types/`

---

## Agente Especialista em Design Frontend StudyTrackPro (UI/UX + Design System)

**Quando usar:** decisões visuais e de experiência; design system (`variables.css`); hierarquia e identidade visual; componentes base em `ui/`; acessibilidade e microinterações; auditoria de telas e propostas de melhoria de interface.

**Como ativar no Composer:**

1. Inclua no contexto arquivos de `frontend/src/assets/styles/`, `frontend/src/components/ui/`, `frontend/src/components/layout/`, `frontend/src/views/` ou componentes em `frontend/src/features/`, ou
2. Abra um arquivo em uma dessas pastas antes de abrir o Composer.

Com isso, a regra **Design Frontend StudyTrackPro** pode ser aplicada e o Composer atua como especialista em design de interface (UI/UX + design system).

**Regra:** `.cursor/rules/design-frontend-studytrackpro.mdc`  
**Prompt completo:** `docs/prompt-agente-design-frontend-studytrackpro.md`

**Escopo do agente:**

- Design system: tokens em `variables.css` (cores, tipografia, espaçamento, sombras, motion); auditar antes de propor; sem valores hardcoded.
- Componentes base: Button, Timer, Card, Badge, Chart, EmptyState, ProgressBar/Ring — agnósticos de domínio, acessíveis, com variantes e estados.
- Direção estética intencional (evitar genérico); hierarquia visual; densidade com respiro; animações com propósito; a11y e responsividade (375px–1440px).
- Layouts e views: Dashboard, Timer/sessão ativa, histórico, perfil — cada tela com objetivo de experiência claro.

---

## Agente Especialista Backend StudyTrackPro (Laravel 11 + PHP 8.2)

**Quando usar:** melhorias de API, performance, segurança, modelagem, migrations, filas (Horizon), eventos, listeners, jobs, WebSocket (Reverb), debug de bugs no backend e implementação de funcionalidades com qualidade de produção.

**Como ativar no Composer:**

1. Inclua no contexto arquivos da pasta `backend/` (por exemplo um controller, service, migration ou rota), ou
2. Abra um arquivo em `backend/` antes de abrir o Composer.

Com isso, a regra **Backend StudyTrackPro** é aplicada automaticamente e o Composer atua como o agente especialista.

**Regra:** `.cursor/rules/backend-studytrackpro.mdc`  
**Prompt completo:** `docs/prompt-agente-backend-studytrackpro.md`

**Escopo do agente:**

- Laravel 11, PHP 8.2+, Sanctum 4, Reverb 1, Horizon 5, PostgreSQL 16 (schemas public + analytics), Redis 7, PHPUnit
- Melhorias, boas práticas, debug e implementação; consultor proativo (sugestões: Laravel Data, Pest, Laravel Actions, Enums, Telescope, PHPStan, etc.)
- Arquitetura event-driven; módulos Auth, StudySessions, Technologies, Analytics; CQRS leve; cache com tags; rate limiting; contratos de API estáveis para o frontend

---

## Agente Full-Stack StudyTrackPro

**Quando usar:** tarefas que envolvam backend e frontend, API, eventos, migrations, testes ou infra; manter consistência entre camadas e seguir arquitetura e convenções do projeto.

**Como ativar no Composer:**

1. Inclua no contexto arquivos de `backend/`, `frontend/`, `docker/` ou `docs/` (por exemplo rotas API, controllers, stores, migrations), ou
2. Abra um arquivo em uma dessas pastas antes de abrir o Composer.

Com isso, a regra **Full-Stack StudyTrackPro** pode ser aplicada e o Composer atua como o agente especialista no projeto como um todo.

**Regra:** `.cursor/rules/fullstack-studytrackpro.mdc`  
**Prompt completo:** `docs/prompt-agente-fullstack-studytrackpro.md`

**Escopo do agente:**

- Stack: Vue 3 + TypeScript, Laravel 11, PostgreSQL (public + analytics), Redis, Reverb, Horizon, Docker
- Arquitetura event-driven, módulos (Auth, StudySessions, Technologies, Analytics), API REST, WebSocket
- Convenções: Services/DTOs/Repositories no backend; Pinia, api modules e design tokens no frontend
- Manter contrato API estável; indicar impacto em front e back ao propor mudanças

---

## Agente Especialista em Integração & Debug Full-Stack StudyTrackPro

**Quando usar:** erros de integração (500, dado errado na tela, WebSocket que não dispara, store Pinia desatualizada, query lenta, job falhando); rastrear bugs de ponta a ponta; garantir que DB, API, cache, filas, Reverb e frontend estejam sincronizados; validar fluxos completos após correções.

**Como ativar no Composer:**

1. Inclua no contexto arquivos de `backend/`, `frontend/`, `docker/` ou `docs/` relacionados a debug, rotas API, stores, listeners, WebSocket ou tipos de API, ou
2. Abra um arquivo em uma dessas pastas antes de abrir o Composer (especialmente ao investigar um bug que cruza camadas).

Com isso, a regra **Integração & Debug StudyTrackPro** pode ser aplicada e o Composer atua como especialista em rastrear a origem do problema e corrigir em todas as camadas afetadas.

**Regra:** `.cursor/rules/integracao-debug-studytrackpro.mdc`  
**Prompt completo:** `docs/prompt-agente-integracao-debug-studytrackpro.md`

**Escopo do agente:**

- Visão do sistema como fluxo único: Frontend → API → Backend → PostgreSQL/Redis/Queue → Reverb → Frontend.
- Metodologia: classificar sintoma → traçar fluxo completo → isolar camada → corrigir em todas as camadas → validar ponta a ponta.
- Ferramentas por camada: PostgreSQL (EXPLAIN, triggers), Laravel (Telescope, Horizon, queue:failed), Redis (MONITOR, tags), Reverb (channels, logs), Vue (DevTools, storeToRefs, Network/WS).
- Contratos alinhados: API Resource ↔ tipos TypeScript; eventos WebSocket ↔ channels.php + listener + composable + store; cache: mesmas tags em armazenamento e invalidação.
- Checklist antes de entregar: fluxo traçado, origem identificada, correção propagada, testes e validação ponta a ponta.
