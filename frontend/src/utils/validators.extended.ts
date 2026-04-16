/**
 * Validadores estendidos para formulários e API
 */

export function isRequired(value: unknown): value is NonNullable<typeof value> {
  if (value == null) return false
  if (typeof value === 'string') return value.trim() !== ''
  if (Array.isArray(value)) return value.length > 0
  return true
}

export function isEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
}

export function isMinLength(value: string, min: number): boolean {
  return value.length >= min
}

export function isMaxLength(value: string, max: number): boolean {
  return value.length <= max
}

export function isInRange(value: number, min: number, max: number): boolean {
  return value >= min && value <= max
}

export function isPositiveInteger(value: unknown): value is number {
  return Number.isInteger(value) && (value as number) > 0
}

export function isNonNegativeInteger(value: unknown): value is number {
  return Number.isInteger(value) && (value as number) >= 0
}

export function isUUID(value: string): boolean {
  return /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value)
}

export function isISODate(value: string): boolean {
  return /^\d{4}-\d{2}-\d{2}$/.test(value) && !isNaN(new Date(value + 'T00:00:00').getTime())
}

export function isHexColor(value: string): boolean {
  return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(value)
}

export function isSlug(value: string): boolean {
  return /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value)
}

export function composeValidators<T>(...fns: Array<(v: T) => boolean>): (v: T) => boolean {
  return (v) => fns.every((fn) => fn(v))
}

export function withMessage(validator: (v: unknown) => boolean, message: string) {
  return (v: unknown): true | string => (validator(v) ? true : message)
}
