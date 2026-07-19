import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useCodeTerminalStore } from '@/features/code-terminal/store/code-terminal.store'
import type { TerminalEntry } from '@/features/code-terminal/types/code-terminal.types'

describe('code-terminal store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('has default state', () => {
    const store = useCodeTerminalStore()
    expect(store.code).toBe('console.log("Hello, World!")')
    expect(store.language).toBe('javascript')
    expect(store.history).toEqual([])
    expect(store.isExecuting).toBe(false)
    expect(store.darkMode).toBe(true)
  })

  it('setCode updates code', () => {
    const store = useCodeTerminalStore()
    store.setCode('const x = 42')
    expect(store.code).toBe('const x = 42')
  })

  it('setLanguage updates language', () => {
    const store = useCodeTerminalStore()
    store.setLanguage('python' as any)
    expect(store.language).toBe('python')
  })

  it('addEntry prepends to history', () => {
    const store = useCodeTerminalStore()
    const entry1: TerminalEntry = {
      id: '1',
      code: 'print("hello")',
      language: 'javascript',
      result: {
        success: true,
        output: 'hello',
        error: null,
        executionTime: 10,
        language: 'javascript',
      },
      timestamp: 1000,
    }
    const entry2: TerminalEntry = {
      id: '2',
      code: 'print("world")',
      language: 'javascript',
      result: {
        success: true,
        output: 'world',
        error: null,
        executionTime: 5,
        language: 'javascript',
      },
      timestamp: 2000,
    }

    store.addEntry(entry1)
    store.addEntry(entry2)

    expect(store.history).toHaveLength(2)
    expect(store.history[0].id).toBe('2')
    expect(store.history[1].id).toBe('1')
  })

  it('clearHistory empties history', () => {
    const store = useCodeTerminalStore()
    store.addEntry({
      id: '1',
      code: 'test',
      language: 'javascript',
      result: { success: true, output: '', error: null, executionTime: 0, language: 'javascript' },
      timestamp: 1000,
    })
    expect(store.history).toHaveLength(1)

    store.clearHistory()
    expect(store.history).toEqual([])
  })

  it('toggleDarkMode toggles state', () => {
    const store = useCodeTerminalStore()
    expect(store.darkMode).toBe(true)

    store.toggleDarkMode()
    expect(store.darkMode).toBe(false)

    store.toggleDarkMode()
    expect(store.darkMode).toBe(true)
  })

  it('setExecuting updates state', () => {
    const store = useCodeTerminalStore()
    expect(store.isExecuting).toBe(false)

    store.setExecuting(true)
    expect(store.isExecuting).toBe(true)

    store.setExecuting(false)
    expect(store.isExecuting).toBe(false)
  })
})
