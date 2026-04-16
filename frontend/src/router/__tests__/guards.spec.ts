import { setActivePinia, createPinia } from 'pinia'
import { setupAuthGuard, resetBackgroundRefresh } from '../guards'
import { useAuthStore } from '@/stores/auth.store'
import { authApi } from '@/api/modules/auth.api'
import type { RouteLocationNormalized, NavigationGuardNext } from 'vue-router'

vi.mock('@/api/modules/auth.api', () => ({
  authApi: {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}))

function makeTo(meta: Record<string, unknown> = {}, name = 'some-route'): RouteLocationNormalized {
  return {
    meta,
    name,
    path: '/',
    fullPath: '/',
    query: {},
    params: {},
    hash: '',
    matched: [],
    redirectedFrom: undefined,
  } as unknown as RouteLocationNormalized
}

const from = makeTo({}, 'home')

describe('setupAuthGuard', () => {
  let next: NavigationGuardNext

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorage.clear()
    resetBackgroundRefresh()
    next = vi.fn() as unknown as NavigationGuardNext
  })

  it('redirects to login when route requires auth and no token', async () => {
    const to = makeTo({ requiresAuth: true })
    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith({ name: 'login' })
  })

  it('redirects to dashboard when guest route and authenticated', async () => {
    vi.mocked(authApi.me).mockResolvedValue({
      data: { success: true, data: { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } },
    } as never)

    const authStore = useAuthStore()
    authStore.token = 'valid-token'
    const to = makeTo({ guest: true })

    await setupAuthGuard(to, from, next)

    expect(authApi.me).toHaveBeenCalledOnce()
    expect(next).toHaveBeenCalledWith({ name: 'dashboard' })
  })

  it('calls fetchMe when authenticated but no user', async () => {
    vi.mocked(authApi.me).mockResolvedValue({
      data: { success: true, data: { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } },
    } as never)

    const authStore = useAuthStore()
    authStore.token = 'valid-token'
    const to = makeTo({ requiresAuth: true })

    await setupAuthGuard(to, from, next)

    expect(authApi.me).toHaveBeenCalledOnce()
    expect(next).toHaveBeenCalledWith()
  })

  it('calls fetchMe once when authenticated with cached user (valida token antes da rota)', async () => {
    vi.mocked(authApi.me).mockResolvedValue({
      data: { success: true, data: { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } },
    } as never)

    const authStore = useAuthStore()
    authStore.token = 'valid-token'
    authStore.user = { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } as never

    const to = makeTo({ requiresAuth: true })
    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith()
    expect(authApi.me).toHaveBeenCalledOnce()
  })

  it('skips fetchMe on second navigation when sessão já validada', async () => {
    vi.mocked(authApi.me).mockResolvedValue({
      data: { success: true, data: { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } },
    } as never)

    const authStore = useAuthStore()
    authStore.token = 'valid-token'
    authStore.user = { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } as never

    const to = makeTo({ requiresAuth: true })
    await setupAuthGuard(to, from, next)
    vi.clearAllMocks()

    const next2 = vi.fn()
    await setupAuthGuard(to, from, next2)

    expect(next2).toHaveBeenCalledWith()
    expect(authApi.me).not.toHaveBeenCalled()
  })

  it('does not call fetchMe twice simultaneously', async () => {
    let resolveMe!: (v: unknown) => void
    vi.mocked(authApi.me).mockImplementation(
      () =>
        new Promise((resolve) => {
          resolveMe = resolve as (v: unknown) => void
        })
    )

    const authStore = useAuthStore()
    authStore.token = 'valid-token'
    const to = makeTo({ requiresAuth: true })

    const next1 = vi.fn()
    const next2 = vi.fn()

    const p1 = setupAuthGuard(to, from, next1)
    const p2 = setupAuthGuard(to, from, next2)

    resolveMe({
      data: { success: true, data: { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } },
    })

    await Promise.all([p1, p2])

    expect(authApi.me).toHaveBeenCalledTimes(1)
    expect(next1).toHaveBeenCalledWith()
    expect(next2).toHaveBeenCalledWith()
  })

  it('redirects to login if fetchMe fails and token was cleared (401 interceptor)', async () => {
    const authStore = useAuthStore()
    authStore.token = 'valid-token'

    vi.mocked(authApi.me).mockImplementation(() => {
      authStore.token = null
      return Promise.reject(new Error('Unauthorized'))
    })

    const to = makeTo({ requiresAuth: true })
    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith({ name: 'login' })
  })

  it('calls next if fetchMe fails but token still valid', async () => {
    vi.mocked(authApi.me).mockRejectedValue(new Error('Network error'))

    const authStore = useAuthStore()
    authStore.token = 'valid-token'
    const to = makeTo({ requiresAuth: true })

    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith()
  })

  it('calls next for non-protected non-guest routes', async () => {
    const to = makeTo({})
    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith()
    expect(next).toHaveBeenCalledTimes(1)
  })
})
