declare namespace YT {
  interface Player {
    destroy(): void
    getDuration(): number
    getCurrentTime(): number
    getVolume(): number
    isMuted(): boolean
    loadVideoById(videoId: string): void
    loadPlaylist(playlistId: string, startIndex?: number): void
    pauseVideo(): void
    playVideo(): void
    seekTo(seconds: number, allowSeekAhead: boolean): void
    setVolume(volume: number): void
    mute(): void
    unMute(): void
    getPlayerState(): number
    getPlaylistIndex(): number
    setShuffle(shuffle: boolean): void
    setLoop(loop: boolean): void
    playVideoAt(index: number): void
  }

  interface PlayerEvent {
    target: Player
    data: number
  }

  interface OnStateChangeEvent {
    target: Player
    data: number
  }

  enum PlayerState {
    UNSTARTED = -1,
    ENDED = 0,
    PLAYING = 1,
    PAUSED = 2,
    BUFFERING = 3,
    CUED = 5,
  }

  interface PlayerOptions {
    height?: string | number
    width?: string | number
    videoId?: string
    playerVars?: Record<string, unknown>
    events?: {
      onReady?: (event: PlayerEvent) => void
      onStateChange?: (event: OnStateChangeEvent) => void
      onError?: (event: PlayerEvent) => void
    }
  }

  function ready(callback: () => void): void

  class Player {
    constructor(target: string | HTMLElement, options: PlayerOptions)
    destroy(): void
    getDuration(): number
    getCurrentTime(): number
    getVolume(): number
    isMuted(): boolean
    loadVideoById(videoId: string): void
    loadPlaylist(playlistId: string, startIndex?: number): void
    pauseVideo(): void
    playVideo(): void
    seekTo(seconds: number, allowSeekAhead: boolean): void
    setVolume(volume: number): void
    mute(): void
    unMute(): void
    getPlayerState(): number
    getPlaylistIndex(): number
    setShuffle(shuffle: boolean): void
    setLoop(loop: boolean): void
    playVideoAt(index: number): void
  }
}
