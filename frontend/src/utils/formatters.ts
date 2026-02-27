/**
 * Funções puras de formatação (sem efeitos colaterais)
 */

export function formatDuration(minutes: number): string {
  if (minutes < 60) return `${minutes}min`
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return m > 0 ? `${h}h ${m}min` : `${h}h`
}

export function formatHours(minutes: number): string {
  const h = (minutes / 60).toFixed(1)
  return `${h}h`
}

export function formatDate(dateStr: string, locale = 'pt-BR'): string {
  const d = new Date(dateStr)
  return d.toLocaleDateString(locale, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

/** Formato curto para labels de gráficos (ex: "25/01") */
export function formatShortDate(dateStr: string, locale = 'pt-BR'): string {
  const d = new Date(dateStr)
  return d.toLocaleDateString(locale, {
    day: '2-digit',
    month: '2-digit',
  })
}

export function formatDateTime(dateStr: string, locale = 'pt-BR'): string {
  const d = new Date(dateStr)
  return d.toLocaleString(locale, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
