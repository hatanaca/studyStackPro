import { ref } from 'vue'

/**
 * Copia texto para a área de transferência e expõe estado de sucesso/erro.
 */
export function useClipboard() {
  const copied = ref(false)
  const error = ref<string | null>(null)

  async function copy(text: string): Promise<boolean> {
    copied.value = false
    error.value = null
    try {
      await navigator.clipboard.writeText(text)
      copied.value = true
      setTimeout(() => { copied.value = false }, 2000)
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Falha ao copiar'
      return false
    }
  }

  return { copy, copied, error }
}
