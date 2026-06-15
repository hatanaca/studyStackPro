import { defineStore } from 'pinia'
import { ref } from 'vue'
import { youtubeApi, type YouTubeSearchItem, type YouTubeVideoItem } from '@/api/modules/youtube.api'

export const useYouTubeStore = defineStore('youtube', () => {
  const results = ref<YouTubeSearchItem[]>([])
  const nextPageToken = ref<string | null>(null)
  const prevPageToken = ref<string | null>(null)
  const totalResults = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Vídeo selecionado + detalhes
  const selectedVideoId = ref<string | null>(null)
  const videoDetail = ref<YouTubeVideoItem | null>(null)
  const videoLoading = ref(false)

  async function search(query: string, pageToken = '') {
    loading.value = true
    error.value = null
    try {
      const { data } = await youtubeApi.search(query, pageToken)
      if (data.success && data.data) {
        results.value = data.data.items
        nextPageToken.value = data.data.nextPageToken
        prevPageToken.value = data.data.prevPageToken
        totalResults.value = data.data.totalResults
      }
    } catch (e: unknown) {
      const err = e as { response?: { data?: { error?: { message?: string } } } }
      error.value = err?.response?.data?.error?.message ?? 'Falha ao buscar vídeos.'
    } finally {
      loading.value = false
    }
  }

  async function loadVideoDetail(videoId: string) {
    selectedVideoId.value = videoId
    videoLoading.value = true
    videoDetail.value = null
    try {
      const { data } = await youtubeApi.videos(videoId)
      if (data.success && data.data && data.data.items.length > 0) {
        videoDetail.value = data.data.items[0]
      }
    } catch {
      // Silencioso — o player embed funciona mesmo sem detalhes
    } finally {
      videoLoading.value = false
    }
  }

  function clear() {
    results.value = []
    nextPageToken.value = null
    prevPageToken.value = null
    totalResults.value = 0
    error.value = null
    selectedVideoId.value = null
    videoDetail.value = null
  }

  return {
    results,
    nextPageToken,
    prevPageToken,
    totalResults,
    loading,
    error,
    selectedVideoId,
    videoDetail,
    videoLoading,
    search,
    loadVideoDetail,
    clear,
  }
})
