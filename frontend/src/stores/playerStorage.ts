import type { YouTubePlaylistItem, YouTubeSearchItem } from '@/api/modules/youtube.api'

export type PlayerMode = 'playlists' | 'search' | 'favorites'
export type RepeatMode = 'none' | 'playlist' | 'single'

export interface FavoriteEntry {
  playlistId: string
  title: string
  thumbnail: string
}

export interface TrackInfo {
  title: string
  artist: string
  thumbnail: string
  videoId: string
}

export interface PlayerState {
  playlist: YouTubePlaylistItem | null
  videoIndex: number
  isPlaying: boolean
  isExpanded: boolean
  mode: PlayerMode
  searchResults: YouTubeSearchItem[]
}

const STORAGE_KEY = 'studytrack_miniplayer'
const FAVORITES_KEY = 'studytrack_favorites'
const SHUFFLE_KEY = 'studytrack_shuffle'
const REPEAT_KEY = 'studytrack_repeat'
const VOLUME_KEY = 'studytrack_volume'

const DEFAULT_STATE: PlayerState = {
  playlist: null,
  videoIndex: 0,
  isPlaying: false,
  isExpanded: false,
  mode: 'search',
  searchResults: [],
}

function read<T>(key: string): T | null {
  try {
    const raw = localStorage.getItem(key)
    return raw ? (JSON.parse(raw) as T) : null
  } catch (error) {
    if (import.meta.env.DEV) console.warn(`[playerStorage] Falha ao ler ${key}`, error)
    return null
  }
}

function write(key: string, value: unknown): void {
  try {
    localStorage.setItem(key, JSON.stringify(value))
  } catch (error) {
    if (import.meta.env.DEV) console.warn(`[playerStorage] Falha ao gravar ${key}`, error)
  }
}

export function loadPlayerState(): PlayerState {
  const saved = read<Partial<PlayerState>>(STORAGE_KEY)
  return saved
    ? { ...DEFAULT_STATE, ...saved, playlist: saved.playlist ?? null }
    : { ...DEFAULT_STATE }
}

export function savePlayerState(state: PlayerState): void {
  write(STORAGE_KEY, state)
}

export function loadFavorites(): FavoriteEntry[] {
  return read<FavoriteEntry[]>(FAVORITES_KEY) ?? []
}

export function saveFavorites(list: FavoriteEntry[]): void {
  write(FAVORITES_KEY, list)
}

export function loadShuffle(): boolean {
  try {
    return localStorage.getItem(SHUFFLE_KEY) === 'true'
  } catch {
    return false
  }
}

export function saveShuffle(value: boolean): void {
  try {
    localStorage.setItem(SHUFFLE_KEY, String(value))
  } catch (error) {
    if (import.meta.env.DEV) console.warn('[playerStorage] Falha ao gravar shuffle', error)
  }
}

export function loadRepeat(): RepeatMode {
  try {
    const value = localStorage.getItem(REPEAT_KEY)
    if (value === 'none' || value === 'playlist' || value === 'single') return value
    return 'none'
  } catch {
    return 'none'
  }
}

export function saveRepeat(mode: RepeatMode): void {
  try {
    localStorage.setItem(REPEAT_KEY, mode)
  } catch (error) {
    if (import.meta.env.DEV) console.warn('[playerStorage] Falha ao gravar repeat', error)
  }
}

export function loadVolume(): number {
  try {
    const raw = localStorage.getItem(VOLUME_KEY)
    if (raw === null) return 80
    const value = Number(raw)
    return Number.isFinite(value) && value >= 0 && value <= 100 ? value : 80
  } catch {
    return 80
  }
}

export function saveVolume(volume: number): void {
  try {
    localStorage.setItem(VOLUME_KEY, String(volume))
  } catch (error) {
    if (import.meta.env.DEV) console.warn('[playerStorage] Falha ao gravar volume', error)
  }
}
