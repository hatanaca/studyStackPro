import { setActivePinia, createPinia } from 'pinia'
import { useYouTubeStore } from '../youtube.store'
import { youtubeApi } from '@/api/modules/youtube.api'

vi.mock('@/api/modules/youtube.api', () => ({
  youtubeApi: {
    search: vi.fn(),
    videos: vi.fn(),
  },
}))

const mockSearchResult = {
  items: [
    { id: { videoId: 'v1' }, snippet: { title: 'Video 1', channelTitle: 'Channel' } },
    { id: { videoId: 'v2' }, snippet: { title: 'Video 2', channelTitle: 'Channel' } },
  ],
  nextPageToken: 'next123',
  prevPageToken: null,
  totalResults: 100,
}

const mockVideoDetail = {
  items: [
    { id: 'v1', snippet: { title: 'Video 1', description: 'Description' } },
  ],
}

describe('youtube.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('initializes with empty state', () => {
    const store = useYouTubeStore()
    expect(store.results).toEqual([])
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
    expect(store.totalResults).toBe(0)
  })

  it('search stores results', async () => {
    vi.mocked(youtubeApi.search).mockResolvedValue({
      data: { success: true, data: mockSearchResult },
    } as never)

    const store = useYouTubeStore()
    await store.search('vue tutorial')

    expect(store.results).toEqual(mockSearchResult.items)
    expect(store.nextPageToken).toBe('next123')
    expect(store.totalResults).toBe(100)
    expect(store.loading).toBe(false)
  })

  it('search sets loading during request', async () => {
    let resolveSearch!: (v: unknown) => void
    vi.mocked(youtubeApi.search).mockImplementation(
      () => new Promise((resolve) => { resolveSearch = resolve }) as never,
    )

    const store = useYouTubeStore()
    const searchPromise = store.search('test')

    expect(store.loading).toBe(true)

    resolveSearch({ data: { success: true, data: mockSearchResult } })
    await searchPromise

    expect(store.loading).toBe(false)
  })

  it('search sets error on failure', async () => {
    vi.mocked(youtubeApi.search).mockRejectedValue({
      response: { data: { error: { message: 'API Error' } } },
    })

    const store = useYouTubeStore()
    await store.search('test')

    expect(store.error).toBe('API Error')
    expect(store.loading).toBe(false)
  })

  it('search uses generic error message for network errors', async () => {
    vi.mocked(youtubeApi.search).mockRejectedValue(new Error('Network'))

    const store = useYouTubeStore()
    await store.search('test')

    expect(store.error).toBe('Falha ao buscar vídeos.')
  })

  it('loadVideoDetail stores detail', async () => {
    vi.mocked(youtubeApi.videos).mockResolvedValue({
      data: { success: true, data: mockVideoDetail },
    } as never)

    const store = useYouTubeStore()
    await store.loadVideoDetail('v1')

    expect(store.selectedVideoId).toBe('v1')
    expect(store.videoDetail).toEqual(mockVideoDetail.items[0])
  })

  it('clear resets all state', async () => {
    vi.mocked(youtubeApi.search).mockResolvedValue({
      data: { success: true, data: mockSearchResult },
    } as never)

    const store = useYouTubeStore()
    await store.search('test')
    store.clear()

    expect(store.results).toEqual([])
    expect(store.nextPageToken).toBeNull()
    expect(store.totalResults).toBe(0)
    expect(store.error).toBeNull()
    expect(store.selectedVideoId).toBeNull()
    expect(store.videoDetail).toBeNull()
  })
})
