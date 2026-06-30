# Cleanup/Modernization Plan: Graficos Feature Overlap

## Executive Summary

The new Graficos page (`/graficos`) has rendered several existing components redundant. This plan outlines a safe, phased approach to remove dead code, consolidate duplicates, and simplify the dashboard while maintaining backward compatibility.

---

## Current State Analysis

### Confirmed Duplicates

| Component | Lines | Overlap Level | Status |
|-----------|-------|---------------|--------|
| ReportsView.vue | 441 | **FULL** — same 4 API endpoints, inferior to GraficosView | DELETE |
| TimeSeriesWidget.vue | 170 | PARTIAL — duplicates hero chart in GraficosView | KEEP (dashboard summary) |
| WeeklyComparisonWidget.vue | 100 | PARTIAL — duplicates WeeklyBarPanel | KEEP (dashboard summary) |
| TechDistributionWidget.vue | 491 | PARTIAL — duplicates TechDistributionPanel | SIMPLIFY |
| HeatmapWidget.vue | 178 | UNUSED — not imported anywhere | DELETE |
| PieChart.vue | 226 | UNUSED — not imported anywhere | DELETE |

### Navigation Conflicts

- Sidebar has both "Relatórios" (`/reports`) and "Gráficos" (`/graficos`)
- Settings has `/settings/reports` pointing to ReportsView
- Legacy redirect exists: `/reports` → `settings-reports`

---

## Phase 1: Remove Dead Code (LOW RISK)

**Goal**: Delete unused components with zero import references.

### 1.1 Delete HeatmapWidget.vue
- **File**: `frontend/src/features/dashboard/components/HeatmapWidget.vue`
- **Lines**: 178
- **Risk**: NONE — grep confirms zero imports outside its own file
- **Test**: Verify DashboardView renders correctly (it doesn't use this widget)

### 1.2 Delete PieChart.vue
- **File**: `frontend/src/components/charts/PieChart.vue`
- **Lines**: 226
- **Risk**: NONE — grep confirms zero imports; TechDistributionPanel uses DonutChart instead
- **Test**: Run full test suite

### 1.3 Clean Up Type Definitions
- **File**: `frontend/src/types/chart.types.ts` — remove `PieChartData` interface if unused
- **Risk**: LOW — type-only change

---

## Phase 2: Remove ReportsView (MEDIUM RISK)

**Goal**: Eliminate the inferior ReportsView and redirect all routes to GraficosView.

### 2.1 Update Route Configuration
- **File**: `frontend/src/router/routes/reports.routes.ts`
- **Change**: Replace ReportsView import with redirect to `/graficos`
```ts
export const reportsRoutes: RouteRecordRaw[] = [
  {
    path: '/reports',
    redirect: '/graficos',
  },
  {
    path: '/graficos',
    name: 'graficos',
    component: () => import('@/views/graficos/GraficosView.vue'),
    meta: { title: 'Gráficos & Analytics' },
  },
]
```
- **Risk**: MEDIUM — users with bookmarked `/reports` will be redirected
- **Test**: Navigate to `/reports`, verify redirect to `/graficos`

### 2.2 Update Settings Route
- **File**: `frontend/src/router/routes/settings.routes.ts`
- **Change**: Redirect `/settings/reports` to `/graficos`
```ts
{
  path: 'reports',
  redirect: '/graficos',
},
```
- **Risk**: LOW — settings page was inferior anyway
- **Test**: Navigate to `/settings/reports`, verify redirect

### 2.3 Remove Sidebar Nav Item
- **File**: `frontend/src/constants/sidebar-nav.ts`
- **Change**: Remove the 'Relatórios' entry (lines 58-63)
- **Risk**: LOW — just removing a menu item
- **Test**: Verify sidebar shows only "Gráficos", not "Relatórios"

### 2.4 Delete ReportsView.vue
- **File**: `frontend/src/views/reports/ReportsView.vue`
- **Lines**: 441
- **Risk**: LOW after routes are updated — no remaining imports
- **Test**: Full navigation test across all routes

---

## Phase 3: Simplify Dashboard Widgets (MEDIUM RISK)

**Goal**: Reduce TechDistributionWidget from 491 lines by simplifying it to a lightweight summary card.

### 3.1 Simplify TechDistributionWidget.vue
- **File**: `frontend/src/features/dashboard/components/TechDistributionWidget.vue`
- **Strategy**: Convert to a simple summary card showing top 5 technologies with hours
- **Remove**: ApexCharts polar chart, BarChart integration, responsive viewport logic
- **Target**: ~150 lines (from 491)
- **Risk**: MEDIUM — changes dashboard appearance
- **Test**: Dashboard loads, shows tech distribution summary, links to `/graficos` for details

**Proposed simplified component**:
```vue
<template>
  <div class="tech-dist-widget">
    <div class="widget-header">
      <h3 class="widget-title">Distribuição por tecnologia</h3>
      <RouterLink to="/graficos" class="view-all-link">Ver gráficos →</RouterLink>
    </div>
    <div class="tech-list">
      <div v-for="tech in topTechs" :key="tech.name" class="tech-row">
        <span class="tech-name">{{ tech.name }}</span>
        <span class="tech-hours">{{ tech.hours }}h</span>
        <div class="tech-bar">
          <div class="tech-bar__fill" :style="{ width: tech.pct + '%', background: tech.color }" />
        </div>
      </div>
    </div>
  </div>
</template>
```

### 3.2 Keep TimeSeriesWidget and WeeklyComparisonWidget
- **Decision**: KEEP as-is — they serve as dashboard summaries
- **Rationale**: Dashboard ≠ full analytics page; lightweight chart previews are valuable
- **Risk**: NONE — no changes

---

## Phase 4: Dashboard View Cleanup (LOW RISK)

**Goal**: Remove the HeatmapWidget import from DashboardView (it was never used there anyway).

### 4.1 Verify DashboardView Imports
- **File**: `frontend/src/views/Dashboard/DashboardView.vue`
- **Check**: Confirm HeatmapWidget is NOT imported (it isn't — verified via grep)
- **Action**: No changes needed to DashboardView
- **Risk**: NONE

---

## Implementation Order

```
Phase 1 (Dead Code)     → Phase 2 (ReportsView)  → Phase 3 (Simplify)  → Phase 4 (Verify)
     ↓                         ↓                         ↓                      ↓
  Delete 2 files          Update 3 files            Rewrite 1 file        Run tests
  (HeatmapWidget,         (routes, sidebar)         (TechDistWidget)      (full suite)
   PieChart)
```

**Estimated Effort**: 2-3 hours
**Files Modified**: 5
**Files Deleted**: 3
**Lines Removed**: ~850+
**Lines Simplified**: ~340 (TechDistWidget 491→150)

---

## Risk Assessment

| Phase | Risk Level | Mitigation |
|-------|------------|------------|
| 1. Delete dead code | LOW | Grep confirms zero imports |
| 2. Remove ReportsView | MEDIUM | Redirects preserve backward compat |
| 3. Simplify TechDistWidget | MEDIUM | Keep same data, lighter rendering |
| 4. Dashboard cleanup | LOW | No changes needed |

**Rollback Strategy**: Each phase is independent. If Phase 3 causes issues, revert just that file.

---

## Testing Approach

### Unit Tests
- Run existing test suite after each phase
- No new tests needed (removing code, not adding logic)

### Integration Tests
- Navigate to `/reports` → verify redirect to `/graficos`
- Navigate to `/settings/reports` → verify redirect to `/graficos`
- Verify sidebar shows only "Gráficos"
- Dashboard loads with simplified TechDistWidget

### Manual QA
- Dashboard page renders correctly
- Graficos page renders correctly
- No console errors
- Responsive behavior maintained

---

## Backward Compatibility

- `/reports` → 301 redirect to `/graficos` (preserves bookmarks)
- `/settings/reports` → redirect to `/graficos`
- Legacy redirects in `settings.routes.ts` updated
- No API changes (backend endpoints remain)

---

## Files Summary

### DELETE
1. `frontend/src/features/dashboard/components/HeatmapWidget.vue` (178 lines)
2. `frontend/src/components/charts/PieChart.vue` (226 lines)
3. `frontend/src/views/reports/ReportsView.vue` (441 lines)

### MODIFY
1. `frontend/src/router/routes/reports.routes.ts` — redirect `/reports` to `/graficos`
2. `frontend/src/router/routes/settings.routes.ts` — redirect `/settings/reports` to `/graficos`
3. `frontend/src/constants/sidebar-nav.ts` — remove 'Relatórios' nav item
4. `frontend/src/features/dashboard/components/TechDistributionWidget.vue` — simplify to ~150 lines

### NO CHANGES NEEDED
- `frontend/src/views/Dashboard/DashboardView.vue` — already doesn't use HeatmapWidget
- `frontend/src/views/graficos/GraficosView.vue` — no changes
- All Graficos components — no changes
- Backend API — no changes

---

*Plan created: 2026-06-27*
*Status: Ready for implementation*
