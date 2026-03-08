import { ref, type Ref } from 'vue'
import { useIntersectionObserver as useIntersectionObserverVueUse } from '@vueuse/core'

export interface UseIntersectionObserverOptions {
  root?: Element | null
  rootMargin?: string
  threshold?: number
}

/**
 * Observa um elemento e expõe se está visível no viewport (VueUse).
 */
export function useIntersectionObserver(
  target: Ref<HTMLElement | null>,
  options: UseIntersectionObserverOptions = {}
) {
  const isIntersecting = ref(false)
  useIntersectionObserverVueUse(
    target,
    ([entry]) => {
      isIntersecting.value = entry?.isIntersecting ?? false
    },
    {
      root: options.root ?? undefined,
      rootMargin: options.rootMargin ?? '0px',
      threshold: options.threshold ?? 0,
    } as import('@vueuse/core').UseIntersectionObserverOptions
  )
  return { isIntersecting }
}
