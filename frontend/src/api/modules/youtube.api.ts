import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'

/** Resultado da busca: snippet básico. */
export interface YouTubeSearchItem {
  id: { videoId: string }
  snippet: {
    title: string
    description: string
    thumbnails: { medium: { url: string }; high: { url: string } }
    channelTitle: string
    publishedAt: string
  }
}

/** Resultado de detalhes: inclui duração e estatísticas. */
export interface YouTubeVideoItem {
  id: string
  snippet: {
    title: string
    description: string
    thumbnails: { medium: { url: string }; high: { url: string } }
    channelTitle: string
    publishedAt: string
  }
  contentDetails: {
    duration: string // ISO 8601, ex: "PT1H23M45S"
  }
  statistics: {
    viewCount: string
    likeCount: string
  }
}

export interface YouTubeSearchResult {
  items: YouTubeSearchItem[]
  nextPageToken: string | null
  prevPageToken: string | null
  totalResults: number
}

export interface YouTubeVideosResult {
  items: YouTubeVideoItem[]
}

export const youtubeApi = {
  search: (q: string, pageToken = '', maxResults = 20) =>
    apiClient.get<ApiResponse<YouTubeSearchResult>>(ENDPOINTS.youtube.search, {
      params: { q, pageToken, maxResults },
    }),
  videos: (ids: string) =>
    apiClient.get<ApiResponse<YouTubeVideosResult>>(ENDPOINTS.youtube.videos, {
      params: { ids },
    }),
}
