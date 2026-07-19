# Design System — StudyTrack Pro

This document describes the design tokens and UI patterns used in the Vue frontend.

---

## 1. Colors

### Base Colors (Light Theme)
| Token | Usage |
|-------|-------|
| `--color-text` | Main text |
| `--color-text-muted` | Secondary text, captions |
| `--color-bg` | Page background |
| `--color-bg-soft` | Soft area background (hover, chips) |
| `--color-bg-card` | Card and elevated surface background |
| `--color-primary` | Primary actions, links, highlights |
| `--color-primary-hover` | Primary button hover |
| `--color-primary-soft` | Soft highlight background |
| `--color-border` | Borders and dividers |
| `--color-focus-ring` | Focus ring (accessibility) |

### Semantic Colors
| Token | Usage |
|-------|-------|
| `--color-success` | Success, confirmation |
| `--color-success-soft` | Success message background |
| `--color-warning` | Warning |
| `--color-warning-soft` | Warning background |
| `--color-error` | Error, destructive |
| `--color-error-soft` | Error background |
| `--color-info` | Neutral information |
| `--color-info-soft` | Info background |

---

## 2. Gradients
- `--gradient-primary`: buttons and highlights (blue → indigo).
- `--gradient-accent`: secondary highlights (green → blue).
- `--gradient-mesh`: subtle main area background (layout).

---

## 3. Spacing
Base scale in `rem`:
- `--spacing-2xs`: 0.125rem
- `--spacing-xs`: 0.25rem
- `--spacing-sm`: 0.5rem
- `--spacing-md`: 1rem
- `--spacing-lg`: 1.5rem
- `--spacing-xl`: 2rem
- `--spacing-2xl`: 2.5rem
- `--spacing-3xl`: 3rem

---

## 4. Typography
- **Font**: `--font-sans` (Inter + system-ui).
- **Sizes**: `--text-xs` (0.75rem) to `--text-3xl` (1.875rem).
- Prefer tokens over fixed values to maintain consistency.

---

## 5. Border Radius and Shadows
- **Radius**: `--radius-sm`, `--radius-md`, `--radius-lg`, `--radius-xl`.
- **Shadows**: `--shadow-sm`, `--shadow-md`, `--shadow-lg`, `--shadow-card-hover`.
- **Dropdown**: `--dropdown-shadow` for dropdown menus.

---

## 6. Motion
- **Durations**: `--duration-fast` (150ms), `--duration-normal` (250ms), `--duration-slow` (400ms).
- **Easing**: `--ease-out-expo`, `--ease-in-out`.
- Respect `prefers-reduced-motion` in global styles.

---

## 7. Breakpoints (Media Queries)
| Name | Width |
|------|-------|
| xs   | 480px |
| sm   | 640px |
| md   | 768px |
| lg   | 1024px |
| xl   | 1280px |

---

## 8. Base Components (UI)
- **BaseCard**: container with optional title and action slot.
- **BaseButton**: primary, secondary, ghost, danger, outline variants; sm, md, lg sizes.
- **BaseInput**: text field with label and error message.
- **BaseModal**: overlay with blur; scale animation on content.
- **BaseToast**: temporary notifications.
- **BaseBadge**: labels and counters.
- **BaseAvatar**: image or initials; sm, md, lg, xl sizes.
- **BaseTooltip**: hover/focus hint; top, bottom, left, right positions.
- **BaseTabs**: tabs (line, pill, enclosed variants).
- **BaseProgress**: progress bar (primary, success, warning, error); indeterminate option.
- **BaseDropdown**: dropdown menu with trigger and content slot.
- **BaseAccordion**: expandable items.
- **EmptyState**: empty state with icon, title, description, and optional action.
- **SkeletonLoader**: loading placeholder.
- **ErrorCard**: error message with retry.
- **ThemeToggle**: light/dark theme switch.

---

## 9. CSS Utilities (`utilities.css`)
Classes for margin/padding (m-0..m-5, mt-*, p-*, gap-*), typography (text-xs, font-bold, etc.), display and flex (flex, items-center, justify-between), width/height (w-full, min-h-screen), position (relative, absolute, z-10), borders and radius (rounded-lg, border), shadows (shadow-md), overflow, cursor, opacity, transitions (transition, transition-colors), accessibility (sr-only) and responsive visibility (hide-xs, show-md). Container with responsive max-width.

---

## 10. Animations and Transitions
- **fade**: opacity.
- **slide**: translateX.
- **fade-up**: bottom entry (opacity + translateY).
- **modal**: overlay fade + content with scale.
- **tooltip-fade**: used by BaseTooltip.
- **accordion**: used by BaseAccordion.
- **dropdown**: used by BaseDropdown.

---

## 11. Dark Theme
Activated with `[data-theme='dark']` on `<html>`. All color, gradient, shadow, and glass variables are redefined for the dark theme. Custom scrollbars for both themes.

---

## 12. Accessibility
- Visible outline with `:focus-visible` using `--color-primary`.
- Use of `role`, `aria-label`, `aria-expanded`, `aria-selected` on interactive components.
- Keyboard support (Tab, Enter, Escape) in modals and dropdowns.
- Respect `prefers-reduced-motion` to reduce animations.
