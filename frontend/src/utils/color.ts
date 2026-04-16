/**
 * Utilitários de manipulação de cores (hex)
 */

/** Hex equivalente de --color-primary, usado como fallback em utils */
const DEFAULT_PRIMARY_HEX = '#3b82f6'

/** Normaliza valor hex (#RGB ou #RRGGBB) para formato #RRGGBB */
export function normalizeHexColor(hex: string, fallback = DEFAULT_PRIMARY_HEX): string {
  const m = hex.match(/^#?([0-9A-Fa-f]+)$/)
  if (!m) return fallback
  const s = m[1]
  if (s.length === 6) return '#' + s
  if (s.length === 3)
    return (
      '#' +
      s
        .split('')
        .map((c) => c + c)
        .join('')
    )
  return fallback
}

/** Valida que o hex é formato #RRGGBB, retorna fallback se inválido */
export function safeHexColor(hex: string | undefined, fallback = DEFAULT_PRIMARY_HEX): string {
  return hex && /^#[0-9A-Fa-f]{6}$/.test(hex) ? hex : fallback
}

export function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return result
    ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16),
      }
    : null
}

export function rgbToHex(r: number, g: number, b: number): string {
  return '#' + [r, g, b].map((x) => x.toString(16).padStart(2, '0')).join('')
}

export function darken(hex: string, percent: number): string {
  const rgb = hexToRgb(hex)
  if (!rgb) return hex
  const factor = 1 - percent / 100
  return rgbToHex(
    Math.round(rgb.r * factor),
    Math.round(rgb.g * factor),
    Math.round(rgb.b * factor)
  )
}

export function lighten(hex: string, percent: number): string {
  const rgb = hexToRgb(hex)
  if (!rgb) return hex
  const factor = percent / 100
  return rgbToHex(
    Math.min(255, Math.round(rgb.r + (255 - rgb.r) * factor)),
    Math.min(255, Math.round(rgb.g + (255 - rgb.g) * factor)),
    Math.min(255, Math.round(rgb.b + (255 - rgb.b) * factor))
  )
}
