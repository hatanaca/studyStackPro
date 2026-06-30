import { isUUID } from '../validators.extended'

describe('validators.extended (comprehensive)', () => {
  describe('isUUID', () => {
    it('accepts valid UUID v4', () => {
      expect(isUUID('550e8400-e29b-41d4-a716-446655440000')).toBe(true)
    })

    it('accepts another valid UUID v4', () => {
      expect(isUUID('6ba7b810-9dad-11d1-80b4-00c04fd430c8')).toBe(true)
    })

    it('accepts UUID with uppercase', () => {
      expect(isUUID('550E8400-E29B-41D4-A716-446655440000')).toBe(true)
    })

    it('accepts UUID v1 (regex allows versions 1-5)', () => {
      expect(isUUID('f47ac10b-58cc-1372-a567-0e02b2c3d479')).toBe(true)
    })

    it('rejects empty string', () => {
      expect(isUUID('')).toBe(false)
    })

    it('rejects random string', () => {
      expect(isUUID('not-a-uuid')).toBe(false)
    })

    it('rejects UUID with invalid characters', () => {
      expect(isUUID('550e8400-e29b-41d4-a716-44665544000g')).toBe(false)
    })

    it('rejects too short string', () => {
      expect(isUUID('550e8400-e29b-41d4')).toBe(false)
    })

    it('rejects too long string', () => {
      expect(isUUID('550e8400-e29b-41d4-a716-446655440000-extra')).toBe(false)
    })

    it('rejects UUID without dashes', () => {
      expect(isUUID('550e8400e29b41d4a716446655440000')).toBe(false)
    })
  })
})
