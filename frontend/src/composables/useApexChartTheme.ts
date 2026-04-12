import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import { useUiStore } from '@/stores/ui.store'

interface ThemeSnapshot {
  textColor: string
  textMuted: string
  gridColor: string
  background: string
  fontFamily: string
  fontSize: string
  fontSizeAxis: string
  stockColor: string
  palette: string[]
  strokeColor: string
}

/**
 * Module-level CSS variable cache.
 * A single getComputedStyle call per theme change instead of N per chart.
 */
let _cachedThemeKey = ''
let _snapshot: ThemeSnapshot = buildDefaults('light')

function buildDefaults(mode: string): ThemeSnapshot {
  const isDark = mode === 'dark'
  return {
    textColor: isDark ? '#f1f5f9' : '#0f172a',
    textMuted: isDark ? '#94a3b8' : '#64748b',
    gridColor: isDark ? 'rgba(148,163,184,0.2)' : '#e2e8f0',
    background: isDark ? '#1e293b' : '#ffffff',
    fontFamily: 'Inter, sans-serif',
    fontSize: '0.75rem',
    fontSizeAxis: '0.875rem',
    stockColor: isDark ? '#60a5fa' : '#3b82f6',
    palette: [
      isDark ? '#60a5fa' : '#3b82f6',
      isDark ? '#4ade80' : '#22c55e',
      isDark ? '#fbbf24' : '#f59e0b',
      isDark ? '#f87171' : '#ef4444',
      isDark ? '#38bdf8' : '#0ea5e9',
      '#8b5cf6',
      '#ec4899',
    ],
    strokeColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(15,23,42,0.06)',
  }
}

function refreshSnapshot(themeKey: string): ThemeSnapshot {
  if (themeKey === _cachedThemeKey) return _snapshot
  _cachedThemeKey = themeKey

  if (typeof document === 'undefined') {
    _snapshot = buildDefaults(themeKey)
    return _snapshot
  }

  const s = window.getComputedStyle(document.documentElement)
  const g = (name: string) => s.getPropertyValue(name).trim()

  const text = g('--color-text')
  const textMuted = g('--color-text-muted')
  const border = g('--color-border')
  const bgCard = g('--color-bg-card')
  const primary = g('--color-primary')
  const success = g('--color-success')
  const warning = g('--color-warning')
  const error = g('--color-error')
  const info = g('--color-info')
  const fontSans = g('--font-sans') || 'Inter, sans-serif'
  const fontSizeSm = g('--text-xs') || '0.75rem'
  const fontSizeAxis = g('--chart-axis-font-size') || g('--text-sm') || '0.875rem'
  const stockColor = g('--chart-line-stock-color') || primary || '#3b82f6'

  const defaults = buildDefaults(themeKey)

  _snapshot = {
    textColor: text || defaults.textColor,
    textMuted: textMuted || defaults.textMuted,
    gridColor: border || defaults.gridColor,
    background: bgCard || defaults.background,
    fontFamily: fontSans,
    fontSize: fontSizeSm,
    fontSizeAxis,
    stockColor,
    palette: [
      primary || defaults.palette[0],
      success || defaults.palette[1],
      warning || defaults.palette[2],
      error || defaults.palette[3],
      info || defaults.palette[4],
      '#8b5cf6',
      '#ec4899',
    ],
    strokeColor: themeKey === 'dark' ? 'rgba(255,255,255,0.1)' : 'rgba(15,23,42,0.06)',
  }
  return _snapshot
}

/** Invalida o cache (ex.: após customTheme ser aplicado). */
export function invalidateChartThemeCache() {
  _cachedThemeKey = ''
}

/**
 * Tema ApexCharts baseado no design system (variables.css).
 * CSS variables são lidas uma única vez por mudança de tema e cacheadas
 * no nível do módulo — todos os gráficos compartilham o mesmo snapshot.
 */
export function useApexChartTheme() {
  const uiStore = useUiStore()

  const theme = computed(() => refreshSnapshot(uiStore.theme))

  let _baseOptionsCache: { key: string; value: Partial<ApexOptions> } | null = null

  const baseOptions = computed<Partial<ApexOptions>>(() => {
    const t = theme.value
    const key = _cachedThemeKey
    if (_baseOptionsCache && _baseOptionsCache.key === key) return _baseOptionsCache.value

    const opts: Partial<ApexOptions> = {
      chart: {
        background: 'transparent',
        fontFamily: t.fontFamily,
      },
      grid: {
        borderColor: t.gridColor,
        strokeDashArray: 0,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
      },
      xaxis: {
        labels: { style: { colors: t.textMuted, fontSize: t.fontSizeAxis } },
        axisBorder: { color: t.gridColor },
        axisTicks: { color: t.gridColor },
      },
      yaxis: {
        labels: { style: { colors: t.textMuted, fontSize: t.fontSizeAxis } },
        axisBorder: { show: false },
        crosshairs: { show: false },
      },
      tooltip: {
        theme: uiStore.theme === 'dark' ? 'dark' : 'light',
        style: { fontSize: t.fontSize, fontFamily: t.fontFamily },
      },
      legend: {
        labels: { colors: t.textMuted },
        fontSize: t.fontSize,
        fontFamily: t.fontFamily,
        itemMargin: { horizontal: 8, vertical: 4 },
      },
      dataLabels: {
        style: { fontSize: t.fontSize, fontFamily: t.fontFamily },
      },
      stroke: {
        colors: [t.strokeColor],
      },
    }
    _baseOptionsCache = { key, value: opts }
    return opts
  })

  const palette = computed(() => theme.value.palette)

  return { theme, baseOptions, palette }
}
