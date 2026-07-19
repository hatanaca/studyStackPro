# Design Token Reference

Complete list of CSS variables available in the project and usage examples.

---

## Colors — Text and Background

| Token | Description | Typical Usage |
|-------|-------------|---------------|
| `--color-text` | Main text | Body text, headings |
| `--color-text-muted` | Secondary text | Captions, placeholders, hints |
| `--color-bg` | Page background | body, main |
| `--color-bg-soft` | Soft background | Hover, chips, highlighted areas |
| `--color-bg-card` | Card background | Cards, modals, dropdowns |
| `--color-primary` | Primary color | Buttons, links, highlights |
| `--color-primary-hover` | Primary hover | Hover state on primary button |
| `--color-primary-soft` | Soft primary background | Badges, initial avatars |
| `--color-border` | Borders | Dividers, inputs, cards |
| `--color-focus-ring` | Focus ring | outline on :focus-visible |

Example:
```css
.my-title { color: var(--color-text); }
.my-hint { color: var(--color-text-muted); }
.my-card { background: var(--color-bg-card); border: 1px solid var(--color-border); }
```

---

## Semantic Colors

| Token | Description |
|-------|-------------|
| `--color-success` | Success, confirmation |
| `--color-success-soft` | Success message background |
| `--color-warning` | Warning |
| `--color-warning-soft` | Warning background |
| `--color-error` | Error, destructive |
| `--color-error-soft` | Error background |
| `--color-info` | Information |
| `--color-info-soft` | Info background |

---

## Gradients

| Token | Description |
|-------|-------------|
| `--gradient-primary` | Primary gradient (blue → indigo) |
| `--gradient-accent` | Accent gradient (green → blue) |
| `--gradient-mesh` | Main area mesh background |

---

## Spacing

| Token | Value (rem) |
|-------|-------------|
| `--spacing-2xs` | 0.125 |
| `--spacing-xs` | 0.25 |
| `--spacing-sm` | 0.5 |
| `--spacing-md` | 1 |
| `--spacing-lg` | 1.5 |
| `--spacing-xl` | 2 |
| `--spacing-2xl` | 2.5 |
| `--spacing-3xl` | 3 |

Use in margin, padding, and gap for consistency.

---

## Border Radius

| Token | Usage |
|-------|-------|
| `--radius-sm` | Chips, small badges |
| `--radius-md` | Inputs, buttons |
| `--radius-lg` | Cards, modals |
| `--radius-xl` | Large cards, sections |

---

## Shadows

| Token | Usage |
|-------|-------|
| `--shadow-sm` | Light elevation |
| `--shadow-md` | Cards, dropdowns |
| `--shadow-lg` | Modals, overlays |
| `--shadow-card-hover` | Card hover state |
| `--dropdown-shadow` | Dropdown panel |

---

## Typography

| Token | Size | Usage |
|-------|------|-------|
| `--font-sans` | Family | Main font |
| `--text-xs` | 0.75rem | Captions, hints |
| `--text-sm` | 0.875rem | Secondary text, buttons |
| `--text-base` | 1rem | Body |
| `--text-lg` | 1.125rem | Subtitles |
| `--text-xl` | 1.25rem | Section headings |
| `--text-2xl` | 1.5rem | Page headings |
| `--text-3xl` | 1.875rem | Hero, highlight |

---

## Motion

| Token | Value | Usage |
|-------|-------|-------|
| `--ease-out-expo` | cubic-bezier(0.16, 1, 0.3, 1) | Entries, animations |
| `--ease-in-out` | cubic-bezier(0.4, 0, 0.2, 1) | Smooth transitions |
| `--duration-fast` | 150ms | Hover, focus |
| `--duration-normal` | 250ms | State transitions |
| `--duration-slow` | 400ms | Content entry |

---

## Component-Specific

| Token | Usage |
|-------|-------|
| `--sidebar-width` | Sidebar width |
| `--input-height-sm/md/lg` | Input heights |
| `--avatar-size-sm/md/lg/xl` | Avatar sizes |
| `--tooltip-offset` | Tooltip distance from trigger |
| `--glass-bg` | Glass background (backdrop) |
| `--glass-border` | Glass border |

---

## Dark Theme

All tokens above are redefined in `[data-theme='dark']`. Don't use fixed colors in components; always prefer variables so the dark theme works.

---

## Breakpoints (Media Queries)

These are not CSS variables; use fixed values or JS constants:

- 480px (xs)
- 640px (sm)
- 768px (md)
- 1024px (lg)
- 1280px (xl)

Example:
```css
@media (min-width: 768px) {
  .sidebar { width: var(--sidebar-width); }
}
```
