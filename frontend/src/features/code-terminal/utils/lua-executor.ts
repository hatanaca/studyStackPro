/**
 * Executa código Lua usando Fengari (Lua VM em JavaScript).
 * Executa na thread principal (não em Web Worker) porque Fengari depende de APIs de browser.
 */
import type { CodeExecutionResult } from '../types/code-terminal.types'

const MAX_EXECUTION_MS = 5_000
const MAX_CODE_LENGTH = 10_000

export async function executeLua(code: string): Promise<CodeExecutionResult> {
  const startTime = performance.now()

  if (code.length > MAX_CODE_LENGTH) {
    return {
      success: false,
      output: '',
      error: `Código excede o limite de ${MAX_CODE_LENGTH} caracteres.`,
      executionTime: 0,
      language: 'lua',
    }
  }

  try {
    const fengari = await import('fengari-web')
    const { lua, lauxlib, to_luastring, to_jsstring } = fengari

    const L = lauxlib.luaL_newstate()
    lauxlib.luaL_openlibs(L)

    const logs: string[] = []

    // Override print function to capture output
    lua.lua_pushcfunction(L, () => {
      const nargs = lua.lua_gettop(L)
      const parts: string[] = []
      for (let i = 1; i <= nargs; i++) {
        const s = lauxlib.luaL_tolstring(L, i)
        if (s !== null) {
          parts.push(to_jsstring(s))
        }
        lua.lua_pop(L, 1)
      }
      logs.push(parts.join('\t'))
      return 0
    })
    lua.lua_setglobal(L, to_luastring('print'))

    // Load and execute code
    const loadResult = lauxlib.luaL_loadstring(L, to_luastring(code))

    if (loadResult !== lua.LUA_OK) {
      const errMsgRaw = lua.lua_tostring(L, -1)
      const errMsg = errMsgRaw ? to_jsstring(errMsgRaw) : ''
      lua.lua_pop(L, 1)
      const executionTime = Math.round(performance.now() - startTime)
      return {
        success: false,
        output: logs.join('\n'),
        error: errMsg || 'Erro ao carregar código Lua',
        executionTime,
        language: 'lua',
      }
    }

    // Execute with timeout
    let timedOut = false
    const timeoutId = setTimeout(() => {
      timedOut = true
    }, MAX_EXECUTION_MS)

    const callResult = lua.lua_pcall(L, 0, lua.LUA_MULTRET, 0)
    clearTimeout(timeoutId)

    if (timedOut) {
      return {
        success: false,
        output: logs.join('\n'),
        error: `Execução excedeu o limite de ${MAX_EXECUTION_MS / 1000}s.`,
        executionTime: MAX_EXECUTION_MS,
        language: 'lua',
      }
    }

    const executionTime = Math.round(performance.now() - startTime)

    if (callResult !== lua.LUA_OK) {
      const errMsgRaw = lua.lua_tostring(L, -1)
      const errMsg = errMsgRaw ? to_jsstring(errMsgRaw) : ''
      lua.lua_pop(L, 1)
      return {
        success: false,
        output: logs.join('\n'),
        error: errMsg || 'Erro desconhecido na execução Lua',
        executionTime,
        language: 'lua',
      }
    }

    // Check if there's a return value to print
    const nResults = lua.lua_gettop(L)
    if (nResults > 0) {
      for (let i = 1; i <= nResults; i++) {
        const s = lauxlib.luaL_tolstring(L, i)
        if (s !== null) {
          logs.push(to_jsstring(s))
        }
        lua.lua_pop(L, 1)
      }
    }

    lua.lua_close(L)

    return {
      success: true,
      output: logs.join('\n'),
      error: null,
      executionTime,
      language: 'lua',
    }
  } catch (err) {
    const executionTime = Math.round(performance.now() - startTime)
    return {
      success: false,
      output: '',
      error: err instanceof Error ? err.message : 'Erro ao inicializar Lua',
      executionTime,
      language: 'lua',
    }
  }
}
