import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

/** Evita múltiplas chamadas simultâneas a fetchMe (dedup entre guards) */
let fetchMePromise: Promise<void> | null = null

/** Limpa apenas a promise deduplicada (testes / hot reload) */
export function resetBackgroundRefresh() {
  fetchMePromise = null
}

async function awaitSessionValidation(authStore: ReturnType<typeof useAuthStore>): Promise<void> {
  if (!authStore.user) return
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
 * Hidrata sessão com /auth/me quando há utilizador em cache mas sessionValidated ainda é false.
 */
export async function setupAuthGuard(
  to: RouteLocationNormalized,
  _from: RouteLocationNormalized,
  next: NavigationGuardNext
): Promise<void> {
  const authStore = useAuthStore()

  if (authStore.user && !authStore.sessionValidated) {
    try {
      await awaitSessionValidation(authStore)
    } catch {
      /* fetchMe / interceptor tratam 401 */
    }
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' })
    return
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

  next()
}
