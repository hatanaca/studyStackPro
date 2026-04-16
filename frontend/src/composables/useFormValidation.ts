import { ref } from 'vue'

export type ValidationRule<T = string> = (value: T) => true | string

/**
 * Valida um valor com uma lista de regras. Retorna o primeiro erro ou null.
 */
export function validate<T>(value: T, rules: ValidationRule<T>[]): string | null {
  for (const rule of rules) {
    const result = rule(value)
    if (result !== true) return result
  }
  return null
}

/**
 * Regras comuns reutilizáveis.
 */
export const rules = {
  required:
    (msg = 'Campo obrigatório.'): ValidationRule<string> =>
    (v) =>
      v != null && String(v).trim() !== '' ? true : msg,

  minLength:
    (min: number, msg?: string): ValidationRule<string> =>
    (v) =>
      String(v).length >= min ? true : (msg ?? `Mínimo ${min} caracteres.`),

  maxLength:
    (max: number, msg?: string): ValidationRule<string> =>
    (v) =>
      String(v).length <= max ? true : (msg ?? `Máximo ${max} caracteres.`),

  email:
    (msg = 'E-mail inválido.'): ValidationRule<string> =>
    (v) =>
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v)) ? true : msg,

  number:
    (msg = 'Deve ser um número.'): ValidationRule<string> =>
    (v) =>
      !isNaN(Number(v)) && v !== '' ? true : msg,

  min:
    (min: number, msg?: string): ValidationRule<number> =>
    (v) =>
      v >= min ? true : (msg ?? `Valor mínimo: ${min}.`),

  max:
    (max: number, msg?: string): ValidationRule<number> =>
    (v) =>
      v <= max ? true : (msg ?? `Valor máximo: ${max}.`),
}

/**
 * Composável para validar um campo com estado de erro reativo.
 */
export function useFieldValidation<T>(rulesList: ValidationRule<T>[]) {
  const error = ref<string | null>(null)

  function validateField(value: T): boolean {
    const result = validate(value, rulesList)
    error.value = result
    return result === null
  }

  function clearError() {
    error.value = null
  }

  return { error, validateField, clearError }
}
