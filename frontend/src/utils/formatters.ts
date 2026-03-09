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

/** Label para eixo Y e tooltip de gráficos: minutos → "1h", "2h", "1h 30min" */
export function formatMinutesToHoursLabel(minutes: number): string {
  if (minutes <= 0) return '0h'
  const h = Math.floor(minutes / 60)
  const m = Math.round(minutes % 60)
  if (m === 0) return `${h}h`
  return `${h}h ${m}min`
}

export function formatDate(dateStr: string | null | undefined, locale = 'pt-BR'): string {
  if (dateStr == null || dateStr === '') return '—'
  const d = new Date(dateStr)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString(locale, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

/** Formato curto para labels de gráficos (ex: "25/01") */
export function formatShortDate(dateStr: string | null | undefined, locale = 'pt-BR'): string {
  if (dateStr == null || dateStr === '') return '—'
  const d = new Date(dateStr)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString(locale, {
    day: '2-digit',
    month: '2-digit',
  })
}

export function formatDateTime(dateStr: string | null | undefined, locale = 'pt-BR'): string {
  if (dateStr == null || dateStr === '') return '—'
  const d = new Date(dateStr)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString(locale, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/** Número com separador de milhar (ex: 1.234) */
export function formatNumber(value: number, locale = 'pt-BR'): string {
  return value.toLocaleString(locale)
}

/** Porcentagem (0-100) com uma casa decimal */
export function formatPercent(value: number, decimals = 1): string {
  return `${Number(value.toFixed(decimals))}%`
}

/** Moeda (BRL) – útil para relatórios futuros */
export function formatCurrency(value: number, currency = 'BRL'): string {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(value)
}

/** Texto truncado com reticências */
export function truncate(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text
  const keep = Math.max(0, maxLength - 3)
  return text.slice(0, keep) + '...'
}

/** Iniciais a partir de um nome (ex: "João Silva" → "JS") */
export function initials(name: string | null | undefined): string {
  if (name == null || typeof name !== 'string') return '?'
  const trimmed = name.trim()
  if (!trimmed) return '?'
  const parts = trimmed.split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return trimmed.slice(0, 2).toUpperCase()
}

/** Tempo relativo (ex: "há 2 horas") – simplificado */
export function formatRelativeTime(iso: string | null | undefined, base = new Date()): string {
  if (iso == null || iso === '') return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  const diffMs = base.getTime() - d.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMin / 60)
  const diffDays = Math.floor(diffHours / 24)
  if (diffMin < 1) return 'agora'
  if (diffMin < 60) return `há ${diffMin} min`
  if (diffHours < 24) return `há ${diffHours}h`
  if (diffDays < 7) return `há ${diffDays} dia(s)`
  return formatDate(iso)
}
