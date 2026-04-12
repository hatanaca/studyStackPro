# Checklist Frontend StudyTrackPro

Checklist de entrega e referência para pedidos ao agente. Use os exemplos ao final para solicitar escopos específicos.

---

## Checklist geral de entrega

- [ ] **Build** — `npm run build` sem erros
- [ ] **Lint** — `npm run lint` sem erros
- [ ] **Format** — `npm run format:check` (Prettier) sem divergências
- [ ] **Tokens** — Componentes em `components/ui/` usam apenas variáveis de `variables.css` (sem cores/valores hardcoded)
- [ ] **A11y** — Foco visível (`--shadow-focus` em controles interativos), ARIA onde necessário (EmptyState, ErrorCard), `prefers-reduced-motion` respeitado
- [ ] **TypeScript** — Sem `any`; props/emits tipados
- [ ] **API** — Chamadas apenas via módulos em `api/modules/`; erros tratados com `getApiErrorMessage` quando exibir ao usuário

---

## Alta prioridade

| Item | Descrição | Como verificar |
|------|-----------|----------------|
| **Tokens em UI** | Nenhum valor hardcoded em `components/ui/`. Cores, espaçamentos, font-size, sombras e ícones vêm de `variables.css`. | Buscar por `#`, `px` fixos (exceto 0/1px bordas), `rem` literais nos `.vue` de `ui/`. |
| **prefers-reduced-motion** | Animações e transições usam `var(--duration-*)`; em `prefers-reduced-motion: reduce` as durações são ~0. | Ver `variables.css` (media query) e que transições usam as variáveis. |
| **WebSocket ↔ TypeScript** | Payloads do Reverb alinhados a `websocket.types.ts` (ex.: `slug` em `SessionStartedEvent.technology`). | Conferir tipos em `websocket.types.ts` e uso em `useWebSocket.ts`. |
| **Prettier** | Projeto usa Prettier; integrado ao ESLint; script `format` e `format:check`. | `.prettierrc` existe; `eslint-config-prettier` no `eslint.config.js`; `npm run format:check`. |
| **Foco acessível** | Controles interativos (botões, links, inputs) com `:focus-visible` e `box-shadow: var(--shadow-focus)`. | Inspecionar BaseButton, BaseModal, ErrorCard, EmptyState, BasePagination, BaseToast. |

---

## Média prioridade

| Item | Descrição |
|------|-----------|
| **Tokens ícones/tamanhos** | `--icon-size-sm`, `--icon-size-md`, `--icon-size-lg`, `--empty-state-*` usados onde aplicável. |
| **Responsividade** | Testar em 375px e 1440px; tokens `--viewport-min`, `--viewport-max` para referência. |
| **ARIA EmptyState/ErrorCard** | EmptyState com `role="status"`, `aria-labelledby`; ErrorCard com `role="alert"`, `aria-live="assertive"`. |
| **Erro padronizado nas APIs** | Usar `getApiErrorMessage(error)` ao exibir mensagem ao usuário; interceptors para 401/429. |

---

## Baixa prioridade

| Item | Descrição |
|------|-----------|
| **JSDoc** | Comentários em composables e funções públicas dos módulos `api/modules/`. |
| **VeeValidate + Zod** | Validação de formulários com schema Zod e VeeValidate 4. |

---

## Como pedir ao agente

Use frases como as abaixo no Composer para escopo claro:

- **Todas de alta** — “Aplique todas as itens de alta prioridade do checklist frontend: tokens em UI, reduced-motion, WebSocket types, Prettier, foco acessível.”
- **Só item 3** — “Alinhe os payloads do WebSocket com `websocket.types.ts` (ex.: slug em SessionStarted).”
- **Só a11y** — “Garanta foco visível (--shadow-focus) em todos os controles interativos de `components/ui/` e ARIA em EmptyState e ErrorCard.”
- **Checklist antes de PR** — “Rode o checklist de entrega: build, lint, format:check, tokens, a11y e me diga o que falhar.”
- **Média** — “Implemente os itens de média prioridade do checklist: tokens de ícones, responsividade 375/1440, ARIA EmptyState/ErrorCard, tratamento de erro nas APIs.”

Documentação frontend (índice, design system): ver [`README.md`](./README.md) nesta pasta.
