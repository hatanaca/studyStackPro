import { describe, it, expect } from 'vitest'
import { measureText, useTextLayout } from '../useTextMeasure'

describe('useTextMeasure', () => {
  it('measureText returns a result object', () => {
    const result = measureText('Hello World', '14px Arial', 200, 20)
    expect(typeof result.height).toBe('number')
    expect(typeof result.lineCount).toBe('number')
    expect(result.height).toBeGreaterThanOrEqual(0)
    expect(result.lineCount).toBeGreaterThanOrEqual(0)
  })

  it('measureText returns zero for empty text', () => {
    const result = measureText('', '14px Arial', 200, 20)
    expect(result.height).toBe(0)
    expect(result.lineCount).toBe(0)
  })

  it('useTextLayout returns computed refs', () => {
    const layout = useTextLayout('Hello', '14px Arial', 200, 20)
    expect(layout.height).toBeDefined()
    expect(layout.lineCount).toBeDefined()
    expect(typeof layout.height.value).toBe('number')
    expect(typeof layout.lineCount.value).toBe('number')
  })
})
