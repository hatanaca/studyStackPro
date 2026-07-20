import type { ProgrammingLanguage } from '../types/code-terminal.types'

/**
 * Detecta a linguagem de programação baseada no conteúdo do código.
 * Usa heurísticas simples baseadas em padrões sintáticos.
 */
export function useLanguageDetection() {
  function detect(code: string): ProgrammingLanguage {
    const trimmed = code.trim()

    if (!trimmed) return 'javascript'

    // PHP / Laravel
    if (/^<\?php/.test(trimmed) || /\$[a-zA-Z_]/.test(trimmed) || /->/.test(trimmed)) {
      if (/use\s+App\\/.test(trimmed) || /Illuminate\\/.test(trimmed) || /Route::/.test(trimmed)) {
        return 'laravel'
      }
      return 'php'
    }

    // SQL
    if (
      /^\s*(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|FROM|WHERE|JOIN|INTO|VALUES)\b/i.test(
        trimmed
      )
    ) {
      return 'sql'
    }

    // HTML
    if (/^<!DOCTYPE|^<html|^<head|^<body|^<div/i.test(trimmed)) {
      return 'html'
    }

    // CSS
    if (
      /\{[\s\S]*:\s*[^;]+;/.test(trimmed) ||
      /^\.[a-zA-Z_-]+\s*\{/.test(trimmed) ||
      /^[a-zA-Z-]+\s*\{/.test(trimmed)
    ) {
      return 'css'
    }

    // Lua
    if (
      /\bfunction\s*\w*\s*\(/.test(trimmed) &&
      /\bend\b/.test(trimmed) &&
      /\blocal\b/.test(trimmed)
    ) {
      return 'lua'
    }
    if (/^\s*(local\s+\w+\s*=|if\s+.+\s+then|for\s+.+\s+do|while\s+.+\s+do)/.test(trimmed)) {
      return 'lua'
    }

    // Bash
    if (/^#!\s*\/bin\/(ba)?sh/.test(trimmed)) {
      return 'bash'
    }
    if (/^\s*(echo|if\s+\[|fi|for\s+.+\s+in|done|case|esac|function\s+\w+)\b/.test(trimmed)) {
      return 'bash'
    }

    // JavaScript (default)
    return 'javascript'
  }

  return { detect }
}
