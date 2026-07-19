# Frontend Checklist StudyTrackPro

Delivery checklist and reference for agent requests. Use the examples at the end to request specific scopes.

---

## General Delivery Checklist

- [ ] **Build** — `npm run build` without errors
- [ ] **Lint** — `npm run lint` without errors
- [ ] **Format** — `npm run format:check` (Prettier) without differences
- [ ] **Tokens** — Components in `components/ui/` use only variables from `variables.css` (no hardcoded colors/values)
- [ ] **A11y** — Visible focus (`--shadow-focus` on interactive controls), ARIA where needed (EmptyState, ErrorCard), `prefers-reduced-motion` respected
- [ ] **TypeScript** — No `any`; typed props/emits
- [ ] **API** — Calls only via modules in `api/modules/`; errors handled with `getApiErrorMessage` when displaying to user

---

## High Priority

| Item | Description | How to Verify |
|------|-------------|---------------|
| **UI Tokens** | No hardcoded values in `components/ui/`. Colors, spacing, font-size, shadows, and icons come from `variables.css`. | Search for `#`, fixed `px` (except 0/1px borders), literal `rem` in `.vue` files in `ui/`. |
| **prefers-reduced-motion** | Animations and transitions use `var(--duration-*)`; in `prefers-reduced-motion: reduce` durations are ~0. | Check `variables.css` (media query) and that transitions use the variables. |
| **WebSocket ↔ TypeScript** | Reverb payloads aligned to `websocket.types.ts` (e.g., `slug` in `SessionStartedEvent.technology`). | Check types in `websocket.types.ts` and usage in `useWebSocket.ts`. |
| **Prettier** | Project uses Prettier; integrated with ESLint; `format` and `format:check` scripts. | `.prettierrc` exists; `eslint-config-prettier` in `eslint.config.js`; `npm run format:check`. |
| **Accessible Focus** | Interactive controls (buttons, links, inputs) with `:focus-visible` and `box-shadow: var(--shadow-focus)`. | Inspect BaseButton, BaseModal, ErrorCard, EmptyState, BasePagination, BaseToast. |

---

## Medium Priority

| Item | Description |
|------|-------------|
| **Icon/Size Tokens** | `--icon-size-sm`, `--icon-size-md`, `--icon-size-lg`, `--empty-state-*` used where applicable. |
| **Responsiveness** | Test at 375px and 1440px; tokens `--viewport-min`, `--viewport-max` for reference. |
| **ARIA EmptyState/ErrorCard** | EmptyState with `role="status"`, `aria-labelledby`; ErrorCard with `role="alert"`, `aria-live="assertive"`. |
| **Standardized API Errors** | Use `getApiErrorMessage(error)` when displaying messages to user; interceptors for 401/429. |

---

## Low Priority

| Item | Description |
|------|-------------|
| **JSDoc** | Comments on composables and public functions in `api/modules/` modules. |
| **VeeValidate + Zod** | Form validation with Zod schema and VeeValidate 4. |

---

## How to Request from the Agent

Use phrases like the below in the Composer for clear scope:

- **All high** — "Apply all high-priority items from the frontend checklist: UI tokens, reduced-motion, WebSocket types, Prettier, accessible focus."
- **Just item 3** — "Align WebSocket payloads with `websocket.types.ts` (e.g., slug in SessionStarted)."
- **Just a11y** — "Ensure visible focus (--shadow-focus) on all interactive controls in `components/ui/` and ARIA on EmptyState and ErrorCard."
- **Pre-PR checklist** — "Run the delivery checklist: build, lint, format:check, tokens, a11y and tell me what fails."
- **Medium** — "Implement the medium-priority checklist items: icon tokens, 375/1440 responsiveness, ARIA EmptyState/ErrorCard, API error handling."

Frontend documentation (index, design system): see [`README.md`](./README.md) in this folder.
