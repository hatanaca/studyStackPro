import { ref, onMounted, onUnmounted, type Ref } from 'vue'

export interface UseIntersectionObserverOptions {
  root?: Element | null
  rootMargin?: string
  threshold?: number
}

/**
 * Observa um elemento e expõe se está visível no viewport.
 */
export function useIntersectionObserver(
  target: Ref<HTMLElement | null>,
  options: UseIntersectionObserverOptions = {}
) {
  const isIntersecting = ref(false)
  let observer: IntersectionObserver | null = null

  onMounted(() => {
    if (!target.value || typeof IntersectionObserver === 'undefined') return
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          isIntersecting.value = entry.isIntersecting
        })
      },
      {
        root: options.root ?? null,
        rootMargin: options.rootMargin ?? '0px',
        threshold: options.threshold ?? 0,
      }
    )
    observer.observe(target.value)
  })

  onUnmounted(() => {
    if (observer && target.value) {
      observer.unobserve(target.value)
      observer.disconnect()
      observer = null
    }
  })

  return { isIntersecting }
}
