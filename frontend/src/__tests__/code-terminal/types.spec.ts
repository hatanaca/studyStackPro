import { describe, it, expect } from 'vitest'
import { LANGUAGE_CONFIGS } from '@/features/code-terminal/types/code-terminal.types'
import type { ProgrammingLanguage } from '@/features/code-terminal/types/code-terminal.types'

describe('code-terminal types', () => {
  it('has all 8 supported languages', () => {
    const languages = Object.keys(LANGUAGE_CONFIGS) as ProgrammingLanguage[]
    expect(languages).toHaveLength(8)
    expect(languages).toContain('javascript')
    expect(languages).toContain('php')
    expect(languages).toContain('lua')
    expect(languages).toContain('html')
    expect(languages).toContain('css')
    expect(languages).toContain('sql')
    expect(languages).toContain('laravel')
    expect(languages).toContain('bash')
  })

  it('client languages are javascript, lua, html, css', () => {
    const clientLangs = Object.values(LANGUAGE_CONFIGS)
      .filter((c) => c.executor === 'client')
      .map((c) => c.name)

    expect(clientLangs).toContain('javascript')
    expect(clientLangs).toContain('lua')
    expect(clientLangs).toContain('html')
    expect(clientLangs).toContain('css')
    expect(clientLangs).not.toContain('php')
    expect(clientLangs).not.toContain('sql')
  })

  it('backend languages are php, sql, laravel, bash', () => {
    const backendLangs = Object.values(LANGUAGE_CONFIGS)
      .filter((c) => c.executor === 'backend')
      .map((c) => c.name)

    expect(backendLangs).toContain('php')
    expect(backendLangs).toContain('sql')
    expect(backendLangs).toContain('laravel')
    expect(backendLangs).toContain('bash')
  })

  it('each language has a cmLanguage for CodeMirror', () => {
    Object.values(LANGUAGE_CONFIGS).forEach((config) => {
      expect(config.cmLanguage).toBeTruthy()
      expect(typeof config.cmLanguage).toBe('string')
    })
  })
})
