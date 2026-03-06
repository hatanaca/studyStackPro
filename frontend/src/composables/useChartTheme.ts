import { computed } from 'vue'
import { useUiStore } from '@/stores/ui.store'

function getCssVar(name: string): string {
  if (typeof document === 'undefined') return ''
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || ''
}

function hexToRgba(hex: string, alpha: number): string {
  const match = hex.replace('#', '').match(/.{2}/g)
  if (!match) return hex
  const [r, g, b] = match.map((x) => parseInt(x, 16))
  return `rgba(${r},${g},${b},${alpha})`
}

function rgbToRgba(rgb: string, alpha: number): string {
  const m = rgb.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/)
  if (!m) return rgb
  return `rgba(${m[1]},${m[2]},${m[3]},${alpha})`
}

function toRgba(cssColor: string, alpha: number): string {
  const c = cssColor.trim()
  if (c.startsWith('rgba')) return c
  if (c.startsWith('rgb')) return rgbToRgba(c, alpha)
  if (c.startsWith('#')) return hexToRgba(c, alpha)
  return c
}

/**
 * Cores do tema atual para uso em Chart.js (respeita tema claro/escuro).
 */
export function useChartTheme() {
  const uiStore = useUiStore()

  const themeColors = computed(() => {
    void uiStore.theme // re-run when theme changes
    const primary = getCssVar('--color-primary')
    const textMuted = getCssVar('--color-text-muted')
    const border = getCssVar('--color-border')
    return {
      primary: primary || '#3b82f6',
      primaryFill: toRgba(primary || '#3b82f6', 0.12),
      textColor: textMuted || '#64748b',
      gridColor: border || '#e2e8f0',
    }
  })

  return { themeColors }
}
