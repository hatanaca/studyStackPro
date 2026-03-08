import type { Ref } from 'vue'
import { useDebounce as useDebounceVueUse, useDebounceFn as useDebounceFnVueUse } from '@vueuse/core'

/**
 * Debounce de um valor reativo (VueUse).
 * value debounced atualiza após `delayMs` ms sem mudanças.
 */
export function useDebounce<T>(source: Ref<T>, delayMs: number): Ref<T> {
  return useDebounceVueUse(source, delayMs)
}

/**
 * Função debounced (VueUse): executa fn após `delayMs` ms da última chamada.
 */
export function useDebounceFn<T extends (...args: unknown[]) => unknown>(
  fn: T,
  delayMs: number
): (...args: Parameters<T>) => void {
  return useDebounceFnVueUse(fn, delayMs) as (...args: Parameters<T>) => void
}
