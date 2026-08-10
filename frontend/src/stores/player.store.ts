import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import {
  youtubeApi,
  type YouTubePlaylistItem,
  type YouTubeSearchItem,
} from '@/api/modules/youtube.api'
import {
  type PlayerMode,
  type RepeatMode,
  type FavoriteEntry,
  type TrackInfo,
  loadPlayerState,
  savePlayerState,
  loadFavorites,
  saveFavorites,
  loadShuffle,
  saveShuffle,
  loadRepeat,
  saveRepeat,
  loadVolume,
  saveVolume,
} from './playerStorage'

export type { TrackInfo } from './playerStorage'

const MAX_SEARCH_RESULTS = 100

export const usePlayerStore = defineStore('player', () => {
  // --- Data (loaded once from localStorage) ---
  const saved = loadPlayerState()
  const playlists = ref<YouTubePlaylistItem[]>([])
  const loadingPlaylists = ref(false)
  const playlistError = ref<string | null>(null)

  const searchQuery = ref('')
  const searching = ref(false)
  const searchError = ref<string | null>(null)
  const searchNextPageToken = ref<string | null>(null)

  const favorites = ref<FavoriteEntry[]>(loadFavorites())

  const mode = ref<PlayerMode>(saved.mode)
  const selectedPlaylist = ref<YouTubePlaylistItem | null>(saved.playlist)
  const videoIndex = ref(saved.videoIndex)
  const isPlaying = ref(saved.isPlaying)
  const isExpanded = ref(saved.isExpanded)
  const searchResults = ref<YouTubeSearchItem[]>(saved.searchResults)

  // --- Player controls state ---
  const isShuffled = ref(loadShuffle())
  const repeatMode = ref<RepeatMode>(loadRepeat())
  const volume = ref(loadVolume())
  const currentTime = ref(0)
  const duration = ref(0)
  const progress = computed(() =>
    duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0
  )

  // --- Persist watchers ---
  watch(isShuffled, (v) => saveShuffle(v))
  watch(repeatMode, (v) => saveRepeat(v))
  watch(volume, (v) => saveVolume(v))

  const currentPlaylistId = computed(() =>
    mode.value === 'playlists' || mode.value === 'favorites'
      ? (selectedPlaylist.value?.id ?? null)
      : null
  )
  const currentVideoId = computed(() => {
    if (mode.value === 'search' && searchResults.value.length > 0) {
      return (
        searchResults.value[videoIndex.value]?.id?.videoId ??
        searchResults.value[0]?.id?.videoId ??
        null
      )
    }
    return null
  })
  const currentTrack = computed<TrackInfo | null>(() => {
    if (mode.value === 'search' && searchResults.value.length > 0) {
      const item = searchResults.value[videoIndex.value] ?? searchResults.value[0]
      if (!item?.snippet) return null
      return {
        title: item.snippet.title ?? 'Sem título',
        artist: item.snippet.channelTitle ?? '',
        thumbnail: item.snippet.thumbnails?.high?.url ?? item.snippet.thumbnails?.medium?.url ?? '',
        videoId: item.id?.videoId ?? '',
      }
    }
    if ((mode.value === 'playlists' || mode.value === 'favorites') && selectedPlaylist.value) {
      return {
        title: selectedPlaylist.value.snippet?.title ?? 'Playlist',
        artist: selectedPlaylist.value.snippet?.channelTitle ?? '',
        thumbnail: selectedPlaylist.value.snippet?.thumbnails?.high?.url ?? '',
        videoId: '',
      }
    }
    return null
  })
  const hasContent = computed(() => !!currentPlaylistId.value || searchResults.value.length > 0)

  function persist() {
    savePlayerState({
      playlist: selectedPlaylist.value,
      videoIndex: videoIndex.value,
      isPlaying: isPlaying.value,
      isExpanded: isExpanded.value,
      mode: mode.value,
      searchResults: searchResults.value,
    })
  }

  // --- Playlists ---
  async function fetchPlaylists() {
    loadingPlaylists.value = true
    playlistError.value = null
    try {
      const r = await youtubeApi.playlists()
      playlists.value = r.data.data?.items ?? []
    } catch (e: unknown) {
      playlistError.value = getErrorMessage(e, 'Falha ao carregar')
    } finally {
      loadingPlaylists.value = false
      persist()
    }
  }
  function selectPlaylist(p: YouTubePlaylistItem) {
    mode.value = 'playlists'
    selectedPlaylist.value = p
    videoIndex.value = 0
    isPlaying.value = true
    isExpanded.value = true
    persist()
  }

  // --- Search ---
  async function searchVideos(query: string) {
    if (!query.trim()) return
    searching.value = true
    searchError.value = null
    try {
      const r = await youtubeApi.search(query.trim(), undefined, 20)
      searchResults.value = r.data.data?.items ?? []
      searchNextPageToken.value = r.data.data?.nextPageToken ?? null
      if (!searchResults.value.length) searchError.value = 'Nenhum resultado.'
    } catch (e: unknown) {
      searchError.value = getErrorMessage(e, 'Falha ao buscar')
    } finally {
      searching.value = false
      persist()
    }
  }

  async function loadMoreResults() {
    if (!searchNextPageToken.value || searching.value || !searchQuery.value) return
    if (searchResults.value.length >= MAX_SEARCH_RESULTS) return
    searching.value = true
    try {
      const r = await youtubeApi.search(searchQuery.value, searchNextPageToken.value, 20)
      const newItems = r.data.data?.items ?? []
      searchResults.value = [...searchResults.value, ...newItems].slice(0, MAX_SEARCH_RESULTS)
      searchNextPageToken.value = r.data.data?.nextPageToken ?? null
      if (searchResults.value.length >= MAX_SEARCH_RESULTS) searchNextPageToken.value = null
    } catch (e: unknown) {
      if (import.meta.env.DEV) console.warn('[player.store] Falha ao carregar mais resultados', e)
    } finally {
      searching.value = false
      persist()
    }
  }
  function playSearchResult(i: number) {
    if (i >= 0 && i < searchResults.value.length) {
      videoIndex.value = i
      isPlaying.value = true
      isExpanded.value = true
      persist()
    }
  }

  // --- Favorites ---
  function addToFavorites() {
    if (!selectedPlaylist.value) return
    const exists = favorites.value.find((f) => f.playlistId === selectedPlaylist.value!.id)
    if (!exists) {
      favorites.value.push({
        playlistId: selectedPlaylist.value.id,
        title: selectedPlaylist.value.snippet?.title ?? 'Sem título',
        thumbnail: selectedPlaylist.value.snippet?.thumbnails?.medium?.url ?? '',
      })
      saveFavorites(favorites.value)
    }
  }
  function removeFavorite(playlistId: string) {
    favorites.value = favorites.value.filter((f) => f.playlistId !== playlistId)
    saveFavorites(favorites.value)
  }
  function selectFavorite(entry: FavoriteEntry) {
    mode.value = 'favorites'
    selectedPlaylist.value = favoriteToPlaylist(entry)
    videoIndex.value = 0
    isPlaying.value = true
    isExpanded.value = true
    persist()
  }
  function isFavorite(playlistId: string) {
    return favorites.value.some((f) => f.playlistId === playlistId)
  }

  // Shuffle history for search mode
  let shuffleHistory: number[] = []

  // --- Controls ---
  function switchMode(m: PlayerMode) {
    mode.value = m
    videoIndex.value = 0
    shuffleHistory = []
    persist()
  }
  function nextVideo() {
    if (mode.value === 'search') {
      if (isShuffled.value) {
        shuffleHistory.push(videoIndex.value)
        let next: number
        const len = searchResults.value.length
        do {
          next = Math.floor(Math.random() * len)
        } while (next === videoIndex.value && len > 1)
        videoIndex.value = next
      } else {
        videoIndex.value =
          videoIndex.value < searchResults.value.length - 1 ? videoIndex.value + 1 : 0
      }
    } else {
      // Playlist mode: increment index (playlist items loaded on demand)
      videoIndex.value++
    }
    isPlaying.value = true
    persist()
  }
  function prevVideo() {
    if (isShuffled.value && shuffleHistory.length > 0) {
      videoIndex.value = shuffleHistory.pop()!
    } else if (videoIndex.value > 0) {
      videoIndex.value--
    } else if (mode.value === 'search') {
      videoIndex.value = searchResults.value.length - 1
    } else if (mode.value === 'playlists') {
      // Wrap to last video in playlist (index is loaded on demand)
    }
    isPlaying.value = true
    persist()
  }
  function togglePlay() {
    isPlaying.value = !isPlaying.value
    persist()
  }
  function toggleExpand() {
    isExpanded.value = !isExpanded.value
    persist()
  }
  function toggleShuffle() {
    isShuffled.value = !isShuffled.value
    if (!isShuffled.value) shuffleHistory = []
  }
  function cycleRepeat() {
    repeatMode.value =
      repeatMode.value === 'none' ? 'playlist' : repeatMode.value === 'playlist' ? 'single' : 'none'
  }
  function setVolume(v: number) {
    volume.value = Math.max(0, Math.min(100, v))
  }
  function clearPlaylist() {
    selectedPlaylist.value = null
    videoIndex.value = 0
    isPlaying.value = false
    persist()
  }

  return {
    playlists,
    loadingPlaylists,
    playlistError,
    searchQuery,
    searchResults,
    searching,
    searchError,
    searchNextPageToken,
    favorites,
    mode,
    selectedPlaylist,
    videoIndex,
    isPlaying,
    isExpanded,
    isShuffled,
    repeatMode,
    volume,
    currentTime,
    duration,
    progress,
    currentPlaylistId,
    currentVideoId,
    currentTrack,
    hasContent,
    fetchPlaylists,
    selectPlaylist,
    searchVideos,
    loadMoreResults,
    playSearchResult,
    addToFavorites,
    removeFavorite,
    selectFavorite,
    isFavorite,
    switchMode,
    nextVideo,
    prevVideo,
    togglePlay,
    toggleExpand,
    toggleShuffle,
    cycleRepeat,
    setVolume,
    clearPlaylist,
    persist,
  }
})

function favoriteToPlaylist(entry: FavoriteEntry): YouTubePlaylistItem {
  return {
    id: entry.playlistId,
    snippet: {
      title: entry.title,
      channelTitle: '',
      description: '',
      thumbnails: { medium: { url: entry.thumbnail }, high: { url: entry.thumbnail } },
      publishedAt: '',
    },
  } as YouTubePlaylistItem
}

function getErrorMessage(e: unknown, fallback: string): string {
  if (e && typeof e === 'object' && 'response' in e) {
    const data = (e as { response?: { data?: { error?: { message?: string } } } }).response?.data
    return data?.error?.message ?? fallback
  }
  return fallback
}
