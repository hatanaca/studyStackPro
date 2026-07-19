/** Linguagens suportadas pelo code terminal */
export type ProgrammingLanguage =
  | 'javascript'
  | 'php'
  | 'lua'
  | 'html'
  | 'css'
  | 'sql'
  | 'laravel'
  | 'bash'

/** Configuração de uma linguagem */
export interface LanguageConfig {
  name: ProgrammingLanguage
  label: string
  icon: string
  executor: 'client' | 'backend'
  cmLanguage: string
}

/** Request de execução de código */
export interface CodeExecutionRequest {
  code: string
  language: ProgrammingLanguage
}

/** Resultado da execução */
export interface CodeExecutionResult {
  success: boolean
  output: string
  error: string | null
  executionTime: number
  language: ProgrammingLanguage
}

/** Entrada no histórico do terminal */
export interface TerminalEntry {
  id: string
  code: string
  language: ProgrammingLanguage
  result: CodeExecutionResult
  timestamp: number
}

/** Mapa de configurações das linguagens */
export const LANGUAGE_CONFIGS: Record<ProgrammingLanguage, LanguageConfig> = {
  javascript: {
    name: 'javascript',
    label: 'JavaScript',
    icon: 'pi pi-code',
    executor: 'client',
    cmLanguage: 'javascript',
  },
  php: {
    name: 'php',
    label: 'PHP',
    icon: 'pi pi-code',
    executor: 'backend',
    cmLanguage: 'php',
  },
  lua: {
    name: 'lua',
    label: 'Lua',
    icon: 'pi pi-code',
    executor: 'client',
    cmLanguage: 'lua',
  },
  html: {
    name: 'html',
    label: 'HTML',
    icon: 'pi pi-code',
    executor: 'client',
    cmLanguage: 'html',
  },
  css: {
    name: 'css',
    label: 'CSS',
    icon: 'pi pi-code',
    executor: 'client',
    cmLanguage: 'css',
  },
  sql: {
    name: 'sql',
    label: 'SQL',
    icon: 'pi pi-code',
    executor: 'backend',
    cmLanguage: 'sql',
  },
  laravel: {
    name: 'laravel',
    label: 'Laravel',
    icon: 'pi pi-code',
    executor: 'backend',
    cmLanguage: 'php',
  },
  bash: {
    name: 'bash',
    label: 'Bash',
    icon: 'pi pi-code',
    executor: 'backend',
    cmLanguage: 'bash',
  },
}
