import { ref, type Ref } from 'vue'

export interface AsyncState<T> {
  data: Ref<T>
  loading: Ref<boolean>
  error: Ref<string | null>
}

/**
 * Composable genérico para estado assíncrono (data/loading/error).
 *
 * Elimina a repetição de tripletas `{ data, loading, error }` em stores.
 *
 * @example
 * ```ts
 * const meta = useAsyncState<Record<string, LaneChampion[]>>({})
 * await meta.run(async () => {
 *   const { data } = await api.someEndpoint()
 *   return data.data?.data?.positions ?? {}
 * })
 * // meta.data.value, meta.loading.value, meta.error.value
 * ```
 */
export function useAsyncState<T>(defaultValue: T): AsyncState<T> & { run: (fn: () => Promise<T>, fallbackMessage?: string) => Promise<T | null> } {
  const data = ref<T>(defaultValue) as Ref<T>
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function run(fn: () => Promise<T>, fallbackMessage = 'Erro ao buscar dados'): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      const result = await fn()
      data.value = result
      return result
    } catch (e: unknown) {
      const err = e as { response?: { data?: { error?: { message?: string } } } }
      error.value = err?.response?.data?.error?.message ?? fallbackMessage
      return null
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, run }
}
