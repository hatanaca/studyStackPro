# Prompt: Implement Frontend Improvements for StudyTrackPro

Use this prompt when you want the Composer (or another agent) to **execute** improvements in the StudyTrackPro frontend. Tasks are prioritized. You can request "implement all high-priority items" or "implement only item 2".

**Context:** Vue 3 + TypeScript, design system in `frontend/src/assets/styles/variables.css`, components in `frontend/src/components/ui/` and `layout/`, views in `frontend/src/views/`, and features in `frontend/src/features/`. Frontend agent rules: `docs/agents/prompt-agente-frontend-studytrackpro.md`. Design: `docs/agents/prompt-agente-design-frontend-studytrackpro.md`.

---

## Agent Instructions

You must implement the frontend improvements listed below in the indicated order (or only those the user requests). For each item:

1. **Don't break** what already works; run tests and build after changes.
2. Use **only tokens** from `variables.css` in CSS; don't add hardcoded color, spacing, shadow, or typography values in components.
3. Maintain **accessibility** (contrast, visible focus, ARIA when needed).
4. Respect **`prefers-reduced-motion`** in any new animation or transition.

---

## High Priority Improvements

### 1. Replace Hardcoded Values in UI Components with Design System Tokens

**Objective:** No component in `src/components/ui/` should use fixed values (px, rem, #hex, rgba) for colors, spacing, radius, shadow, or typography. Everything should come from `variables.css`.

**Action:**

- Audit each component in `frontend/src/components/ui/` and list properties with fixed values.
- For each: map to an existing token or propose a new token in `variables.css` (with usage comment) and then use `var(--token)` in the component.
- Prioritize: **BaseButton**, **BaseStepper**, **BasePagination**, **Callout**, **ErrorCard**, **EmptyState**, **BaseToast**, **BaseModal**, **BaseInput**, **BaseProgress**, **BaseAvatar**, **StatCard**, **FilterBar**, **LoadingOverlay**.

**Deliverable:** Updated components; if new tokens are created, document with a comment in `variables.css` `:root`.

---

### 2. Respect `prefers-reduced-motion` in Animations and Transitions

**Objective:** Users who prefer less motion should not be impacted by unnecessary animations.

**Action:**

- Identify throughout the frontend where there are `transition`, `animation`, or motion libraries (@vueuse/motion, etc.).
- Wrap animations in `@media (prefers-reduced-motion: no-preference) { ... }` or use a class/condition that disables motion when `prefers-reduced-motion: reduce`.
- Ensure states (loading, success, error) are still communicated by text/icon/state, not just animation.

**Deliverable:** Listed changed files; a line in README or docs about reduced-motion support (optional).

---

### 3. Align WebSocket Payloads with TypeScript Types

**Objective:** Events received from Reverb (e.g., `session.started`, `session.ended`, `metrics.updated`) must be correctly typed in the frontend; no field used in code can be missing or have the wrong type in the backend.

**Action:**

- Read `frontend/src/types/websocket.types.ts` and the events in `useWebSocket` (or the composable that processes Echo).
- Check in the backend which events are dispatched (BroadcastSessionStarted, BroadcastSessionEnded, BroadcastMetricsUpdate, etc.) and the payload structure (e.g., `technology.slug`, `technology.id`, etc.).
- Adjust `websocket.types.ts` to exactly reflect the actual payload; adjust the code consuming events to use correct fields (e.g., slug vs id).
- Fix any inconsistencies (e.g., backend sends `slug`, frontend expects another field).

**Deliverable:** Updated types and handlers; if the backend needs a change, document in a comment or issue.

---

### 4. Add Prettier to Frontend and Integrate with ESLint

**Objective:** Consistent formatting without conflict with ESLint.

**Action:**

- Install Prettier and `eslint-config-prettier` (and optionally `eslint-plugin-prettier`) in the frontend.
- Create `.prettierrc` (or `.prettierrc.json`) with project preferences (e.g., singleQuote, trailingComma, tabWidth).
- Ensure ESLint doesn't re-rule styles already covered by Prettier (use eslint-config-prettier).
- Add script to `package.json`: `"format": "prettier --write \"src/**/*.{vue,ts,js,json,css}\""`.
- Optional: configure format on save in `.vscode/settings.json` of the project.

**Deliverable:** Configuration committed; README or CONTRIBUTING with mention of `npm run format`.

---

### 5. Accessible Focus Token and Usage on Interactive Controls

**Objective:** Every interactive control (button, link, input, tab, etc.) must have a visible and consistent focus indicator.

**Action:**

- In `variables.css`, define or ensure the existence of `--shadow-focus` (or `--color-focus-ring`) suitable for contrast in light and dark themes.
- Apply this token in all focusable components in `components/ui/` (BaseButton, BaseInput, BaseDropdown, BaseTabs, BaseModal trigger, etc.): use `outline` or `box-shadow` on `:focus-visible`, never `outline: none` without an alternative.
- Don't use `outline: none` without replacing with a visible focus ring.

**Deliverable:** Token defined; components updated; manual verification or with axe DevTools.

---

## Medium Priority Improvements

### 6. Tokens for Icon Sizes and Repeated Elements

**Objective:** Values like 1.25rem, 2rem for icons and controls should come from tokens (e.g., `--size-icon-sm`, `--size-icon-md`, `--size-touch-min` for 44px).

**Action:**

- Add size tokens (icon, minimum touch area) to `variables.css` and use them in components that currently use these fixed values.

---

### 7. Review Responsiveness (375px and 1440px)

**Objective:** Dashboard, session listing, and Export should be usable on mobile (375px) and desktop (1440px).

**Action:**

- Review breakpoints in `variables.css` (--screen-sm, --screen-md, etc.) and usage in each main view.
- Adjust grid, fonts, and spacing to not break layout or readability at 375px and 1440px.

---

### 8. Roles and ARIA on EmptyState and ErrorCard

**Objective:** EmptyState and ErrorCard should have adequate roles and ARIA for screen readers (e.g., `role="status"`, `aria-live` when dynamic).

**Action:**

- Add ARIA attributes and roles according to each component's purpose (information, alert, etc.).

---

### 9. Standardize Error Handling in API Calls

**Objective:** All calls passing through modules in `api/modules/` should handle errors consistently (toast, friendly message, retry when appropriate).

**Action:**

- Review each module (auth, sessions, technologies, analytics, goals) and ensure errors are mapped to a message and/or toast; avoid just `console.error` without user feedback.

---

## Low Priority Improvements

### 10. JSDoc on Composables and API Modules

Add JSDoc comments on public composables in `composables/` and on exported functions from modules in `api/modules/`, describing parameters, return value, and typical usage.

### 11. VeeValidate + Zod on Forms

Evaluate adopting VeeValidate with Zod on main forms (login, registration, session, technology) to align validation with the API schema and improve error messages.

---

## Pre-Completion Checklist

- [ ] `npm run build` without errors.
- [ ] `npm run lint` without errors (and, if it exists, `npm run test` for frontend unit tests).
- [ ] No hardcoded color/spacing/shadow/typography values in changed UI components; exclusive token usage.
- [ ] Visible focus on changed controls; reduced-motion preference respected where there's animation.
- [ ] WebSocket types and handlers aligned with backend.

---

## How to Use This Prompt

- **Implement all (high):** "Implement all high-priority improvements from docs/agents/prompt-implementar-melhorias-frontend.md."
- **Implement one item:** "Implement only item 3 (WebSocket) from docs/agents/prompt-implementar-melhorias-frontend.md."
- **Implement by topic:** "Implement the accessibility items (2, 5, and 8) from docs/agents/prompt-implementar-melhorias-frontend.md."

Include relevant files in context (e.g., `variables.css`, `useWebSocket.ts`, `websocket.types.ts`, a UI component) so the agent has immediate reference.
