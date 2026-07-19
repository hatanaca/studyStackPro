const blockedGlobals = ['fetch','XMLHttpRequest','importScripts','navigator','location','history','localStorage','sessionStorage','indexedDB','openDatabase']
const MAX_EXECUTION_MS = 5_000
const MAX_CODE_LENGTH = 10_000
const logs: string[] = []
let timedOut = false

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
  const patterns = [/\.constructor\.constructor\s*\(/g,/\["constructor"\]\s*\["constructor"\]\s*\(/g,/(?:__lookupGetter__|__lookupSetter__|__defineGetter__|__defineSetter__)\s*\(/g]
  let sanitized = code
  for (const pattern of patterns) { sanitized = sanitized.replace(pattern, '/*blocked*/(') }
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
    const sandboxedFn = new Function(
      'console','Math','Date','JSON','parseInt','parseFloat','isNaN','isFinite',
      'encodeURIComponent','decodeURIComponent','encodeURI','decodeURI',
      'String','Number','Boolean','Array','Object','RegExp',
      'Error','TypeError','RangeError','SyntaxError',
      'setTimeout','clearTimeout','setInterval','clearInterval',
      `"use strict"; ${safeCode}`
    )
    const result = sandboxedFn(
      fakeConsole,Math,Date,JSON,parseInt,parseFloat,isNaN,isFinite,
      encodeURIComponent,decodeURIComponent,encodeURI,decodeURI,
      String,Number,Boolean,Array,Object,RegExp,
      Error,TypeError,RangeError,SyntaxError,
      createSafeTimeout,clearTimeout,createSafeInterval,clearInterval,
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
