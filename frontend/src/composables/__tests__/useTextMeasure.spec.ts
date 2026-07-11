import { useTextMeasure } from '../useTextMeasure'

describe('useTextMeasure', () => {
  it('returns expected API', () => {
    const measure = useTextMeasure()
    expect(typeof measure.measureText).toBe('function')
    expect(typeof measure.getTextWidth).toBe('function')
  })

  it('measureText returns a number', () => {
    const measure = useTextMeasure()
    const width = measure.measureText('Hello World')
    expect(typeof width).toBe('number')
    expect(width).toBeGreaterThanOrEqual(0)
  })

  it('getTextWidth returns a number', () => {
    const measure = useTextMeasure()
    const width = measure.getTextWidth('Test', '14px', 'Arial')
    expect(typeof width).toBe('number')
    expect(width).toBeGreaterThanOrEqual(0)
  })
})
