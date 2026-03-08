import { useMediaQuery as useMediaQueryVueUse } from '@vueuse/core'

/** Breakpoints do projeto (px) */
export const BREAKPOINTS = {
  xs: 480,
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
} as const

/**
 * Reage a uma media query (VueUse).
 * Retorna ref<boolean> que indica se a query bate.
 */
export function useMediaQuery(query: string) {
  return useMediaQueryVueUse(query)
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
