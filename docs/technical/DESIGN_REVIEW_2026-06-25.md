# Design Review — StudyTrackPro Frontend

**Date**: 2026-06-25
**Scope**: Design system, components, views, visual consistency, accessibility

---

## Summary

The design system is **well-structured** with consistent tokens, dark mode support, and good accessibility. The improvements below are for polish and consistency, not refactoring.

---

## 1. Color Palette — Contrast and Consistency

### 1.1 Very Cold Background in Light Mode
- **File**: `frontend/src/assets/styles/variables.css:22`
- **Issue**: `--color-bg: #d0dce9` is a cold blue-gray that may cause visual fatigue during prolonged use
- **Recommendation**: Consider a more neutral tone: `#f1f5f9` (slate-100) or `#f8fafc` (slate-50)
- **Priority**: MEDIUM

### 1.2 Secondary Text with Unused Token
- **File**: `frontend/src/assets/styles/variables.css:20`
- **Issue**: `--color-text-secondary: #334155` exists but is rarely used — `--color-text-muted` dominates
- **Recommendation**: Consolidate into a single token or define clear usage (secondary = hover states, muted = labels)
- **Priority**: LOW

---

## 2. Login — Visual Improvement Opportunities

### 2.1 Generic Title "StudyTrack Pro"
- **File**: `frontend/src/views/auth/LoginView.vue:43`
- **Issue**: `<h1>StudyTrack Pro</h1>` doesn't use `font-display` and looks like a placeholder
- **Recommendation**: Add gradient to title + decorative icon, or use the logo instead of text

### 2.2 OAuth Buttons Without Visual Identity
- **File**: `frontend/src/views/auth/LoginView.vue:139-168`
- **Issue**: Google/Discord buttons use `var(--color-bg-card)` — lose brand identity
- **Recommendation**: Google button with subtle `#4285F4` border, Discord with `#5865F2` on hover

---

## 3. Sidebar — Micro-interactions

### 3.1 Logo Without Collapse Animation
- **File**: `frontend/src/components/layout/AppSidebar.vue:500-508`
- **Issue**: Logo disappears abruptly when collapsing (opacity 0 + width 0)
- **Recommendation**: Add `transition: opacity` with delay to smooth it

### 3.2 Link Hover with translateX May Cause Layout Shift
- **File**: `frontend/src/components/layout/AppSidebar.vue:831`
- **Issue**: `transform: translateX(var(--spacing-2xs))` (2px) on hover may cause micro-jitter
- **Recommendation**: Use `padding-left` instead of transform, or increase gap to compensate

---

## 4. Dashboard — Layout and Visual

### 4.1 Dashboard Content Background with Opaque Mixin
- **File**: `frontend/src/views/Dashboard/DashboardView.vue:329`
- **Issue**: `background: color-mix(in srgb, var(--color-bg-soft) 40%, var(--color-bg))` — complex mix
- **Recommendation**: Simplify to `var(--color-bg-card)` or create a dedicated token

### 4.2 Stakent Style with Too Many Global Variables
- **File**: `frontend/src/assets/styles/variables.css:296-317`
- **Issue**: `[data-theme='dark'] .app-layout.stakent-style` redefines ~15 variables — hard to maintain
- **Recommendation**: Extract to a separate CSS file or use scoped CSS custom properties

---

## 5. UI Components — Consistency

### 5.1 EmptyState Border with Complex Mix
- **File**: `frontend/src/assets/styles/variables.css:145`
- **Issue**: `--empty-state-border: 1px solid color-mix(...)` — 3 inputs for 1 border
- **Recommendation**: Simplify to `1px dashed var(--color-primary)` or `1px solid var(--color-border)`

### 5.2 Form Inputs with Too Many Tokens
- **File**: `frontend/src/assets/styles/variables.css:168-187`
- **Issue**: 18 tokens for form inputs — cognitive overload
- **Recommendation**: Keep only: bg, border, border-focus, border-error, radius, height

---

## 6. Accessibility — Gaps

### 6.1 Touch Targets May Be Below 44px
- **File**: `frontend/src/components/layout/AppSidebar.vue:817`
- **Issue**: `min-height: 2.25rem` (36px) on sidebar links — below WCAG 2.5.5 (44px)
- **Recommendation**: Increase to `min-height: var(--touch-target-min)` (2.75rem = 44px)

### 6.2 Missing Skip Link
- **Issue**: No "Skip to content" link for keyboard navigation
- **Recommendation**: Add `<a href="#main" class="sr-only focus:not-sr-only">Skip to content</a>` at the beginning of body

---

## 7. Visual Performance

### 7.1 Skeleton Loaders with Unnecessary Border
- **File**: `frontend/src/views/Dashboard/DashboardView.vue:497-504`
- **Issue**: `.kpi-card-skeleton` has `border: 1px solid var(--color-border)` — unnecessary for placeholder
- **Recommendation**: Remove border from skeletons — only background + radius

### 7.2 Too Many Duplicate Media Queries
- **File**: `frontend/src/views/Dashboard/DashboardView.vue`
- **Issue**: `@media (min-width: 640px)` appears 2x, `@media (min-width: 1024px)` appears 2x
- **Recommendation**: Consolidate into one media query per breakpoint

---

## 8. Dark Mode — Gaps

### 8.1 Stakent Style Without Custom Scrollbar
- **File**: `frontend/src/assets/styles/variables.css:319-370`
- **Issue**: Custom scrollbar only for `[data-theme='dark']` — doesn't cover `.stakent-style`
- **Recommendation**: Add `.stakent-style ::-webkit-scrollbar` with purple colors

### 8.2 Gradient Mesh Not Visible in Dark Mode
- **File**: `frontend/src/assets/styles/variables.css:263-265`
- **Issue**: `--gradient-mesh` in dark mode uses very dark tones — almost invisible
- **Recommendation**: Increase opacity or use lighter tones

---

## Implementation Priorities

### High (Do First)
1. Increase touch targets in sidebar to 44px
2. Simplify form input tokens (reduce from 18 to ~8)
3. Add skip link for accessibility

### Medium (Next Sprint)
4. Review light mode palette (less cold background)
5. Improve OAuth button visuals on login
6. Consolidate duplicate media queries in Dashboard

### Low (When Possible)
7. Simplify empty state border
8. Add custom scrollbar for stakent
9. Improve gradient mesh visibility in dark mode

---

## Positive Notes

- ✅ Well-organized and documented design tokens
- ✅ Complete dark mode with consistent overrides
- ✅ `prefers-reduced-motion` respected
- ✅ Focus-visible with `--shadow-focus` on all interactive components
- ✅ Sidebar with smooth collapse animations
- ✅ Well-structured reusable UI components
- ✅ PageView with breadcrumb, header, and action slots
- ✅ Dashboard with lazy loading of heavy widgets
