import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

let fetchMePromise: Promise<void> | null = null
let hasDoneBackgroundRefresh = false

export async function setupAuthGuard(
  to: RouteLocationNormalized,
  _from: RouteLocationNormalized,
  next: NavigationGuardNext
): Promise<void> {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' })
    return
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

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
