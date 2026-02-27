/**
 * Funções puras de validação
 */

export function isValidHexColor(value: string): boolean {
  return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(value)
}

export function isValidDuration(minutes: number): boolean {
  return Number.isInteger(minutes) && minutes >= 0 && minutes <= 1440
}

export function isValidMood(value: number): boolean {
  return Number.isInteger(value) && value >= 1 && value <= 5
}
