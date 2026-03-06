/**
 * Utilitários de data para o frontend
 */

const PT_BR_OPTIONS: Intl.DateTimeFormatOptions = {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
}

/**
 * Formata uma data ISO para exibição em pt-BR (data e hora).
 */
export function formatDateTime(iso: string | null | undefined): string {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('pt-BR', PT_BR_OPTIONS)
  } catch {
    return iso
  }
}

/**
 * Formata uma data ISO para exibição em pt-BR (apenas data).
 */
export function formatDate(iso: string | null | undefined): string {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  } catch {
    return iso
  }
}

/**
 * Retorna a data no formato YYYY-MM-DD (para inputs type="date").
 */
export function toDateInputValue(iso: string | Date | null | undefined): string {
  if (!iso) return ''
  const d = typeof iso === 'string' ? new Date(iso) : iso
  if (isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/**
 * Retorna o início do dia em ISO para uma data local.
 */
export function startOfDay(date: Date): Date {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  return d
}

/**
 * Retorna o fim do dia (23:59:59.999) para uma data local.
 */
export function endOfDay(date: Date): Date {
  const d = new Date(date)
  d.setHours(23, 59, 59, 999)
  return d
}

/**
 * Adiciona dias a uma data.
 */
export function addDays(date: Date, days: number): Date {
  const d = new Date(date)
  d.setDate(d.getDate() + days)
  return d
}

/**
 * Retorna a diferença em dias entre duas datas (truncada).
 */
export function diffInDays(a: Date, b: Date): number {
  const start = startOfDay(a)
  const end = startOfDay(b)
  return Math.trunc((end.getTime() - start.getTime()) / (24 * 60 * 60 * 1000))
}

/**
 * Verifica se duas datas são o mesmo dia (ignorando hora).
 */
export function isSameDay(a: Date, b: Date): boolean {
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  )
}

/**
 * Retorna o primeiro dia da semana (domingo) para uma data.
 */
export function startOfWeek(date: Date): Date {
  const d = new Date(date)
  const day = d.getDay()
  d.setDate(d.getDate() - day)
  return startOfDay(d)
}

/**
 * Retorna o último dia da semana (sábado) para uma data.
 */
export function endOfWeek(date: Date): Date {
  const d = startOfWeek(date)
  d.setDate(d.getDate() + 6)
  return endOfDay(d)
}

/**
 * Gera um array de datas entre start e end (inclusive).
 */
export function dateRange(start: Date, end: Date): Date[] {
  const result: Date[] = []
  const current = new Date(start)
  const endTime = endOfDay(end).getTime()
  while (current.getTime() <= endTime) {
    result.push(new Date(current))
    current.setDate(current.getDate() + 1)
  }
  return result
}

/**
 * Retorna string YYYY-MM-DD para uma data.
 */
export function toISODateString(date: Date): string {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

/**
 * Parse de string YYYY-MM-DD para Date (meia-noite local).
 */
export function parseISODate(str: string): Date | null {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(str)) return null
  const d = new Date(str + 'T00:00:00')
  return isNaN(d.getTime()) ? null : d
}
