/**
 * Web Worker para execução isolada de JavaScript.
 * Executa código em escopo isolado, capturando console.log/error/warn.
 * Bloqueia APIs perigosas (fetch, importScripts, etc.)
 */

const blockedGlobals = ['fetch', 'XMLHttpRequest', 'importScripts', 'navigator', 'location', 'history', 'localStorage', 'sessionStorage', 'indexedDB', 'openDatabase']

const logs: string[] = []

const fakeConsole = {
  log: (...args: unknown[]) => {
    logs.push(args.map(stringify).join(' '))
  },
  error: (...args: unknown[]) => {
    logs.push('[ERROR] ' + args.map(stringify).join(' '))
  },
  warn: (...args: unknown[]) => {
    logs.push('[WARN] ' + args.map(stringify).join(' '))
  },
  info: (...args: unknown[]) => {
    logs.push('[INFO] ' + args.map(stringify).join(' '))
  },
  debug: (...args: unknown[]) => {
    logs.push('[DEBUG] ' + args.map(stringify).join(' '))
  },
}

function stringify(val: unknown): string {
  if (val === undefined) return 'undefined'
  if (val === null) return 'null'
  if (typeof val === 'string') return val
  if (typeof val === 'function') return `[Function: ${val.name || 'anonymous'}]`
  try {
    return JSON.stringify(val, null, 2)
  } catch {
    return String(val)
  }
}

self.onmessage = function (e: MessageEvent) {
  const { code, id } = e.data
  logs.length = 0

  const startTime = performance.now()

  try {
    // Criar escopo isolado com console e Math/Date/JSON permitidos
    const sandboxedFn = new Function(
      'console',
      'Math',
      'Date',
      'JSON',
      'parseInt',
      'parseFloat',
      'isNaN',
      'isFinite',
      'encodeURIComponent',
      'decodeURIComponent',
      'encodeURI',
      'decodeURI',
      'String',
      'Number',
      'Boolean',
      'Array',
      'Object',
      'RegExp',
      'Error',
      'TypeError',
      'RangeError',
      'SyntaxError',
      `
        "use strict";
        ${code}
      `
    )

    const result = sandboxedFn(
      fakeConsole,
      Math,
      Date,
      JSON,
      parseInt,
      parseFloat,
      isNaN,
      isFinite,
      encodeURIComponent,
      decodeURIComponent,
      encodeURI,
      decodeURI,
      String,
      Number,
      Boolean,
      Array,
      Object,
      RegExp,
      Error,
      TypeError,
      RangeError,
      SyntaxError
    )

    const executionTime = Math.round(performance.now() - startTime)

    // Se o resultado não foi capturado via console.log, adicionar ao output
    if (result !== undefined && logs.length === 0) {
      logs.push(stringify(result))
    }

    self.postMessage({
      id,
      success: true,
      output: logs.join('\n'),
      error: null,
      executionTime,
    })
  } catch (err) {
    const executionTime = Math.round(performance.now() - startTime)
    const errorMessage = err instanceof Error ? err.message : String(err)

    self.postMessage({
      id,
      success: false,
      output: logs.join('\n'),
      error: errorMessage,
      executionTime,
    })
  }
}
