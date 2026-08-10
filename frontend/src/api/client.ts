import axios from 'axios'
import { useAuthStore, getStoredToken } from '@/stores/auth.store'
import router from '@/router'

export const SESSION_NOT_READY = 'SESSION_NOT_READY'

const apiClient = axios.create({
  baseURL: `${import.meta.env.VITE_API_URL || ''}/api/v1`,
  withCredentials: true,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  withXSRFToken: true,
  timeout: 30000,
})

apiClient.interceptors.request.use((config) => {
  const token = getStoredToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

type ToastFn = (msg: string, type?: 'success' | 'error' | 'info') => void
let toastFn: ToastFn | null = null
export function setApiToast(fn: ToastFn) {
  toastFn = fn
}

let lastUnauthorizedRoute = ''
let lastUnauthorizedTime = 0
const UNAUTHORIZED_DEBOUNCE_MS = 300

function getApiErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.error?.message ?? error.response?.data?.message ?? error.message
  }
  if (typeof error === 'object' && error !== null && 'response' in error) {
    const resp = (error as { response?: { data?: Record<string, unknown> } }).response
    const data = resp?.data
    if (data && typeof data === 'object') {
      const errorMsg = data.error
      if (errorMsg && typeof errorMsg === 'object' && 'message' in errorMsg) {
        return (errorMsg as { message: string }).message
      }
      if (typeof data.message === 'string') {
        return data.message
      }
    }
  }
  return 'Erro na comunicação com o servidor.'
}

apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error?.__sessionNotReady) return Promise.reject(error)

    const status = error.response?.status
    const reqUrl = String(error.config?.url ?? '')

    if (status === 401) {
      if (
        reqUrl.includes('/auth/login') ||
        reqUrl.includes('/auth/register') ||
        reqUrl.includes('auth/logout')
      ) {
        return Promise.reject(error)
      }
      const routeName = router.currentRoute.value.name as string
      const now = Date.now()
      if (
        lastUnauthorizedRoute === routeName &&
        now - lastUnauthorizedTime < UNAUTHORIZED_DEBOUNCE_MS
      ) {
        return Promise.reject(error)
      }
      lastUnauthorizedRoute = routeName
      lastUnauthorizedTime = now
      try {
        useAuthStore().clearSessionLocally()
        if (routeName !== 'login') {
          await router.push({ name: 'login' })
        }
      } catch {
        /* ignore navigation errors */
      }
    } else if (status === 429) {
      const message =
        getApiErrorMessage(error) || 'Muitas requisições. Aguarde um momento e tente novamente.'
      if (toastFn) toastFn(message, 'error')
    } else if (status && status >= 500) {
      const message = getApiErrorMessage(error) || 'Erro interno do servidor.'
      if (toastFn) toastFn(message, 'error')

      // Capture 5xx API errors in Sentry for visibility
      if (import.meta.env.PROD && import.meta.env.VITE_SENTRY_DSN) {
        import('@sentry/vue')
          .then((Sentry) => {
            Sentry.captureException(error, {
              tags: { api_error: 'true', status: String(status) },
              extra: { url: reqUrl, method: error.config?.method },
            })
          })
          .catch(() => {})
      }
    }

    return Promise.reject(error)
  }
)

export function getApiErrorMessageExport(error: unknown): string {
  return getApiErrorMessage(error)
}

export { apiClient, getApiErrorMessage }

/**
 * Unwraps an Axios response with ApiResponse<T> envelope.
 * Returns the inner data directly, throwing on error responses.
 */
export async function unwrap<T>(
  promise: Promise<{ data: { success: boolean; data: T; message?: string } }>
): Promise<T> {
  const { data } = await promise
  if (!data.success) {
    throw new Error(data.message || 'Erro na comunicação com o servidor.')
  }
  return data.data
}
