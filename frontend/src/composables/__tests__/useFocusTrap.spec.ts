import { describe, it, expect } from 'vitest'
import { ref } from 'vue'
import { useFocusTrap } from '../useFocusTrap'

describe('useFocusTrap', () => {
  it('accepts container and active refs', () => {
    const container = ref<HTMLElement | null>(null)
    const active = ref(false)
    // Should not throw when called with proper refs
    expect(() => useFocusTrap(container, active)).not.toThrow()
  })
})
