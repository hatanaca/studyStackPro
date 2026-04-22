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

  it('redirecciona para login quando a rota exige auth e não há sessão', async () => {
    const to = makeTo({ requiresAuth: true })
    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith({ name: 'login' })
  })

  it('redirecciona para o dashboard em rota guest com sessão já validada', async () => {
    const authStore = useAuthStore()
    authStore.user = { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } as never
    authStore.sessionValidated = true
    const to = makeTo({ guest: true })

    await setupAuthGuard(to, from, next)

    expect(authApi.me).not.toHaveBeenCalled()
    expect(next).toHaveBeenCalledWith({ name: 'dashboard' })
  })

  it('valida sessão com fetchMe quando há cache e rota exige auth', async () => {
    vi.mocked(authApi.me).mockResolvedValue({
      data: { success: true, data: { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } },
    } as never)

    const authStore = useAuthStore()
    authStore.user = { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } as never

    const to = makeTo({ requiresAuth: true })
    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith()
    expect(authApi.me).toHaveBeenCalledOnce()
  })

  it('não chama fetchMe na segunda navegação quando a sessão já está validada', async () => {
    vi.mocked(authApi.me).mockResolvedValue({
      data: { success: true, data: { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } },
    } as never)

    const authStore = useAuthStore()
    authStore.user = { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } as never

    const to = makeTo({ requiresAuth: true })
    await setupAuthGuard(to, from, next)
    vi.clearAllMocks()

    const next2 = vi.fn()
    await setupAuthGuard(to, from, next2)

    expect(next2).toHaveBeenCalledWith()
    expect(authApi.me).not.toHaveBeenCalled()
  })

  it('não dispara fetchMe em duplicado em navegações simultâneas', async () => {
    let resolveMe!: (v: unknown) => void
    vi.mocked(authApi.me).mockImplementation(
      () =>
        new Promise((resolve) => {
          resolveMe = resolve as (v: unknown) => void
        })
    )

    const authStore = useAuthStore()
    authStore.user = { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } as never
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

  it('redirecciona para login quando fetchMe falha sem sessão válida', async () => {
    vi.mocked(authApi.me).mockRejectedValue({ response: { status: 401 } })

    const authStore = useAuthStore()
    authStore.user = { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } as never

    const to = makeTo({ requiresAuth: true })
    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith({ name: 'login' })
  })

  it('redirecciona para login quando fetchMe falha por rede e a sessão não foi validada', async () => {
    vi.mocked(authApi.me).mockRejectedValue(new Error('Network error'))

    const authStore = useAuthStore()
    authStore.user = { id: 'u1', name: 'User', email: 'u@e.com', timezone: 'UTC' } as never
    const to = makeTo({ requiresAuth: true })

    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith({ name: 'login' })
  })

  it('chama next em rotas sem meta guest nem requiresAuth', async () => {
    const to = makeTo({})
    await setupAuthGuard(to, from, next)

    expect(next).toHaveBeenCalledWith()
    expect(next).toHaveBeenCalledTimes(1)
  })
})
