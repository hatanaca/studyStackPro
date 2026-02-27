import { ref, watch } from 'vue'

/**
 * Persistência tipada no localStorage com reatividade
 */
export function useLocalStorage<T>(key: string, defaultValue: T) {
  const raw = localStorage.getItem(key)
  const data = ref<T>(
    raw ? (JSON.parse(raw) as T) : defaultValue
  ) as { value: T }

  watch(
    data,
    (v) => {
      try {
        localStorage.setItem(key, JSON.stringify(v))
      } catch {
        // quota exceeded ou localStorage desabilitado
      }
    },
    { deep: true }
  )

  return data
}
