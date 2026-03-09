import { describe, it, expect } from 'vitest'
import {
  formatDuration,
  formatHours,
  formatDate,
  formatShortDate,
  formatDateTime,
  truncate
} from '../formatters'

describe('formatters', () => {
  describe('formatDuration', () => {
    it('formata minutos < 60 como "Xmin"', () => {
      expect(formatDuration(30)).toBe('30min')
      expect(formatDuration(0)).toBe('0min')
    })
    it('formata horas inteiras como "Xh"', () => {
      expect(formatDuration(60)).toBe('1h')
      expect(formatDuration(120)).toBe('2h')
    })
    it('formata horas com minutos restantes', () => {
      expect(formatDuration(90)).toBe('1h 30min')
      expect(formatDuration(125)).toBe('2h 5min')
    })
  })

  describe('formatHours', () => {
    it('converte minutos em horas decimais', () => {
      expect(formatHours(60)).toBe('1.0h')
      expect(formatHours(90)).toBe('1.5h')
      expect(formatHours(30)).toBe('0.5h')
    })
  })

  describe('formatDate', () => {
    it('formata string ISO no padrão dd/mm/aaaa', () => {
      const result = formatDate('2025-02-26')
      expect(result).toMatch(/^\d{2}\/\d{2}\/\d{4}$/)
      expect(result).toContain('2025')
    })
  })

  describe('formatShortDate', () => {
    it('formata como dd/mm (sem ano)', () => {
      const result = formatShortDate('2025-02-26')
      expect(result).toMatch(/\d{2}\/\d{2}/)
      expect(result).not.toContain('2025')
    })
  })

  describe('formatDateTime', () => {
    it('formata com data e hora', () => {
      const result = formatDateTime('2025-02-26T14:30:00Z')
      expect(result).toMatch(/\d{2}\/\d{2}\/\d{4}/)
      expect(result).toMatch(/\d{2}:\d{2}/)
    })
    it('retorna — para null/undefined/vazio', () => {
      expect(formatDateTime(null)).toBe('—')
      expect(formatDateTime(undefined)).toBe('—')
      expect(formatDateTime('')).toBe('—')
    })
  })

  describe('formatDate e formatDateTime com valor inválido', () => {
    it('retorna — para string de data inválida', () => {
      expect(formatDate('invalid')).toBe('—')
      expect(formatDateTime('invalid')).toBe('—')
    })
  })

  describe('truncate', () => {
    it('retorna o texto inteiro quando menor ou igual a maxLength', () => {
      expect(truncate('ab', 5)).toBe('ab')
      expect(truncate('hello', 5)).toBe('hello')
    })
    it('trunca com reticências quando maior que maxLength', () => {
      expect(truncate('hello world', 8)).toBe('hello...')
    })
    it('não usa índice negativo quando maxLength é pequeno', () => {
      expect(truncate('hello', 2)).toBe('...')
      expect(truncate('hello', 3)).toBe('...')
    })
  })
})
