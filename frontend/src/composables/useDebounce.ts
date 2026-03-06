import { ref, watch, type Ref } from 'vue'

/**
 * Debounce de um valor reativo: value debounced atualiza após `delay` ms sem mudanças.
 */
export function useDebounce<T>(source: Ref<T>, delayMs: number): Ref<T> {
  const debounced = ref(source.value) as Ref<T>
  let timeoutId: ReturnType<typeof setTimeout> | null = null

  watch(source, (newVal) => {
    if (timeoutId) clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      debounced.value = newVal
      timeoutId = null
    }, delayMs)
  }, { immediate: false })

  return debounced
}

/**
 * Função debounced: executa fn após `delay` ms da última chamada.
 */
export function useDebounceFn<T extends (...args: unknown[]) => unknown>(
  fn: T,
  delayMs: number
): (...args: Parameters<T>) => void {
  let timeoutId: ReturnType<typeof setTimeout> | null = null
  return (...args: Parameters<T>) => {
    if (timeoutId) clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      fn(...args)
      timeoutId = null
    }, delayMs)
  }
}
