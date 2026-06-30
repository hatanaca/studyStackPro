import { ref } from 'vue'
import { youtubeApi, type YouTubeSearchItem } from '@/api/modules/youtube.api'

/**
 * Composable compartilhado de busca YouTube.
 *
 * Encapsula lógica de busca, paginação e estado de carregamento.
 * Consumido por `youtube.store` e `player.store` para evitar duplicação.
 *
 * @example
 * ```ts
 * const { results, loading, error, search } = useYouTubeSearch()
 * await search('vue.js tutorial')
 * ```
 */
export function useYouTubeSearch() {
  const results = ref<YouTubeSearchItem[]>([])
  const nextPageToken = ref<string | null>(null)
  const prevPageToken = ref<string | null>(null)
  const totalResults = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function search(query: string, pageToken = '', maxResults = 20) {
    if (!query.trim()) return
    loading.value = true
    error.value = null
    try {
      const { data } = await youtubeApi.search(query.trim(), pageToken, maxResults)
      if (data.success && data.data) {
        results.value = data.data.items
        nextPageToken.value = data.data.nextPageToken
        prevPageToken.value = data.data.prevPageToken
        totalResults.value = data.data.totalResults
      }
    } catch (e: unknown) {
      const err = e as { response?: { data?: { error?: { message?: string } } } }
      error.value = err?.response?.data?.error?.message ?? 'Falha ao buscar vídeos.'
      results.value = []
      nextPageToken.value = null
      prevPageToken.value = null
      totalResults.value = 0
    } finally {
      loading.value = false
    }
  }

  function clear() {
    results.value = []
    nextPageToken.value = null
    prevPageToken.value = null
    totalResults.value = 0
    error.value = null
  }

  return {
    results,
    nextPageToken,
    prevPageToken,
    totalResults,
    loading,
    error,
    search,
    clear,
  }
}
