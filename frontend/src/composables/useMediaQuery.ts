import { ref, onMounted, onUnmounted } from 'vue'

/** Breakpoints do projeto (px) */
export const BREAKPOINTS = {
  xs: 480,
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
} as const

/**
 * Reage a uma media query (ex: "(min-width: 768px)").
 * Retorna ref<boolean> que indica se a query bate.
 */
export function useMediaQuery(query: string) {
  const matches = ref(false)
  let mediaQuery: MediaQueryList | null = null

  function update(e?: MediaQueryListEvent) {
    matches.value = e ? e.matches : (mediaQuery?.matches ?? false)
  }

  onMounted(() => {
    if (typeof window === 'undefined') return
    mediaQuery = window.matchMedia(query)
    matches.value = mediaQuery.matches
    mediaQuery.addEventListener('change', update)
  })

  onUnmounted(() => {
    mediaQuery?.removeEventListener('change', update)
  })

  return matches
}

/**
 * Atalhos para breakpoints comuns: isMobile, isTablet, isDesktop.
 */
export function useBreakpoints() {
  const isXs = useMediaQuery(`(max-width: ${BREAKPOINTS.xs - 1}px)`)
  const isSm = useMediaQuery(`(min-width: ${BREAKPOINTS.sm}px)`)
  const isMd = useMediaQuery(`(min-width: ${BREAKPOINTS.md}px)`)
  const isLg = useMediaQuery(`(min-width: ${BREAKPOINTS.lg}px)`)
  const isXl = useMediaQuery(`(min-width: ${BREAKPOINTS.xl}px)`)

  const isMobile = useMediaQuery(`(max-width: ${BREAKPOINTS.md - 1}px)`)
  const isTablet = useMediaQuery(
    `(min-width: ${BREAKPOINTS.sm}px) and (max-width: ${BREAKPOINTS.lg - 1}px)`
  )
  const isDesktop = useMediaQuery(`(min-width: ${BREAKPOINTS.lg}px)`)

  return {
    isXs,
    isSm,
    isMd,
    isLg,
    isXl,
    isMobile,
    isTablet,
    isDesktop,
  }
}
