import { useFocusTrap } from '../useFocusTrap'

describe('useFocusTrap', () => {
  it('returns expected API', () => {
    const trap = useFocusTrap()
    expect(typeof trap.activate).toBe('function')
    expect(typeof trap.deactivate).toBe('function')
  })

  it('activate and deactivate are callable without error', () => {
    const trap = useFocusTrap()
    expect(() => trap.activate()).not.toThrow()
    expect(() => trap.deactivate()).not.toThrow()
  })
})
