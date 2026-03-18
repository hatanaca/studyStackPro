import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth.store'
import { authApi } from '@/api/modules/auth.api'
import { resetBackgroundRefresh } from '@/router/guards'

vi.mock('@/api/modules/auth.api', () => ({
  authApi: {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}))

vi.mock('@/router/guards', () => ({
  resetBackgroundRefresh: vi.fn(),
}))

const mockUser = {
  id: 'user-1',
  name: 'Test User',
  email: 'test@example.com',
  timezone: 'UTC',
}
const mockToken = 'jwt-token-123'

describe('auth.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('login stores token and user', async () => {
    vi.mocked(authApi.login).mockResolvedValue({
      data: {
        success: true,
        data: { user: mockUser, token: mockToken },
      },
    } as never)

    const store = useAuthStore()
    await store.login('test@example.com', 'password')

    expect(store.token).toBe(mockToken)
    expect(store.user).toEqual(mockUser)
    expect(localStorage.getItem('studytrack_token')).toBe(mockToken)
    expect(localStorage.getItem('studytrack_user')).toBe(JSON.stringify(mockUser))
  })

  it('logout clears token and user and calls resetBackgroundRefresh', async () => {
    vi.mocked(authApi.logout).mockResolvedValue({} as never)

    const store = useAuthStore()
    store.token = mockToken
    store.user = mockUser
    localStorage.setItem('studytrack_token', mockToken)
    localStorage.setItem('studytrack_user', JSON.stringify(mockUser))

    await store.logout()

    expect(store.token).toBe(null)
    expect(store.user).toBe(null)
    expect(localStorage.getItem('studytrack_token')).toBe(null)
    expect(localStorage.getItem('studytrack_user')).toBe(null)
    expect(resetBackgroundRefresh).toHaveBeenCalledOnce()
    expect(authApi.logout).toHaveBeenCalledOnce()
  })

  it('clearSessionLocally removes token without calling API', () => {
    const store = useAuthStore()
    store.token = mockToken
    store.user = mockUser
    localStorage.setItem('studytrack_token', mockToken)

    store.clearSessionLocally()

    expect(store.token).toBe(null)
    expect(store.user).toBe(null)
    expect(localStorage.getItem('studytrack_token')).toBe(null)
    expect(authApi.logout).not.toHaveBeenCalled()
  })

  it('fetchMe updates user', async () => {
    const updatedUser = { ...mockUser, name: 'Updated Name' }
    vi.mocked(authApi.me).mockResolvedValue({
      data: { success: true, data: updatedUser },
    } as never)

    const store = useAuthStore()
    store.user = mockUser
    await store.fetchMe()

    expect(store.user).toEqual(updatedUser)
    expect(localStorage.getItem('studytrack_user')).toBe(JSON.stringify(updatedUser))
  })

  it('isAuthenticated returns true when token exists', async () => {
    vi.mocked(authApi.login).mockResolvedValue({
      data: {
        success: true,
        data: { user: mockUser, token: mockToken },
      },
    } as never)

    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)

    await store.login('test@example.com', 'password')
    expect(store.isAuthenticated).toBe(true)

    await store.logout()
    expect(store.isAuthenticated).toBe(false)
  })
})
