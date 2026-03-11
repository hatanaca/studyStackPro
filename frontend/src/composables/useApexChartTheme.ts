import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import { useUiStore } from '@/stores/ui.store'

/** Lê variável CSS do :root (SSR-safe) */
function getCssVar(name: string): string {
  if (typeof document === 'undefined') return ''
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || ''
}

/**
 * Tema ApexCharts baseado no design system (variables.css).
 * Reage ao tema claro/escuro e expõe opções base para gráficos.
 */
export function useApexChartTheme() {
  const uiStore = useUiStore()

  const theme = computed(() => {
    void uiStore.theme
    const text = getCssVar('--color-text')
    const textMuted = getCssVar('--color-text-muted')
    const border = getCssVar('--color-border')
    const bgCard = getCssVar('--color-bg-card')
    const primary = getCssVar('--color-primary')
    const success = getCssVar('--color-success')
    const warning = getCssVar('--color-warning')
    const error = getCssVar('--color-error')
    const info = getCssVar('--color-info')
    const fontSans = getCssVar('--font-sans') || 'Inter, sans-serif'
    const fontSizeSm = getCssVar('--text-xs') || '0.75rem'
    const fontSizeAxis = getCssVar('--chart-axis-font-size') || getCssVar('--text-sm') || '0.875rem'

    return {
      textColor: text || '#0f172a',
      textMuted: textMuted || '#64748b',
      gridColor: border || '#e2e8f0',
      background: bgCard || '#ffffff',
      fontFamily: fontSans,
      fontSize: fontSizeSm,
      fontSizeAxis,
      palette: [
        primary || '#3b82f6',
        success || '#22c55e',
        warning || '#f59e0b',
        error || '#ef4444',
        info || '#0ea5e9',
        '#8b5cf6',
        '#ec4899',
      ],
      strokeColor: uiStore.theme === 'dark' ? 'rgba(255,255,255,0.1)' : 'rgba(15,23,42,0.06)',
    }
  })

  /** Opções base ApexCharts para mesclar em qualquer gráfico (grid, tooltip, legend, fontes) */
  const baseOptions = computed<Partial<ApexOptions>>(() => {
    const t = theme.value
    return {
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
        labels: { colors: t.textMuted, style: { fontSize: t.fontSize, fontFamily: t.fontFamily } },
        itemMargin: { horizontal: 8, vertical: 4 },
      },
      dataLabels: {
        style: { fontSize: t.fontSize, fontFamily: t.fontFamily },
      },
      stroke: {
        colors: [t.strokeColor],
      },
    }
  })

  /** Paleta de cores para séries (pie, bar, line) quando não fornecida por prop */
  const palette = computed(() => theme.value.palette)

  return { theme, baseOptions, palette }
}
