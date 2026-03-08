import { useStorage } from '@vueuse/core'

/**
 * Persistência tipada no localStorage com reatividade (VueUse).
 * @param key Chave no localStorage
 * @param defaultValue Valor inicial quando não há dado salvo
 */
export function useLocalStorage<T>(key: string, defaultValue: T) {
  return useStorage(key, defaultValue, localStorage, { serializer: { read: JSON.parse, write: JSON.stringify } })
}
