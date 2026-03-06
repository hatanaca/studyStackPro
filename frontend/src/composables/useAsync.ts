import { computed, shallowRef } from 'vue'

export type AsyncStatus = 'idle' | 'pending' | 'success' | 'error'

export interface UseAsyncResult<T> {
  data: import('vue').ShallowRef<T | null>
  error: import('vue').ShallowRef<Error | null>
  status: import('vue').ShallowRef<AsyncStatus>
  isIdle: import('vue').ComputedRef<boolean>
  isPending: import('vue').ComputedRef<boolean>
  isSuccess: import('vue').ComputedRef<boolean>
  isError: import('vue').ComputedRef<boolean>
  execute: (fn: () => Promise<T>) => Promise<T | null>
  reset: () => void
}

/**
 * Executa uma função assíncrona e expõe estado (data, error, status).
 */
export function useAsync<T = unknown>(): UseAsyncResult<T> {
  const data = shallowRef<T | null>(null)
  const error = shallowRef<Error | null>(null)
  const status = shallowRef<AsyncStatus>('idle')

  const isIdle = computed(() => status.value === 'idle')
  const isPending = computed(() => status.value === 'pending')
  const isSuccess = computed(() => status.value === 'success')
  const isError = computed(() => status.value === 'error')

  async function execute(fn: () => Promise<T>): Promise<T | null> {
    status.value = 'pending'
    error.value = null
    try {
      const result = await fn()
      data.value = result
      status.value = 'success'
      return result
    } catch (e) {
      error.value = e instanceof Error ? e : new Error(String(e))
      status.value = 'error'
      return null
    }
  }

  function reset() {
    data.value = null
    error.value = null
    status.value = 'idle'
  }

  return {
    data,
    error,
    status,
    isIdle,
    isPending,
    isSuccess,
    isError,
    execute,
    reset,
  }
}
