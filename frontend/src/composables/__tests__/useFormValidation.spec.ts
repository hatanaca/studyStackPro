import { describe, it, expect } from 'vitest'
import { validate, rules, useFieldValidation } from '../useFormValidation'

describe('validate', () => {
  it('retorna null quando todas as regras passam', () => {
    const result = validate('hello', [rules.required(), rules.minLength(3)])
    expect(result).toBeNull()
  })

  it('retorna a mensagem da primeira regra que falha', () => {
    const result = validate('', [rules.required(), rules.minLength(3)])
    expect(result).toBe('Campo obrigatório.')
  })

  it('retorna erro de minLength quando aplicável', () => {
    const result = validate('ab', [rules.required(), rules.minLength(5)])
    expect(result).toMatch(/Mínimo 5 caracteres/)
  })

  it('retorna erro de email quando inválido', () => {
    const result = validate('invalid', [rules.required(), rules.email()])
    expect(result).toBe('E-mail inválido.')
  })

  it('aceita email válido', () => {
    const result = validate('user@example.com', [rules.required(), rules.email()])
    expect(result).toBeNull()
  })
})

describe('rules', () => {
  it('required rejeita string vazia', () => {
    expect(rules.required()('')).not.toBe(true)
    expect(rules.required()('  ')).not.toBe(true)
  })

  it('required aceita string não vazia', () => {
    expect(rules.required()('a')).toBe(true)
  })

  it('min aceita número >= min', () => {
    expect(rules.min(10)(10)).toBe(true)
    expect(rules.min(10)(11)).toBe(true)
    expect(rules.min(10)(9)).not.toBe(true)
  })

  it('max aceita número <= max', () => {
    expect(rules.max(10)(10)).toBe(true)
    expect(rules.max(10)(9)).toBe(true)
    expect(rules.max(10)(11)).not.toBe(true)
  })
})

describe('useFieldValidation', () => {
  it('validateField define error quando falha', () => {
    const { error, validateField } = useFieldValidation([rules.required()])
    const ok = validateField('')
    expect(ok).toBe(false)
    expect(error.value).toBe('Campo obrigatório.')
  })

  it('validateField limpa error quando passa', () => {
    const { error, validateField } = useFieldValidation([rules.required()])
    validateField('')
    const ok = validateField('hello')
    expect(ok).toBe(true)
    expect(error.value).toBeNull()
  })

  it('clearError zera o erro', () => {
    const { error, validateField, clearError } = useFieldValidation([rules.required()])
    validateField('')
    expect(error.value).not.toBeNull()
    clearError()
    expect(error.value).toBeNull()
  })
})
