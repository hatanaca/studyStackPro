import ptBR from './pt-BR'
import en from './en'

export type Locale = 'pt-BR' | 'en'

export const locales: Record<Locale, typeof ptBR> = {
  'pt-BR': ptBR,
  en,
}

export function getLocale(storageKey = 'studytrack.locale'): Locale {
  try {
    const stored = localStorage.getItem(storageKey)
    if (stored && (stored === 'pt-BR' || stored === 'en')) return stored
  } catch {
    // ignore
  }
  const browserLang = navigator.language
  return browserLang.startsWith('pt') ? 'pt-BR' : 'en'
}

export function setLocale(locale: Locale, storageKey = 'studytrack.locale'): void {
  try {
    localStorage.setItem(storageKey, locale)
  } catch {
    // ignore
  }
}

export function t(locale: Locale, path: string): string {
  const keys = path.split('.')
  let result: unknown = locales[locale]
  for (const key of keys) {
    if (result === null || result === undefined || typeof result !== 'object') return path
    result = (result as Record<string, unknown>)[key]
    if (result === undefined) return path
  }
  return typeof result === 'string' ? result : path
}
