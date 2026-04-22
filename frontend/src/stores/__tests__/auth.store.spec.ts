import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth.store'
import { authApi } from '@/api/modules/auth.api'
import { fetchSanctumCsrfCookie } from '@/api/sanctum'

vi.mock('@/api/sanctum', () => ({
  fetchSanctumCsrfCookie: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('@/api/modules/auth.api', () => ({
  authApi: {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}))

const mockUser = {
  id: 'user-1',
  name: 'Test User',
  email: 'test@example.com',
  timezone: 'UTC',
}

describe('auth.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('login obtém CSRF, guarda utilizador e marca sessão validada', async () => {
    vi.mocked(authApi.login).mockResolvedValue({
      data: {
        success: true,
        data: { user: mockUser },
      },
    } as never)

    const store = useAuthStore()
    await store.login('test@example.com', 'password')

    expect(fetchSanctumCsrfCookie).toHaveBeenCalledOnce()
    expect(store.user).toEqual(mockUser)
    expect(store.sessionValidated).toBe(true)
    expect(localStorage.getItem('studytrack_user')).toBe(JSON.stringify(mockUser))
  })

  it('register obtém CSRF e guarda utilizador', async () => {
    vi.mocked(authApi.register).mockResolvedValue({
      data: {
        success: true,
        data: { user: mockUser },
      },
    } as never)

    const store = useAuthStore()
    await store.register('Test User', 'test@example.com', 'secret', 'secret')

    expect(fetchSanctumCsrfCookie).toHaveBeenCalledOnce()
    expect(store.user).toEqual(mockUser)
    expect(store.sessionValidated).toBe(true)
  })

  it('logout limpa utilizador e cache local', async () => {
    vi.mocked(authApi.logout).mockResolvedValue({} as never)

    const store = useAuthStore()
    store.user = mockUser
    store.sessionValidated = true
    localStorage.setItem('studytrack_user', JSON.stringify(mockUser))

    await store.logout()

    expect(store.user).toBe(null)
    expect(store.sessionValidated).toBe(false)
    expect(localStorage.getItem('studytrack_user')).toBe(null)
    expect(authApi.logout).toHaveBeenCalledOnce()
  })

  it('clearSessionLocally remove utilizador sem chamar a API', () => {
    const store = useAuthStore()
    store.user = mockUser
    store.sessionValidated = true
    localStorage.setItem('studytrack_user', JSON.stringify(mockUser))

    store.clearSessionLocally()

    expect(store.user).toBe(null)
    expect(store.sessionValidated).toBe(false)
    expect(localStorage.getItem('studytrack_user')).toBe(null)
    expect(authApi.logout).not.toHaveBeenCalled()
  })

  it('fetchMe actualiza utilizador', async () => {
    const updatedUser = { ...mockUser, name: 'Updated Name' }
    vi.mocked(authApi.me).mockResolvedValue({
      data: { success: true, data: updatedUser },
    } as never)

    const store = useAuthStore()
    store.user = mockUser
    await store.fetchMe()

    expect(store.user).toEqual(updatedUser)
    expect(store.sessionValidated).toBe(true)
    expect(localStorage.getItem('studytrack_user')).toBe(JSON.stringify(updatedUser))
  })

  it('inicializa sem utilizador quando o JSON em cache é inválido', () => {
    localStorage.setItem('studytrack_user', 'not-json{')
    setActivePinia(createPinia())
    const store = useAuthStore()

    expect(store.user).toBe(null)
    expect(store.sessionValidated).toBe(false)
  })

  it('isAuthenticated reflecte sessão validada e utilizador', async () => {
    vi.mocked(authApi.login).mockResolvedValue({
      data: {
        success: true,
        data: { user: mockUser },
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
