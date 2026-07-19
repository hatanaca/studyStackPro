declare module 'fengari-web' {
  export const L: unknown
  export function load(source: string, chunkname?: string): () => unknown
  export const interop: unknown

  export const lua: {
    LUA_OK: number
    LUA_ERRRUN: number
    LUA_ERRSYNTAX: number
    LUA_MULTRET: number
    LUA_REGISTRYINDEX: number
    lua_gettop(L: unknown): number
    lua_settop(L: unknown, idx: number): void
    lua_pushcfunction(L: unknown, fn: (...args: unknown[]) => number): void
    lua_setglobal(L: unknown, name: Uint8Array): void
    lua_tostring(L: unknown, idx: number): Uint8Array | null
    lua_pcall(L: unknown, nargs: number, nresults: number, errfunc: number): number
    lua_pop(L: unknown, n: number): void
    lua_close(L: unknown): void
  }

  export const lauxlib: {
    luaL_newstate(): unknown
    luaL_openlibs(L: unknown): void
    luaL_loadbuffer(L: unknown, buf: Uint8Array, size: number, name: Uint8Array | null, sourceName: Uint8Array | null): number
    luaL_loadstring(L: unknown, s: Uint8Array): number
    luaL_tolstring(L: unknown, idx: number): Uint8Array | null
    luaL_error(L: unknown, fmt: Uint8Array, ...args: unknown[]): number
  }

  export const to_luastring: (s: string) => Uint8Array
  export const to_jsstring: (s: Uint8Array) => string
}
