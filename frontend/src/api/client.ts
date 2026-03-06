import axios from 'axios'
import router from '@/router'
import { useAuthStore } from '@/stores/auth.store'

const baseURL = `${import.meta.env.VITE_API_URL || ''}/api/v1`

/** Extrai mensagem de erro da resposta da API (formato { success: false, error: { message } }). */
export function getApiErrorMessage(error: unknown): string {
  const data = error && typeof error === 'object' && 'response' in error
    ? (error as { response?: { data?: { error?: { message?: string }; message?: string } } }).response?.data
    : undefined
  if (data && typeof data === 'object') {
    const msg = (data as { error?: { message?: string }; message?: string }).error?.message
      ?? (data as { message?: string }).message
    if (typeof msg === 'string') return msg
  }
  return 'Erro na comunicação com o servidor.'
}

export const apiClient = axios.create({
  baseURL,
  withCredentials: false,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

apiClient.interceptors.request.use((config) => {
  const token = useAuthStore().token
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

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status

    if (status === 401) {
      useAuthStore().logout()
      router.push('/login')
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
