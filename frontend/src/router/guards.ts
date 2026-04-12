import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

/** Evita múltiplas chamadas simultâneas a fetchMe (dedup entre guards) */
let fetchMePromise: Promise<void> | null = null

/** Limpa apenas a promise deduplicada (testes / hot reload) */
export function resetBackgroundRefresh() {
  fetchMePromise = null
}

async function awaitSessionValidation(authStore: ReturnType<typeof useAuthStore>): Promise<void> {
  if (!authStore.token) return
  if (authStore.sessionValidated) return
  if (!fetchMePromise) {
    fetchMePromise = authStore.fetchMe().finally(() => {
      fetchMePromise = null
    })
  }
  await fetchMePromise
}

/**
 * Guard de autenticação.
 * requiresAuth sem token → redirect login; guest com token → redirect dashboard.
 * Com token e sessão ainda não validada (sessionValidated) → await fetchMe antes de prosseguir.
 */
export async function setupAuthGuard(
  to: RouteLocationNormalized,
  _from: RouteLocationNormalized,
  next: NavigationGuardNext
): Promise<void> {
  const authStore = useAuthStore()

  /** Rota protegida sem autenticação → login */
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' })
    return
  }

  /** Token presente mas sessão não validada na API (ex.: JWT morto + user em cache) */
  if (authStore.token && !authStore.sessionValidated) {
    try {
      await awaitSessionValidation(authStore)
    } catch {
      /* fetchMe já tratou via interceptor */
    }
    if (!authStore.isAuthenticated) {
      if (to.meta.requiresAuth) {
        next({ name: 'login' })
        return
      }
      next()
      return
    }
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

  next()
}
