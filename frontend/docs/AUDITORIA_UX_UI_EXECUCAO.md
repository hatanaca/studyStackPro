# UX/UI Audit To-Do Execution

This document consolidates the execution of the 4 assigned UX/UI audit to-dos, focusing on real-impact fixes, a single experience pattern, and low-risk quick wins.

## 1) `audit-p0-confiabilidade` (Completed)

### Objective
Prioritize reliability fixes on actions and overlays to reduce deceptive behavior and flow abandonment risk.

### Confirmed Diagnosis
- Export CTA in `src/views/settings/DataSection.vue` shows progress but doesn't generate a file or persistent final feedback.
- Base modal in `src/components/ui/BaseModal.vue` still lacks minimum dialog semantics (`role="dialog"`, `aria-modal`) and focus cycle.
- Mobile drawer in `src/components/layout/AppSidebar.vue` opens/closes visually but without robust focus management and no `Escape` closing.

### Prioritization by Impact
- **P0.1 (high impact, low effort):** Adjust copy/feedback of local data CTA to avoid expectation of server session export.
- **P0.2 (high impact, medium effort):** Evolve `BaseModal` to minimum a11y contract (semantics, initial focus, focus return, `Escape`, scroll lock).
- **P0.3 (high impact, medium effort):** Apply guided focus in mobile drawer (`AppSidebar`) reusing `useFocusTrap` and defining initial focused element.

### Expected Outcome
- Fewer "false" actions, predictable overlays, and reliable keyboard navigation in critical flows.

## 2) `audit-p1-consistencia-fluxo` (Completed)

### Objective
Map screen state divergences, global feedback, and destructive confirmations, and define a single pattern.

### Mapped Divergences
- **Destructive confirmations**
  - `src/features/technologies/components/TechnologyList.vue`: native `confirm(...)`.
  - `src/views/profile/ProfileView.vue`: `window.confirm(...)` for global revocation.
  - `src/features/goals/components/GoalList.vue`: `useConfirm` (PrimeVue ConfirmDialog).
  - `src/features/sessions/components/SessionList.vue`: custom `Dialog` for delete.
- **Loading/empty/error states**
  - `src/views/Dashboard/DashboardView.vue`: combines `Skeleton`, `Message`, and empty state with more detailed copy.
  - `src/features/sessions/components/SessionList.vue`: simple textual loading/empty.
  - `src/views/profile/ProfileView.vue`: partial loading per tab and errors via toast.
- **Global feedback**
  - `src/App.vue` uses PrimeVue `Toast` + `ConfirmDialog`.
  - `src/composables/useToast.ts` delegates to PrimeVue and kept legacy API.
  - `src/components/ui/BaseToast.vue` remains as a legacy component without real functional use.

### Proposed Single Pattern
- **Destructive confirmation:** centralize in `ConfirmDialog` (PrimeVue) with severity, verbs, and standard copy per risk level.
- **Screen states:** single contract `loading / empty / error / ready` with base components (`Skeleton`, `EmptyState`, `ErrorCard`) and consistent microcopy.
- **Global feedback:** keep only `useToast` + PrimeVue `Toast`; discontinue `BaseToast` to avoid dual source of truth.

### UX Rules for All Flows
- Error always with a clear next action ("Try again", "Reload", "Go back").
- Empty state always with a contextual CTA.
- Destructive action always with an explicit verb (`Delete`, `Revoke`) and scope in the copy.

## 3) `audit-design-system-gaps` (Completed)

### Objective
Consolidate tokenization and breakpoint gaps with low-risk quick wins.

### Identified Gaps
- `src/assets/styles/variables.css` defines breakpoints in tokens (`--screen-*`), but there are still hardcoded media queries in components.
- `src/components/layout/AppTopBar.vue` uses hardcoded `0.65rem`, `4px`, `1024px`, and `640px`.
- `src/components/layout/AppSidebar.vue` uses hardcoded `769px/768px`, `280px`, `85vw`, `rgba(0, 0, 0, 0.5)`, and shadow in the drawer.
- `src/views/Dashboard/DashboardView.vue` still has hardcoded breakpoints (`480px`, `640px`, `1024px`) in some blocks.

### Low-Risk Quick Wins (Recommended Order)
- **QW-1:** Replace `@media (max-width: 640px|1024px|768px|480px)` with `var(--screen-*)` in the shell (`AppLayout`, `AppTopBar`, `AppSidebar`).
- **QW-2:** Promote tokens for small radius/text (`--radius-xs`, `--text-2xs`) and remove hardcoded `4px`/`0.65rem`.
- **QW-3:** Create overlay tokens (`--overlay-backdrop`, `--overlay-shadow`) for drawer/modal.
- **QW-4:** Normalize mobile widths (`min(280px, 85vw)`, `min(90vw, 420px)`) via overlay/panel tokens.

### Expected Gain
- Less visual divergence between pages, safer maintenance, and responsive predictability between 375px and 1440px.

## 4) `audit-polimento-a11y-visual` (Completed)

### Objective
List fine-tuning adjustments for focus, micro-interactions, and microcopy per component to elevate clarity and consistency without a redesign.

### Adjustment List by Component
- `src/features/technologies/components/TechnologyCard.vue`
  - Ensure `:focus-visible` on card actions (edit/delete) with `--shadow-focus`.
  - Standardize hover/focus for the same highlight hierarchy.
- `src/features/sessions/components/SessionList.vue`
  - Migrate custom inputs from `:focus` to `:focus-visible` where applicable.
  - Apply visible focus on pagination buttons (`.pagination__btn`).
- `src/components/layout/AppTopBar.vue`
  - Add focus state on links/icons (`brand`, `icon-btn`) with focus token.
  - Adjust dynamic title microcopy to avoid ambiguous terms ("Top Study Metrics").
- `src/views/reports/ReportsView.vue`, `src/views/settings/AppearanceSection.vue`, `src/views/help/HelpView.vue`
  - Reduce placeholder CTA noise with no real action.
  - Include explicit status ("In development") with a concrete next action.
- `src/components/ui/BaseModal.vue` and `src/components/layout/AppSidebar.vue`
  - Ensure predictable tab order, initial focus, and focus return to trigger.
  - `Escape` closing with coherent screen reader announcement.

### Polishing Objective Checklist
- All interactive controls with `:focus-visible`.
- No CTA without observable effect.
- No vague microcopy in empty/placeholder states.
- Consistent micro-interactions in duration and visual intensity.

## Recommended Backlog (2 Sprints)

- **Sprint 1 (reliability + consistency):**
  - P0.1, P0.2, P0.3
  - Single destructive confirmation pattern
  - `BaseToast` discontinuation
- **Sprint 2 (design system + polish):**
  - QW-1 to QW-4
  - Focus/micro-interactions per component
  - Final microcopy review on placeholder screens

## Acceptance Criteria for This Execution

- P0/P1/P2 items transformed into actionable backlog with clear priority.
- Divergences mapped with source files.
- Target pattern defined for states, feedback, and confirmations.
- Tokenization/breakpoint quick wins defined with low regression risk.
