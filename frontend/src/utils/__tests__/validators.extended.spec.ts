import { describe, it, expect } from 'vitest'
import {
  isRequired,
  isEmail,
  isMinLength,
  isMaxLength,
  isInRange,
  isPositiveInteger,
  isNonNegativeInteger,
  isUUID,
  isISODate,
  isHexColor,
  isSlug,
} from '../validators.extended'

describe('validators.extended', () => {
  describe('isRequired', () => {
    it('rejeita null e undefined', () => {
      expect(isRequired(null)).toBe(false)
      expect(isRequired(undefined)).toBe(false)
    })
    it('rejeita string vazia e só espaços', () => {
      expect(isRequired('')).toBe(false)
      expect(isRequired('  ')).toBe(false)
    })
    it('aceita string não vazia', () => {
      expect(isRequired('a')).toBe(true)
    })
    it('aceita array não vazio', () => {
      expect(isRequired([1])).toBe(true)
      expect(isRequired([])).toBe(false)
    })
  })

  describe('isEmail', () => {
    it('aceita emails válidos', () => {
      expect(isEmail('a@b.co')).toBe(true)
      expect(isEmail('user@example.com')).toBe(true)
    })
    it('rejeita inválidos', () => {
      expect(isEmail('')).toBe(false)
      expect(isEmail('no-at')).toBe(false)
      expect(isEmail('@nodomain.com')).toBe(false)
    })
  })

  describe('isMinLength', () => {
    it('aceita string com tamanho >= min', () => {
      expect(isMinLength('abc', 3)).toBe(true)
      expect(isMinLength('ab', 3)).toBe(false)
    })
  })

  describe('isMaxLength', () => {
    it('aceita string com tamanho <= max', () => {
      expect(isMaxLength('ab', 3)).toBe(true)
      expect(isMaxLength('abcd', 3)).toBe(false)
    })
  })

  describe('isInRange', () => {
    it('aceita número dentro do intervalo', () => {
      expect(isInRange(5, 1, 10)).toBe(true)
      expect(isInRange(1, 1, 10)).toBe(true)
      expect(isInRange(10, 1, 10)).toBe(true)
      expect(isInRange(0, 1, 10)).toBe(false)
      expect(isInRange(11, 1, 10)).toBe(false)
    })
  })

  describe('isPositiveInteger', () => {
    it('aceita inteiros > 0', () => {
      expect(isPositiveInteger(1)).toBe(true)
      expect(isPositiveInteger(0)).toBe(false)
      expect(isPositiveInteger(-1)).toBe(false)
      expect(isPositiveInteger(1.5)).toBe(false)
    })
  })

  describe('isNonNegativeInteger', () => {
    it('aceita inteiros >= 0', () => {
      expect(isNonNegativeInteger(0)).toBe(true)
      expect(isNonNegativeInteger(1)).toBe(true)
      expect(isNonNegativeInteger(-1)).toBe(false)
    })
  })

  describe('isUUID', () => {
    it('aceita UUID v4 válido', () => {
      expect(isUUID('550e8400-e29b-41d4-a716-446655440000')).toBe(true)
    })
    it('rejeita string inválida', () => {
      expect(isUUID('not-a-uuid')).toBe(false)
    })
  })

  describe('isISODate', () => {
    it('aceita YYYY-MM-DD válido', () => {
      expect(isISODate('2025-03-05')).toBe(true)
    })
    it('rejeita data inválida', () => {
      expect(isISODate('2025-13-01')).toBe(false)
      expect(isISODate('invalid')).toBe(false)
    })
  })

  describe('isHexColor', () => {
    it('aceita hex 3 e 6 caracteres', () => {
      expect(isHexColor('#fff')).toBe(true)
      expect(isHexColor('#ffffff')).toBe(true)
    })
    it('rejeita inválido', () => {
      expect(isHexColor('fff')).toBe(false)
      expect(isHexColor('#ggg')).toBe(false)
    })
  })

  describe('isSlug', () => {
    it('aceita slug válido', () => {
      expect(isSlug('hello-world')).toBe(true)
      expect(isSlug('a')).toBe(true)
    })
    it('rejeita inválido', () => {
      expect(isSlug('Hello')).toBe(false)
      expect(isSlug('hello_world')).toBe(false)
    })
  })
})
