import { setActivePinia, createPinia } from 'pinia'
import { usePlayerStore } from '../player.store'

vi.mock('@/api/modules/youtube.api', () => ({
  youtubeApi: {
    playlists: vi.fn(),
    search: vi.fn(),
  },
}))

describe('player.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('initializes with default state', () => {
    const store = usePlayerStore()
    expect(store.isPlaying).toBe(false)
    expect(store.isExpanded).toBe(false)
    expect(store.videoIndex).toBe(0)
    expect(store.mode).toBe('search')
    expect(store.volume).toBe(80)
    expect(store.isShuffled).toBe(false)
    expect(store.repeatMode).toBe('none')
  })

  it('togglePlay flips isPlaying', () => {
    const store = usePlayerStore()
    store.togglePlay()
    expect(store.isPlaying).toBe(true)

    store.togglePlay()
    expect(store.isPlaying).toBe(false)
  })

  it('toggleExpand flips isExpanded', () => {
    const store = usePlayerStore()
    store.toggleExpand()
    expect(store.isExpanded).toBe(true)

    store.toggleExpand()
    expect(store.isExpanded).toBe(false)
  })

  it('setVolume clamps between 0 and 100', () => {
    const store = usePlayerStore()
    store.setVolume(150)
    expect(store.volume).toBe(100)

    store.setVolume(-10)
    expect(store.volume).toBe(0)

    store.setVolume(50)
    expect(store.volume).toBe(50)
  })

  it('cycleRepeat cycles none → playlist → single → none', () => {
    const store = usePlayerStore()
    expect(store.repeatMode).toBe('none')

    store.cycleRepeat()
    expect(store.repeatMode).toBe('playlist')

    store.cycleRepeat()
    expect(store.repeatMode).toBe('single')

    store.cycleRepeat()
    expect(store.repeatMode).toBe('none')
  })

  it('toggleShuffle flips isShuffled', () => {
    const store = usePlayerStore()
    store.toggleShuffle()
    expect(store.isShuffled).toBe(true)

    store.toggleShuffle()
    expect(store.isShuffled).toBe(false)
  })

  it('switchMode changes mode and resets index', () => {
    const store = usePlayerStore()
    store.switchMode('playlists')
    expect(store.mode).toBe('playlists')
    expect(store.videoIndex).toBe(0)
  })

  it('playSearchResult sets index and starts playing', () => {
    const store = usePlayerStore()
    store.searchResults = [
      { id: { videoId: 'v1' }, snippet: { title: 'T1' } },
      { id: { videoId: 'v2' }, snippet: { title: 'T2' } },
    ] as never

    store.playSearchResult(1)
    expect(store.videoIndex).toBe(1)
    expect(store.isPlaying).toBe(true)
    expect(store.isExpanded).toBe(true)
  })

  it('playSearchResult ignores out of bounds index', () => {
    const store = usePlayerStore()
    store.playSearchResult(99)
    expect(store.videoIndex).toBe(0)
    expect(store.isPlaying).toBe(false)
  })

  it('hasContent is false initially', () => {
    const store = usePlayerStore()
    expect(store.hasContent).toBe(false)
  })

  it('hasContent is true when search results exist', () => {
    const store = usePlayerStore()
    store.searchResults = [{ id: { videoId: 'v1' }, snippet: { title: 'T1' } }] as never
    expect(store.hasContent).toBe(true)
  })

  it('clearPlaylist resets state', () => {
    const store = usePlayerStore()
    store.togglePlay()
    store.toggleExpand()
    store.clearPlaylist()

    expect(store.selectedPlaylist).toBeNull()
    expect(store.videoIndex).toBe(0)
    expect(store.isPlaying).toBe(false)
  })

  it('nextVideo advances index in search mode', () => {
    const store = usePlayerStore()
    store.searchResults = [
      { id: { videoId: 'v1' } },
      { id: { videoId: 'v2' } },
      { id: { videoId: 'v3' } },
    ] as never

    store.nextVideo()
    expect(store.videoIndex).toBe(1)

    store.nextVideo()
    expect(store.videoIndex).toBe(2)

    store.nextVideo()
    expect(store.videoIndex).toBe(0)
  })

  it('prevVideo goes back in search mode', () => {
    const store = usePlayerStore()
    store.searchResults = [
      { id: { videoId: 'v1' } },
      { id: { videoId: 'v2' } },
    ] as never

    store.prevVideo()
    expect(store.videoIndex).toBe(1)

    store.prevVideo()
    expect(store.videoIndex).toBe(0)
  })

  it('favorites management works', () => {
    const store = usePlayerStore()
    expect(store.favorites).toHaveLength(0)
    expect(store.isFavorite('p1')).toBe(false)
  })

  it('progress computes correctly', () => {
    const store = usePlayerStore()
    expect(store.progress).toBe(0)
  })
})
