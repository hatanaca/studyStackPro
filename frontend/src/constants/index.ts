/**
 * Constantes globais do frontend – StudyTrack Pro
 */

/** Breakpoints em pixels (espelhando variables.css) */
export const BREAKPOINTS = {
  xs: 480,
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
} as const

/** Chaves de localStorage usadas pela aplicação */
export const STORAGE_KEYS = {
  theme: 'studytrack.theme',
  onboardingDismissed: 'studytrack.onboarding.dismissed',
} as const

/** Períodos disponíveis para séries temporais */
export const TIME_SERIES_PERIODS = ['7d', '30d', '90d'] as const
export type TimeSeriesPeriodKey = (typeof TIME_SERIES_PERIODS)[number]

/** Labels para períodos (exibição) */
export const TIME_SERIES_LABELS: Record<TimeSeriesPeriodKey, string> = {
  '7d': '7 dias',
  '30d': '30 dias',
  '90d': '90 dias',
}

/** Limites de paginação padrão */
export const DEFAULT_PAGE_SIZE = 20
export const MAX_PAGE_SIZE = 100

/** Durações de toast (ms) */
export const TOAST_DURATION = {
  short: 3000,
  medium: 5000,
  long: 8000,
} as const

/** Intervalos de polling (ms) */
export const POLLING = {
  dashboard: 120_000,
  reconnectDelay: 5000,
} as const
