import { normalizeHexColor, safeHexColor, hexToRgb, rgbToHex, darken, lighten } from '../color'

describe('color utils', () => {
  describe('normalizeHexColor', () => {
    it('normalizes 6-char hex', () => {
      expect(normalizeHexColor('#ff0000')).toBe('#ff0000')
    })

    it('normalizes 3-char hex to 6-char', () => {
      expect(normalizeHexColor('#f00')).toBe('#ff0000')
    })

    it('adds # prefix if missing', () => {
      expect(normalizeHexColor('ff0000')).toBe('#ff0000')
    })

    it('returns fallback for invalid hex', () => {
      expect(normalizeHexColor('xyz')).toBe('#3b82f6')
    })

    it('returns custom fallback', () => {
      expect(normalizeHexColor('xyz', '#000000')).toBe('#000000')
    })

    it('handles uppercase hex', () => {
      expect(normalizeHexColor('#FF0000')).toBe('#FF0000')
    })

    it('handles mixed case', () => {
      expect(normalizeHexColor('#Ff00Aa')).toBe('#Ff00Aa')
    })
  })

  describe('safeHexColor', () => {
    it('returns valid 6-char hex', () => {
      expect(safeHexColor('#ff0000')).toBe('#ff0000')
    })

    it('returns fallback for 3-char hex', () => {
      expect(safeHexColor('#f00')).toBe('#3b82f6')
    })

    it('returns fallback for undefined', () => {
      expect(safeHexColor(undefined)).toBe('#3b82f6')
    })

    it('returns fallback for empty string', () => {
      expect(safeHexColor('')).toBe('#3b82f6')
    })

    it('returns custom fallback', () => {
      expect(safeHexColor(undefined, '#000000')).toBe('#000000')
    })
  })

  describe('hexToRgb', () => {
    it('converts valid hex to RGB', () => {
      expect(hexToRgb('#ff0000')).toEqual({ r: 255, g: 0, b: 0 })
    })

    it('converts black', () => {
      expect(hexToRgb('#000000')).toEqual({ r: 0, g: 0, b: 0 })
    })

    it('converts white', () => {
      expect(hexToRgb('#ffffff')).toEqual({ r: 255, g: 255, b: 255 })
    })

    it('returns null for invalid hex', () => {
      expect(hexToRgb('invalid')).toBeNull()
    })

    it('handles hex without #', () => {
      expect(hexToRgb('ff0000')).toEqual({ r: 255, g: 0, b: 0 })
    })
  })

  describe('rgbToHex', () => {
    it('converts RGB to hex', () => {
      expect(rgbToHex(255, 0, 0)).toBe('#ff0000')
    })

    it('converts black', () => {
      expect(rgbToHex(0, 0, 0)).toBe('#000000')
    })

    it('pads single digit values', () => {
      expect(rgbToHex(1, 2, 3)).toBe('#010203')
    })
  })

  describe('darken', () => {
    it('darkens a color', () => {
      const result = darken('#ff0000', 50)
      expect(result).toBe('#800000')
    })

    it('darkens to black at 100%', () => {
      const result = darken('#ff0000', 100)
      expect(result).toBe('#000000')
    })

    it('returns original for invalid hex', () => {
      expect(darken('invalid', 50)).toBe('invalid')
    })
  })

  describe('lighten', () => {
    it('lightens a color', () => {
      const result = lighten('#000000', 50)
      expect(result).toBe('#808080')
    })

    it('lightens to white at 100%', () => {
      const result = lighten('#000000', 100)
      expect(result).toBe('#ffffff')
    })

    it('returns original for invalid hex', () => {
      expect(lighten('invalid', 50)).toBe('invalid')
    })
  })
})
