import axios, { type AxiosError } from 'axios'
import router from '@/router'
import { useAuthStore } from '@/stores/auth.store'

/** Base URL da API (v1). Usa VITE_API_URL ou string vazia para same-origin. */
const baseURL = `${import.meta.env.VITE_API_URL || ''}/api/v1`

/** Formato de erro da API (Laravel: success: false, error: { message }). */
interface ApiErrorBody {
  error?: { message?: string }
  message?: string
}

/** Extrai o body da resposta de erro (se AxiosError com response.data) */
function getErrorBody(error: unknown): ApiErrorBody | undefined {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as AxiosError<ApiErrorBody>).response
    const data = response?.data
    if (data && typeof data === 'object') return data
  }
  return undefined
}

/** Extrai mensagem de erro da resposta da API (formato { success: false, error: { message } }). */
export function getApiErrorMessage(error: unknown): string {
  const data = getErrorBody(error)
  if (data) {
    const msg = data.error?.message ?? data.message
    if (typeof msg === 'string') return msg
  }
  return 'Erro na comunicação com o servidor.'
}

/**
 * Cliente Axios para a API. Interceptors: injeta Bearer token; 401 → logout + redirect; 429 → toast.
 */
export const apiClient = axios.create({
  baseURL,
  withCredentials: false,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

/** Request interceptor: adiciona Authorization Bearer ao header se token existir */
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore().token
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

/** Tipo da função de toast para feedback de erros (ex: rate limit) */
type ToastFn = (msg: string, type?: 'success' | 'error' | 'info') => void

let toastFn: ToastFn | null = null

/** Registra callback de toast para o interceptor de resposta (401/429) */
export function setApiToast(fn: ToastFn) {
  toastFn = fn
}

/** Evita centenas de logouts/redirects paralelos quando várias APIs retornam 401 ao mesmo tempo */
let handlingUnauthorized = false

/** Response interceptor: 401 → limpa sessão local + /login (sem chamar API de logout em loop) */
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status
    const reqUrl = String(error.config?.url ?? '')

    if (status === 401) {
      if (
        reqUrl.includes('/auth/login') ||
        reqUrl.includes('/auth/register') ||
        handlingUnauthorized
      ) {
        return Promise.reject(error)
      }
      handlingUnauthorized = true
      try {
        useAuthStore().clearSessionLocally()
        if (router.currentRoute.value.name !== 'login') {
          await router.push({ name: 'login' })
        }
      } finally {
        setTimeout(() => {
          handlingUnauthorized = false
        }, 300)
      }
    } else if (status === 429) {
      const message = getApiErrorMessage(error) || 'Muitas requisições. Aguarde um momento e tente novamente.'
      if (toastFn) {
        toastFn(message, 'error')
      } else {
        console.warn('[API] Rate limit (429):', message)
      }
    }

    return Promise.reject(error)
  }
)
