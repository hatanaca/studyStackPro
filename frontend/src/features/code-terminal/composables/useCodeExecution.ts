import { ref } from 'vue'
import { useToast } from '@/composables/useToast'
import { codeExecutionApi } from '@/api/modules/code-execution.api'
import type { ProgrammingLanguage, CodeExecutionResult, TerminalEntry } from '../types/code-terminal.types'

/**
 * Composable para execução de código.
 * Despacha para client-side (JS/Lua/HTML/CSS) ou backend (PHP/SQL/Laravel/Bash).
 */
export function useCodeExecution() {
  const toast = useToast()
  const isExecuting = ref(false)
  const history = ref<TerminalEntry[]>([])

  let workerIdCounter = 0

  function generateId(): string {
    return `entry-${Date.now()}-${++workerIdCounter}`
  }

  /**
   * Executa JavaScript em Web Worker isolado.
   */
  function executeJS(code: string): Promise<CodeExecutionResult> {
    return new Promise((resolve) => {
      const startTime = performance.now()
      const worker = new Worker(
        new URL('../workers/js-executor.worker.ts', import.meta.url),
        { type: 'module' }
      )

      const id = String(++workerIdCounter)

      worker.onmessage = (e) => {
        worker.terminate()
        resolve({
          success: e.data.success,
          output: e.data.output,
          error: e.data.error,
          executionTime: e.data.executionTime,
          language: 'javascript',
        })
      }

      worker.onerror = (e) => {
        worker.terminate()
        resolve({
          success: false,
          output: '',
          error: e.message || 'Erro ao executar JavaScript',
          executionTime: Math.round(performance.now() - startTime),
          language: 'javascript',
        })
      }

      worker.postMessage({ code, id })
    })
  }

  /**
   * Executa código PHP/Laravel/SQL/Bash via backend sandbox.
   */
  async function executeBackend(code: string, language: ProgrammingLanguage): Promise<CodeExecutionResult> {
    try {
      const { data } = await codeExecutionApi.execute({ code, language })
      const result = data.data

      return {
        success: result.success,
        output: result.output,
        error: result.error,
        executionTime: result.executionTime,
        language,
      }
    } catch (err) {
      return {
        success: false,
        output: '',
        error: err instanceof Error ? err.message : 'Erro ao comunicar com o servidor',
        executionTime: 0,
        language,
      }
    }
  }

  /**
   * Executa código Lua via Web Worker (placeholder — usar Fengari quando disponível).
   */
  function executeLua(code: string): Promise<CodeExecutionResult> {
    // Por enquanto, retorna erro indicando que Lua precisa de Fengari
    return Promise.resolve({
      success: false,
      output: '',
      error: 'Execução de Lua ainda não disponível. Use JavaScript para testar.',
      executionTime: 0,
      language: 'lua',
    })
  }

  /**
   * Executa código HTML/CSS em iframe sandboxed.
   */
  function executeHTML(code: string, language: 'html' | 'css'): Promise<CodeExecutionResult> {
    // HTML/CSS são renderizados no componente, não aqui
    return Promise.resolve({
      success: true,
      output: `[${language.toUpperCase()}] Renderizado no preview`,
      error: null,
      executionTime: 0,
      language,
    })
  }

  /**
   * Executa código na linguagem apropriada.
   */
  async function execute(code: string, language: ProgrammingLanguage): Promise<TerminalEntry> {
    isExecuting.value = true

    let result: CodeExecutionResult

    try {
      switch (language) {
        case 'javascript':
          result = await executeJS(code)
          break
        case 'lua':
          result = await executeLua(code)
          break
        case 'html':
        case 'css':
          result = await executeHTML(code, language)
          break
        case 'php':
        case 'laravel':
        case 'sql':
        case 'bash':
          result = await executeBackend(code, language)
          break
        default:
          result = {
            success: false,
            output: '',
            error: `Linguagem '${language}' não suportada`,
            executionTime: 0,
            language,
          }
      }
    } catch (err) {
      result = {
        success: false,
        output: '',
        error: err instanceof Error ? err.message : 'Erro desconhecido',
        executionTime: 0,
        language,
      }
    }

    const entry: TerminalEntry = {
      id: generateId(),
      code,
      language,
      result,
      timestamp: Date.now(),
    }

    history.value.unshift(entry)
    isExecuting.value = false

    if (!result.success && result.error) {
      toast.error(result.error)
    }

    return entry
  }

  function clearHistory() {
    history.value = []
  }

  return {
    isExecuting,
    history,
    execute,
    clearHistory,
  }
}
