const MAX_EXECUTION_MS = 5_000
const MAX_CODE_LENGTH = 10_000
const logs: string[] = []
let timedOut = false

// Globais perigosos que são sombreados no sandbox (passados como undefined)
const DANGEROUS_GLOBALS = [
  'self','globalThis','global','window',
  'fetch','XMLHttpRequest','importScripts','Worker','SharedWorker',
  'navigator','location','history',
  'localStorage','sessionStorage','indexedDB','openDatabase',
  'postMessage','close','onmessage','onerror',
  'Blob','URL','createImageBitmap','WebAssembly',
  'Buffer','atob','btoa','crypto','performance',
] as const

const fakeConsole = {
  log: (...args: unknown[]) => { logs.push(args.map(stringify).join(' ')) },
  error: (...args: unknown[]) => { logs.push('[ERROR] ' + args.map(stringify).join(' ')) },
  warn: (...args: unknown[]) => { logs.push('[WARN] ' + args.map(stringify).join(' ')) },
  info: (...args: unknown[]) => { logs.push('[INFO] ' + args.map(stringify).join(' ')) },
  debug: (...args: unknown[]) => { logs.push('[DEBUG] ' + args.map(stringify).join(' ')) },
}

function stringify(val: unknown): string {
  if (val === undefined) return 'undefined'
  if (val === null) return 'null'
  if (typeof val === 'string') return val
  if (typeof val === 'function') return `[Function: ${val.name || 'anonymous'}]`
  try { return JSON.stringify(val, null, 2) } catch { return String(val) }
}

function preventSandboxEscape(code: string): string {
  // Bloqueia padrões conhecidos de escape de sandbox
  const patterns: [RegExp, string][] = [
    [/\.constructor\.constructor\s*[\(\[.]/g, './*blocked*/('],
    [/\["constructor"\]\s*\["constructor"\]/g, '["blocked"]'],
    [/(?:__lookupGetter__|__lookupSetter__|__defineGetter__|__defineSetter__)\s*\(/g, '/*blocked*/('],
    [/Reflect\s*\.\s*(getPrototypeOf|setPrototypeOf|construct|apply|ownKeys)\s*\(/g, '/*blocked*/('],
    [/Proxy\s*\(/g, '/*blocked*/('],
    [/\[\s*"__proto__"\s*\]/g, '["blocked"]'],
    [/\.__proto__\s*[=:]/g, '/*blocked*/='],
    [/import\s*\(/g, '/*blocked*/('],
  ]
  let sanitized = code
  for (const [pattern, replacement] of patterns) {
    sanitized = sanitized.replace(pattern, replacement)
  }
  return sanitized
}

function createSafeTimeout(fn: Function, _ms: number): number {
  if (timedOut) return 0
  return setTimeout(() => { if (!timedOut) fn() }, _ms) as unknown as number
}
function createSafeInterval(fn: Function, _ms: number): number {
  if (timedOut) return 0
  return setInterval(() => { if (!timedOut) fn() }, _ms) as unknown as number
}

self.onmessage = function (e: MessageEvent) {
  const { code, id } = e.data
  logs.length = 0
  timedOut = false
  if (typeof code !== 'string' || code.length > MAX_CODE_LENGTH) {
    self.postMessage({ id, success: false, output: '', error: `Código excede o limite de ${MAX_CODE_LENGTH} caracteres.`, executionTime: 0 })
    return
  }
  const startTime = performance.now()
  const timeoutId = setTimeout(() => { timedOut = true }, MAX_EXECUTION_MS)
  try {
    const safeCode = preventSandboxEscape(code)
    // Sandbox: o corpo da função declara via parâmetros TODOS os globais perigosos como undefined.
    // O código do usuário recebe apenas os parâmetros explicitamente passados.
    const sandboxedFn = new Function(
      // Whitelist de APIs seguras
      'console','Math','Date','JSON','parseInt','parseFloat','isNaN','isFinite',
      'encodeURIComponent','decodeURIComponent','encodeURI','decodeURI',
      'String','Number','Boolean','Array','Object','RegExp',
      'Error','TypeError','RangeError','SyntaxError',
      'setTimeout','clearTimeout','setInterval','clearInterval',
      // Shadow de globais perigosos — dentro da função valem undefined
      ...DANGEROUS_GLOBALS,
      // Corpo da função em strict mode
      `"use strict"; ${safeCode}`
    )
    const result = sandboxedFn(
      // Whitelist de APIs seguras
      fakeConsole, Math, Date, JSON, parseInt, parseFloat, isNaN, isFinite,
      encodeURIComponent, decodeURIComponent, encodeURI, decodeURI,
      String, Number, Boolean, Array, Object, RegExp,
      Error, TypeError, RangeError, SyntaxError,
      createSafeTimeout, clearTimeout, createSafeInterval, clearInterval,
      // Todos os globais perigosos recebem undefined
      ...DANGEROUS_GLOBALS.map(() => undefined),
    )
    clearTimeout(timeoutId)
    if (timedOut) {
      self.postMessage({ id, success: false, output: logs.join('\n'), error: `Execução excedeu o limite de ${MAX_EXECUTION_MS / 1000}s.`, executionTime: MAX_EXECUTION_MS })
      return
    }
    const executionTime = Math.round(performance.now() - startTime)
    if (result !== undefined && logs.length === 0) { logs.push(stringify(result)) }
    self.postMessage({ id, success: true, output: logs.join('\n'), error: null, executionTime })
  } catch (err) {
    clearTimeout(timeoutId)
    const executionTime = Math.round(performance.now() - startTime)
    const errorMessage = err instanceof Error ? err.message : String(err)
    self.postMessage({ id, success: false, output: logs.join('\n'), error: errorMessage, executionTime })
  }
}
