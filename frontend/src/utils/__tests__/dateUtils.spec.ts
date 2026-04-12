import {
  formatDateTime,
  formatDate,
  toDateInputValue,
  startOfDay,
  endOfDay,
  addDays,
  diffInDays,
  isSameDay,
  toISODateString,
  parseISODate,
} from '../dateUtils'

describe('dateUtils', () => {
  describe('formatDateTime', () => {
    it('formata ISO para pt-BR', () => {
      const result = formatDateTime('2025-03-05T14:30:00')
      expect(result).toMatch(/\d{2}\/\d{2}\/\d{4}/)
      expect(result).toMatch(/\d{2}:\d{2}/)
    })
    it('retorna — para null/undefined', () => {
      expect(formatDateTime(null)).toBe('—')
      expect(formatDateTime(undefined)).toBe('—')
    })
  })

  describe('formatDate', () => {
    it('formata apenas data', () => {
      const result = formatDate('2025-03-05')
      expect(result).toMatch(/\d{2}\/\d{2}\/\d{4}/)
    })
  })

  describe('toDateInputValue', () => {
    it('retorna YYYY-MM-DD', () => {
      expect(toDateInputValue('2025-03-05T00:00:00')).toBe('2025-03-05')
    })
    it('retorna vazio para null', () => {
      expect(toDateInputValue(null)).toBe('')
    })
  })

  describe('startOfDay', () => {
    it('zera horas', () => {
      const d = new Date('2025-03-05T15:30:00')
      const start = startOfDay(d)
      expect(start.getHours()).toBe(0)
      expect(start.getMinutes()).toBe(0)
      expect(start.getSeconds()).toBe(0)
    })
  })

  describe('endOfDay', () => {
    it('define 23:59:59.999', () => {
      const d = new Date('2025-03-05T10:00:00')
      const end = endOfDay(d)
      expect(end.getHours()).toBe(23)
      expect(end.getMinutes()).toBe(59)
      expect(end.getSeconds()).toBe(59)
    })
  })

  describe('addDays', () => {
    it('adiciona dias corretamente', () => {
      // Usar construtor local (ano, mês 0-indexed, dia) para evitar efeito de timezone em new Date('YYYY-MM-DD')
      const d = new Date(2025, 2, 5)
      const next = addDays(d, 3)
      expect(next.getDate()).toBe(8)
      expect(next.getMonth()).toBe(2)
      expect(next.getFullYear()).toBe(2025)
    })
  })

  describe('diffInDays', () => {
    it('calcula diferença positiva', () => {
      const a = new Date(2025, 2, 5)
      const b = new Date(2025, 2, 10)
      expect(diffInDays(a, b)).toBe(5)
    })
    it('calcula diferença negativa', () => {
      const a = new Date(2025, 2, 10)
      const b = new Date(2025, 2, 5)
      expect(diffInDays(a, b)).toBe(-5)
    })
  })

  describe('isSameDay', () => {
    it('retorna true para mesmo dia', () => {
      const a = new Date('2025-03-05T10:00:00')
      const b = new Date('2025-03-05T22:00:00')
      expect(isSameDay(a, b)).toBe(true)
    })
    it('retorna false para dias diferentes', () => {
      const a = new Date(2025, 2, 5)
      const b = new Date(2025, 2, 6)
      expect(isSameDay(a, b)).toBe(false)
    })
  })

  describe('toISODateString', () => {
    it('retorna YYYY-MM-DD', () => {
      const d = new Date('2025-03-05T12:00:00')
      expect(toISODateString(d)).toBe('2025-03-05')
    })
  })

  describe('parseISODate', () => {
    it('parse string válida', () => {
      const d = parseISODate('2025-03-05')
      expect(d).not.toBeNull()
      expect(d!.getFullYear()).toBe(2025)
      expect(d!.getMonth()).toBe(2)
      expect(d!.getDate()).toBe(5)
    })
    it('retorna null para formato inválido', () => {
      expect(parseISODate('invalid')).toBeNull()
      expect(parseISODate('2025-13-01')).toBeNull()
    })
  })
})
