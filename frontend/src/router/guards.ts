import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

/** Evita múltiplas chamadas simultâneas a fetchMe */
let fetchMePromise: Promise<void> | null = null
/** Flag para refresh em background (apenas uma vez após login) */
let hasDoneBackgroundRefresh = false

/**
 * Guard de autenticação.
 * requiresAuth sem token → redirect login; guest com token → redirect dashboard.
 * Se autenticado e user vazio → fetchMe antes de prosseguir.
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

  if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

  /** Autenticado: garantir user preenchido (fetchMe se vazio; refresh em background se já existe) */
  if (to.meta.requiresAuth && authStore.token) {
    if (!authStore.user) {
      if (!fetchMePromise) {
        fetchMePromise = authStore.fetchMe().finally(() => {
          fetchMePromise = null
        })
      }
      await fetchMePromise
    } else if (!hasDoneBackgroundRefresh) {
      hasDoneBackgroundRefresh = true
      authStore.fetchMe().catch(() => {})
    }
  }

  next()
}
