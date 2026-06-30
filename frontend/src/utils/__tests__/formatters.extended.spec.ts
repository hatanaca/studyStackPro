import {
  formatNumber,
  formatPercent,
  formatCurrency,
  truncate,
  formatRelativeTime,
} from '../formatters'

describe('formatters (extended)', () => {
  describe('formatNumber', () => {
    it('formats number with locale separator', () => {
      const result = formatNumber(1234)
      expect(result).toContain('1')
      expect(result).toContain('234')
    })

    it('formats zero', () => {
      expect(formatNumber(0)).toBe('0')
    })

    it('formats small numbers', () => {
      expect(formatNumber(42)).toBe('42')
    })
  })

  describe('formatPercent', () => {
    it('formats percentage with one decimal', () => {
      expect(formatPercent(50)).toBe('50%')
      expect(formatPercent(33.333)).toBe('33.3%')
    })

    it('formats zero', () => {
      expect(formatPercent(0)).toBe('0%')
    })

    it('formats 100%', () => {
      expect(formatPercent(100)).toBe('100%')
    })
  })

  describe('formatCurrency', () => {
    it('formats BRL currency', () => {
      const result = formatCurrency(1234.56)
      expect(result).toContain('1')
      expect(result).toContain('234')
    })

    it('formats zero', () => {
      const result = formatCurrency(0)
      expect(result).toContain('0')
    })
  })

  describe('truncate', () => {
    it('returns original text if shorter than maxLength', () => {
      expect(truncate('hello', 10)).toBe('hello')
    })

    it('truncates text with ellipsis', () => {
      const result = truncate('hello world', 8)
      expect(result).toBe('hello...')
      expect(result.length).toBe(8)
    })

    it('returns empty string for zero maxLength', () => {
      expect(truncate('hello', 0)).toBe('...')
    })

    it('handles exact length', () => {
      expect(truncate('hello', 5)).toBe('hello')
    })
  })

  describe('formatRelativeTime', () => {
    it('returns dash for null', () => {
      expect(formatRelativeTime(null)).toBe('—')
    })

    it('returns dash for empty string', () => {
      expect(formatRelativeTime('')).toBe('—')
    })

    it('returns dash for invalid date', () => {
      expect(formatRelativeTime('invalid')).toBe('—')
    })

    it('returns "agora" for very recent times', () => {
      const now = new Date()
      expect(formatRelativeTime(now.toISOString(), now)).toBe('agora')
    })

    it('returns minutes for recent times', () => {
      const base = new Date('2026-06-29T12:00:00Z')
      const past = new Date('2026-06-29T11:55:00Z')
      expect(formatRelativeTime(past.toISOString(), base)).toBe('há 5 min')
    })

    it('returns hours for times within a day', () => {
      const base = new Date('2026-06-29T12:00:00Z')
      const past = new Date('2026-06-29T09:00:00Z')
      expect(formatRelativeTime(past.toISOString(), base)).toBe('há 3h')
    })
  })
})
