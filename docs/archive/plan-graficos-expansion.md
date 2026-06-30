# Graficos Expansion — Implementation Plan

## Executive Summary

Expand the existing charting/reporting system into a dedicated, premium analytics page at `/graficos`. Fix all existing bugs, build a composable architecture for chart data, add new chart types (radar, funnel, treemap), implement export capabilities, and deliver a polished, animated experience following the existing Resend/Arc design language.

---

## Phase 0: Bug Fixes & Cleanup (Prerequisite)

### 0.1 Remove debug telemetry from BarChart

**File:** `frontend/src/components/charts/BarChart.vue`

- Delete the entire `onMounted` block (lines 355–380) containing the `fetch('http://127.0.0.1:7251/...')` call.
- Remove the `import { onMounted } from 'vue'` — only `computed` is needed.
- **Complexity:** Trivial

### 0.2 Fix ReportsView missing imports

**File:** `frontend/src/views/reports/ReportsView.vue`

- Add explicit imports for `LineChart` and `BarChart` (currently referenced but never imported — no global registration exists):
  ```ts
  import { defineAsyncComponent } from 'vue'
  const LineChart = defineAsyncComponent(() => import('@/components/charts/LineChart.vue'))
  const BarChart = defineAsyncComponent(() => import('@/components/charts/BarChart.vue'))
  ```
- Follow the lazy-load pattern used in `TimeSeriesWidget.vue` (line 10).
- **Complexity:** Trivial

### 0.3 Implement HeatmapChart component

**File:** `frontend/src/components/charts/HeatmapChart.vue` (62 lines — stub)

- Replace stub with a full implementation that wraps the SVG heatmap logic currently duplicated in:
  - `ReportsView.vue` (lines 232–241: CSS grid heatmap)
  - `HeatmapWidget.vue` (lines 89–112: SVG GitHub-style heatmap)
- **Design:** Reuse the SVG approach from `HeatmapWidget.vue` (superior UX: proper weekly grid, tooltips via `<title>`, year navigation).
- **Props:**
  ```ts
  {
    data?: { date: string; total_minutes: number }[]
    year?: number
    title?: string
    showYearSelector?: boolean
    loading?: boolean
  }
  ```
- Use `useApexChartTheme()` for colors, follow existing pattern of CSS variable usage.
- **Complexity:** Medium

### 0.4 Wire up chart.types.ts

**File:** `frontend/src/types/chart.types.ts` (43 lines — unused types)

- Extend existing types and add missing ones to serve as the shared contract:
  ```ts
  export interface BarChartData {
    labels: string[]
    values: number[]
    scores?: number[]
  }

  export interface DonutChartData {
    series: number[]
    labels: string[]
    colors?: string[]
    centerLabel?: string
  }

  export interface RadarDataPoint {
    label: string
    values: number[]
  }

  export interface RadarChartData {
    labels: string[]
    series: { name: string; data: number[] }[]
  }

  export interface FunnelDataPoint {
    label: string
    value: number
  }

  export interface TreemapDataPoint {
    label: string
    value: number
    children?: TreemapDataPoint[]
  }

  export type ChartType = 'line' | 'bar' | 'pie' | 'donut' | 'radar' | 'heatmap' | 'polarArea' | 'area' | 'sparkline'

  export interface DateRange {
    start: string  // ISO date
    end: string    // ISO date
  }

  export type ComparisonMode = 'absolute' | 'percentage' | 'overlay'
  ```
- Refactor existing chart components to import and use these types for their `data` props (gradual adoption — don't break existing APIs).
- **Complexity:** Low-Medium

---

## Phase 1: Foundation — Composables & Shared Infrastructure

### 1.1 `useGraficos` composable

**New file:** `frontend/src/features/graficos/composables/useGraficos.ts`

Central data orchestrator for the Graficos page. Manages:
- Fetching all analytics data via `analyticsApi`
- Date range state (start/end ISO dates)
- Technology filter state (selected tech IDs)
- Comparison mode state
- Derived computed properties for each chart type
- Loading/error states per data domain

```ts
export function useGraficos() {
  // State
  const dateRange = ref<DateRange>(defaultRange())
  const selectedTechIds = ref<string[]>([])
  const comparisonMode = ref<ComparisonMode>('absolute')
  const isLoading = ref(false)

  // Data (shallowRef for perf, matching store pattern)
  const timeSeriesData = shallowRef<DailyMinute[]>([])
  const weeklyData = shallowRef<WeeklySummary[]>([])
  const techStatsData = shallowRef<TechnologyMetric[]>([])
  const heatmapData = shallowRef<HeatmapDay[]>([])

  // Derived computeds for charts
  const timeSeriesForChart = computed(...)
  const weeklyForChart = computed(...)
  const techDistributionForChart = computed(...)
  const radarData = computed(...)  // multi-axis: hours, sessions, streak, focus
  const funnelData = computed(...) // hours → sessions → active days → streak
  const treemapData = computed(...) // tech hierarchy by minutes

  // Actions
  async function fetchAll() { ... }
  async function fetchForDateRange() { ... }
  function setDateRange(range: DateRange) { ... }
  function toggleTechFilter(techId: string) { ... }
  function exportPNG(chartRef: ApexCharts) { ... }
  function exportCSV(data: ...) { ... }

  return { ... }
}
```

**Complexity:** High

### 1.2 `useChartExport` composable

**New file:** `frontend/src/features/graficos/composables/useChartExport.ts`

Handles PNG, CSV, and PDF export:
- PNG: Use ApexCharts built-in `chart.exportTo({ format: 'png' })` — each VueApexCharts instance exposes this via `ref`.
- CSV: Generate from computed data arrays using a simple string builder.
- PDF: Extend the existing `usePdfGenerator` composable to support chart snapshots (html2canvas not needed — ApexCharts has native SVG export → can embed in JsPDF).

```ts
export function useChartExport() {
  const isExporting = ref(false)

  async function exportChartPNG(chartRef: Ref<ApexCharts | null>, filename?: string) { ... }
  async function exportDataCSV(data: { headers: string[]; rows: (string | number)[][] }, filename: string) { ... }
  async function exportFullReportPDF(data: GraficosReportData) { ... }

  return { isExporting, exportChartPNG, exportDataCSV, exportFullReportPDF }
}
```

**Complexity:** Medium

### 1.3 Graficos store (optional — thin wrapper)

**New file:** `frontend/src/stores/graficos.store.ts`

A thin Pinia store if we want Graficos-specific state to survive route changes. However, since `useGraficos` composable handles the data, this store may be unnecessary — evaluate during implementation. If needed, it would mirror `analytics.store.ts` pattern with `shallowRef` + `computed` merging.

**Complexity:** Low (or skip if composable-only approach works)

---

## Phase 2: New Chart Components

### 2.1 RadarChart component

**New file:** `frontend/src/components/charts/RadarChart.vue`

- ApexCharts `type: 'radar'` wrapped in `VueApexCharts`.
- Follows exact same pattern as existing charts: `useApexChartTheme()`, `useMediaQuery('(prefers-reduced-motion: reduce)')`, responsive height.
- **Props:**
  ```ts
  {
    title?: string
    series: { name: string; data: number[] }[]
    labels: string[]
    colors?: string[]
    showToolbar?: boolean
    chartHeight?: number
  }
  ```
- **Use case:** Multi-axis study profile (hours/sessions/streak/focus/mood).

**Complexity:** Medium

### 2.2 FunnelChart component

**New file:** `frontend/src/components/charts/FunnelChart.vue`

- ApexCharts `type: 'rangeBar'` configured as horizontal funnel (ApexCharts doesn't have native funnel; use horizontal bar with sorted descending values + gradient opacity).
- Alternative: Custom SVG funnel using the design tokens. This gives more control over the premium look.
- **Props:**
  ```ts
  {
    title?: string
    data: { label: string; value: number }[]
    colors?: string[]
    showToolbar?: boolean
    chartHeight?: number
  }
  ```
- **Use case:** Conversion funnel (total hours → active days → streak → top techs).

**Complexity:** High (custom SVG approach) / Medium (ApexCharts bar approach)

### 2.3 TreemapChart component

**New file:** `frontend/src/components/charts/TreemapChart.vue`

- ApexCharts `type: 'treemap'` wrapped in `VueApexCharts`.
- **Props:**
  ```ts
  {
    title?: string
    series: { x: string; y: number; color?: string }[]
    colors?: string[]
    showToolbar?: boolean
    chartHeight?: number
  }
  ```
- **Use case:** Technology time distribution as proportional rectangles.

**Complexity:** Medium

### 2.4 SparklineChart component

**New file:** `frontend/src/components/charts/SparklineChart.vue`

- Minimal wrapper around ApexCharts sparkline mode (no axes, no tooltips, just the line/area).
- Replaces the SVG polyline in `StakentMetricCard.vue` with a proper ApexCharts sparkline for consistency.
- **Props:**
  ```ts
  {
    data: number[]
    color?: string
    type?: 'line' | 'area'
    height?: number
    width?: number | string
  }
  ```
- **Use case:** KPI cards, inline metrics.

**Complexity:** Low

---

## Phase 3: Graficos Page View

### 3.1 Route registration

**Modified file:** `frontend/src/router/routes/reports.routes.ts`

Add the `/graficos` route alongside existing `/reports`:
```ts
export const reportsRoutes: RouteRecordRaw[] = [
  {
    path: '/reports',
    name: 'reports',
    component: () => import('@/views/reports/ReportsView.vue'),
    meta: { title: 'Relatórios' },
  },
  {
    path: '/graficos',
    name: 'graficos',
    component: () => import('@/views/graficos/GraficosView.vue'),
    meta: { title: 'Gráficos & Analytics' },
  },
]
```

**Complexity:** Trivial

### 3.2 GraficosView main page

**New file:** `frontend/src/views/graficos/GraficosView.vue`

Structure using `PageView` layout:
```
PageView (title="Gráficos & Analytics", breadcrumb=[Dashboard, Gráficos])
├── #actions slot: ExportMenu (PNG/CSV/PDF buttons)
├── #hint slot: RealtimeBadge (WebSocket status)
└── default slot:
    ├── GraficosToolbar (date range picker, tech filters, comparison mode)
    ├── KpiStrip (animated KPI cards)
    ├── ChartGrid (responsive CSS Grid)
    │   ├── TimeSeriesPanel (wide: full-width)
    │   ├── RadarPanel + TechDistributionPanel (side by side on desktop)
    │   ├── WeeklyBarPanel + HeatmapPanel (side by side on desktop)
    │   ├── FunnelPanel + TreemapPanel (side by side on desktop)
    │   └── TrendComparisonPanel (wide: full-width)
    └── ExportSection (bottom: full report export)
```

- Uses `defineAsyncComponent` for all chart panels (lazy load, same pattern as DashboardView).
- Stagger entrance animations using `animate-fade-in-up` + `stagger-1..8` classes.
- Skeleton loading states using PrimeVue `Skeleton`.
- `prefers-reduced-motion` check on all animations.

**Complexity:** High

### 3.3 GraficosToolbar component

**New file:** `frontend/src/views/graficos/components/GraficosToolbar.vue`

- Date range picker: Two PrimeVue Calendar inputs (start/end) with quick presets (7d, 30d, 90d, 1y, all).
- Technology filter: Multi-select dropdown (PrimeVue MultiSelect) listing all user technologies.
- Comparison mode toggle: Segment control (absolute / percentage / overlay) — same UI pattern as `TechDistributionWidget` toggle.
- Responsive: Stacks on mobile, horizontal on desktop.

**Complexity:** Medium

### 3.4 KpiStrip component

**New file:** `frontend/src/views/graficos/components/KpiStrip.vue`

- Row of 4-6 animated KPI cards showing: Total Hours, Avg Daily, Active Days, Best Day, Current Streak, Total Sessions.
- Each card uses `animate-bounce-in` with stagger delays.
- Numbers use `animate-pulse-soft` on value change.
- Uses `SparklineChart` for mini trend lines inside each card.
- Mobile: 2-column grid. Desktop: 6-column grid.

**Complexity:** Medium

### 3.5 Chart panels (individual sections)

Each panel is a lazy-loaded wrapper that:
1. Receives data from the parent (passed as props from GraficosView)
2. Renders a chart card with consistent styling (bg-card, border, radius-lg, shadow-sm)
3. Has its own loading skeleton
4. Has animated entrance via `animate-fade-in-up` + stagger class

**New files:**
- `frontend/src/views/graficos/components/TimeSeriesPanel.vue` — Wide line/area chart with period toggle
- `frontend/src/views/graficos/components/TechDistributionPanel.vue` — Donut + treemap toggle
- `frontend/src/views/graficos/components/WeeklyBarPanel.vue` — Bar chart for weekly data
- `frontend/src/views/graficos/components/HeatmapPanel.vue` — Year heatmap using HeatmapChart
- `frontend/src/views/graficos/components/RadarPanel.vue` — Multi-axis radar chart
- `frontend/src/views/graficos/components/FunnelPanel.vue` — Study funnel visualization
- `frontend/src/views/graficos/components/TrendComparisonPanel.vue` — Overlay line chart comparing periods

**Complexity:** Medium each (24 total)

---

## Phase 4: Animation Strategy

### 4.1 Page entrance
- Each chart panel uses `animate-fade-in-up` with stagger delays (`.stagger-1` through `.stagger-8`).
- KPI cards use `animate-bounce-in` with stagger.
- All wrapped in `@media (prefers-reduced-motion: no-preference)`.

### 4.2 Chart transitions
- When switching chart types (e.g., donut → treemap), use Vue `<Transition name="fade">` with the CSS transition from `transitions.css`.
- ApexCharts handles its own entrance animations via `chart.animations` config (already set on existing charts — copy pattern).

### 4.3 Data updates
- When filters change, ApexCharts re-renders with `dynamicAnimation` (already configured).
- KPI values animate via CSS `transition: all var(--duration-normal) var(--ease-out-expo)`.
- Loading → loaded transition: skeleton fades out, chart fades in (Vue `<Transition>`).

### 4.4 Hover effects
- Chart cards: `transition: transform var(--duration-fast) ease, box-shadow var(--duration-fast) ease` + `box-shadow: var(--shadow-card-hover)` on hover (same as `.reports__kpi:hover`).
- Buttons/toggles: existing toggle pattern from TechDistributionWidget.

### 4.5 Reduced motion
- All animations gated behind `@media (prefers-reduced-motion: no-preference)`.
- ApexCharts animations disabled via `useMediaQuery('(prefers-reduced-motion: reduce)')` (existing pattern).
- CSS `variables.css` already sets durations to 0.01ms for reduced motion.

---

## Phase 5: Export Capabilities

### 5.1 ExportMenu component

**New file:** `frontend/src/views/graficos/components/ExportMenu.vue`

- PrimeVue `Menu` dropdown with three options: PNG (all charts), CSV (data), PDF (full report).
- Positioned in PageView `#actions` slot.
- Uses `useChartExport` composable.

### 5.2 PNG export
- Use ApexCharts `exportTo()` API on each chart ref.
- Bundle multiple charts into a single page layout (optional: one at a time with filename prefix).

### 5.3 CSV export
- Generate from the filtered data arrays.
- Columns: Date, Minutes, Sessions, Technology, Streak (varies by dataset).
- Use `Blob` + `URL.createObjectURL` for download.

### 5.4 PDF export
- Extend `usePdfGenerator` composable to support Graficos data.
- Include: KPI summary, chart snapshots (via SVG → canvas → image in JsPDF), data tables.
- Reuse existing JsPDF + pretext setup.

**Complexity:** Medium-High

---

## Phase 6: Real-Time Updates

### 6.1 WebSocket integration
- GraficosView subscribes to `analyticsStore` which already listens to `metrics.updated` and `metrics.recalculating` via `useWebSocket.ts`.
- When `analyticsStore.isRecalculating` becomes true: show `RealtimeBadge` with shimmer animation.
- When `updateFromWebSocket` fires: refetch Graficos data via `useGraficos.fetchAll()`.
- Add `watch` on `analyticsStore.lastFetchAt` to trigger data refresh.

**Complexity:** Low (already implemented in store — just wire it up)

---

## Phase 7: Responsive Design

### 7.1 Breakpoint strategy
Following existing patterns from `variables.css` and `DashboardView.vue`:
- **Mobile (< 640px):** Single column, smaller chart heights, stacked filters.
- **Tablet (640px-1023px):** 2-column grid for charts, horizontal toolbar.
- **Desktop (≥ 1024px):** 12-column grid (matching DashboardView), full-height charts.

### 7.2 Grid layout
```css
.graficos-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-lg);
}
@media (min-width: 640px) {
  .graficos-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (min-width: 1024px) {
  .graficos-grid {
    grid-template-columns: repeat(12, 1fr);
  }
  .graficos-grid__wide { grid-column: 1 / -1; }
  .graficos-grid__half { grid-column: span 6; }
}
```

### 7.3 Chart height adaptation
- Use `useMediaQuery` (existing pattern from BarChart) to compute responsive heights.
- Mobile: `min-height: var(--widget-chart-min-height-sm)` (180px).
- Desktop: `min-height: var(--widget-chart-min-height-tall)` (260px) or explicit.

**Complexity:** Medium

---

## Phase 8: Testing

### 8.1 Unit tests

Following existing patterns from `analytics.store.spec.ts`:

**New test files:**
- `frontend/src/features/graficos/composables/__tests__/useGraficos.spec.ts`
  - Mock `analyticsApi`, test data fetching, filtering, derived computeds
- `frontend/src/features/graficos/composables/__tests__/useChartExport.spec.ts`
  - Mock ApexCharts API, test PNG/CSV generation
- `frontend/src/components/charts/__tests__/RadarChart.spec.ts`
  - Mount with `@vue/test-utils`, verify VueApexCharts renders with correct options
- `frontend/src/components/charts/__tests__/FunnelChart.spec.ts`
- `frontend/src/components/charts/__tests__/TreemapChart.spec.ts`
- `frontend/src/components/charts/__tests__/SparklineChart.spec.ts`
- `frontend/src/components/charts/__tests__/HeatmapChart.spec.ts` (replaces stub)

### 8.2 Component tests
- `frontend/src/views/graficos/__tests__/GraficosView.spec.ts`
  - Mount with mocked store, verify layout renders, filter interactions work
- `frontend/src/views/graficos/components/__tests__/GraficosToolbar.spec.ts`
- `frontend/src/views/graficos/components/__tests__/KpiStrip.spec.ts`

### 8.3 Test utilities
- Create shared mock for `useApexChartTheme` (many tests need it).
- Reuse `vi.mock('@/api/modules/analytics.api')` pattern from store tests.

**Complexity:** Medium

---

## File Manifest

### New files (22 files)

| Path | Description | Complexity |
|------|-------------|------------|
| `frontend/src/features/graficos/composables/useGraficos.ts` | Central data composable | High |
| `frontend/src/features/graficos/composables/useChartExport.ts` | Export (PNG/CSV/PDF) | Medium |
| `frontend/src/components/charts/RadarChart.vue` | Radar/spider chart | Medium |
| `frontend/src/components/charts/FunnelChart.vue` | Funnel visualization | High |
| `frontend/src/components/charts/TreemapChart.vue` | Treemap chart | Medium |
| `frontend/src/components/charts/SparklineChart.vue` | Inline sparkline | Low |
| `frontend/src/views/graficos/GraficosView.vue` | Main analytics page | High |
| `frontend/src/views/graficos/components/GraficosToolbar.vue` | Filters toolbar | Medium |
| `frontend/src/views/graficos/components/KpiStrip.vue` | KPI cards row | Medium |
| `frontend/src/views/graficos/components/TimeSeriesPanel.vue` | Time series section | Medium |
| `frontend/src/views/graficos/components/TechDistributionPanel.vue` | Tech distribution section | Medium |
| `frontend/src/views/graficos/components/WeeklyBarPanel.vue` | Weekly bar section | Medium |
| `frontend/src/views/graficos/components/HeatmapPanel.vue` | Heatmap section | Medium |
| `frontend/src/views/graficos/components/RadarPanel.vue` | Radar section | Medium |
| `frontend/src/views/graficos/components/FunnelPanel.vue` | Funnel section | Medium |
| `frontend/src/views/graficos/components/TrendComparisonPanel.vue` | Trend comparison section | Medium |
| `frontend/src/views/graficos/components/ExportMenu.vue` | Export dropdown | Low |
| `frontend/src/stores/graficos.store.ts` | Optional thin store | Low |
| + 3 test files | Unit tests for composables | Medium |
| + 5 test files | Unit tests for components | Medium |

### Modified files (5 files)

| Path | Changes |
|------|---------|
| `frontend/src/components/charts/BarChart.vue` | Remove debug telemetry (lines 355-380), remove `onMounted` import |
| `frontend/src/components/charts/HeatmapChart.vue` | Full implementation replacing stub (62 → ~180 lines) |
| `frontend/src/views/reports/ReportsView.vue` | Add missing `LineChart` and `BarChart` imports |
| `frontend/src/types/chart.types.ts` | Extend with BarChartData, RadarChartData, FunnelDataPoint, etc. |
| `frontend/src/router/routes/reports.routes.ts` | Add `/graficos` route |

---

## Implementation Order

| Step | Phase | Files | Est. Time |
|------|-------|-------|-----------|
| 1 | 0.1 | BarChart.vue (remove telemetry) | 5 min |
| 2 | 0.2 | ReportsView.vue (add imports) | 5 min |
| 3 | 0.4 | chart.types.ts (extend types) | 30 min |
| 4 | 0.3 | HeatmapChart.vue (implement) | 1.5 hrs |
| 5 | 1.1 | useGraficos.ts composable | 3 hrs |
| 6 | 2.4 | SparklineChart.vue | 30 min |
| 7 | 2.1 | RadarChart.vue | 1.5 hrs |
| 8 | 2.3 | TreemapChart.vue | 1 hr |
| 9 | 2.2 | FunnelChart.vue | 2 hrs |
| 10 | 3.1 | Router update | 5 min |
| 11 | 3.2 | GraficosView.vue (shell) | 2 hrs |
| 12 | 3.3 | GraficosToolbar.vue | 1.5 hrs |
| 13 | 3.4 | KpiStrip.vue | 1.5 hrs |
| 14 | 3.5 | All 7 chart panels | 4 hrs |
| 15 | 1.2 | useChartExport.ts | 1.5 hrs |
| 16 | 5.1 | ExportMenu.vue | 30 min |
| 17 | 6 | WebSocket wiring in GraficosView | 30 min |
| 18 | 7 | Responsive CSS refinements | 1 hr |
| 19 | 8 | All test files | 3 hrs |
| 20 | — | Type-check + lint pass | 30 min |

**Total estimated time: ~26 hours**

---

## Key Design Decisions

1. **Composable-first over store:** `useGraficos()` composable manages Graficos-specific state. Keeps the existing `analytics.store.ts` as the source of truth for dashboard data, avoiding duplication.

2. **Lazy loading:** All chart panels use `defineAsyncComponent` (same as DashboardView) for code splitting.

3. **SVG heatmap over CSS grid:** The SVG approach from `HeatmapWidget.vue` is superior (proper weekly alignment, tooltips, year navigation). Standardize on this in `HeatmapChart.vue`.

4. **Funnel as horizontal bar:** ApexCharts has no native funnel. Use horizontal bar with descending values + gradient opacity for the premium look. If this feels insufficient, implement a custom SVG funnel.

5. **Export as progressive enhancement:** PNG via ApexCharts native API (zero dependencies). CSV via string builder. PDF via existing jsPDF + pretext.

6. **No new dependencies:** Everything uses ApexCharts (installed), PrimeVue (installed), jsPDF (installed), and existing design tokens.
