import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { useAuthStore } from '@/stores/auth.store'

/**
 * `enabled` para TanStack Query: só após o guard (ou login) marcar sessão validada.
 * Evita GET paralelos com JWT ainda não confirmado.
 */
export function useQuerySessionEnabled(extra?: MaybeRefOrGetter<boolean>) {
  const authStore = useAuthStore()
  return computed(() => {
    const on = extra === undefined ? true : toValue(extra)
    return authStore.sessionValidated && on
  })
}
