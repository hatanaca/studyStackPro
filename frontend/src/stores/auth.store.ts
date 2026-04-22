import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/domain.types'
import { authApi } from '@/api/modules/auth.api'
import { fetchSanctumCsrfCookie } from '@/api/sanctum'
import { useSessionsStore } from '@/stores/sessions.store'

/** Chave do localStorage apenas para cache do utilizador (não é autenticação). */
const USER_KEY = 'studytrack_user'

/** Carrega utilizador do cache local (evita flash vazio após refresh). */
function loadCachedUser(): User | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? (JSON.parse(raw) as User) : null
  } catch {
    return null
  }
}

/**
 * Store de autenticação (Sanctum SPA: sessão HttpOnly + cookie CSRF).
 * login/register obtêm CSRF antes do POST; o estado local guarda só o utilizador em cache.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(loadCachedUser())
  /**
   * True após login/register ou GET /auth/me com sucesso (sessão válida no servidor).
   */
  const sessionValidated = ref(false)

  const isAuthenticated = computed(() => sessionValidated.value && !!user.value)

  async function login(email: string, password: string) {
    await fetchSanctumCsrfCookie()
    const { data } = await authApi.login(email, password)
    if (data.success && data.data) {
      const { user: u } = data.data
      user.value = u
      localStorage.setItem(USER_KEY, JSON.stringify(u))
      sessionValidated.value = true
    } else {
      throw new Error(
        (data as unknown as { error?: { message?: string } }).error?.message ??
          'Credenciais inválidas.'
      )
    }
  }

  async function register(
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
    timezone = 'UTC'
  ) {
    await fetchSanctumCsrfCookie()
    const { data } = await authApi.register({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
      timezone,
    })
    if (data.success && data.data) {
      const { user: u } = data.data
      user.value = u
      localStorage.setItem(USER_KEY, JSON.stringify(u))
      sessionValidated.value = true
    } else {
      throw new Error(
        (data as unknown as { error?: { message?: string } }).error?.message ??
          'Falha no cadastro. Verifique os dados.'
      )
    }
  }

  async function fetchMe() {
    try {
      const { data } = await authApi.me()
      if (data.success && data.data) {
        user.value = data.data
        localStorage.setItem(USER_KEY, JSON.stringify(data.data))
        sessionValidated.value = true
      }
    } catch (e) {
      const status = (e as { response?: { status?: number } })?.response?.status
      if (status === 401) {
        /* Sessão inválida — interceptor já limpa */
      } else if (sessionValidated.value || user.value) {
        registerOnlineRecovery()
      }
      throw e
    }
  }

  function updateUser(updated: User) {
    user.value = updated
    localStorage.setItem(USER_KEY, JSON.stringify(updated))
  }

  function clearSessionLocally() {
    user.value = null
    sessionValidated.value = false
    localStorage.removeItem(USER_KEY)
    try {
      useSessionsStore().$reset()
    } catch {
      /* store não inicializada */
    }
    if (onlineHandler) {
      window.removeEventListener('online', onlineHandler)
      onlineHandler = null
    }
  }

  let onlineHandler: (() => void) | null = null

  function registerOnlineRecovery() {
    if (onlineHandler) return
    onlineHandler = () => {
      if (!sessionValidated.value) {
        fetchMe().catch(() => {
          /* retry silencioso */
        })
      }
      window.removeEventListener('online', onlineHandler!)
      onlineHandler = null
    }
    window.addEventListener('online', onlineHandler)
  }

  async function logout() {
    const hadSession = sessionValidated.value
    try {
      if (hadSession) {
        await authApi.logout()
      }
    } catch {
      /* rede / sessão já inválida */
    } finally {
      clearSessionLocally()
    }
  }

  return {
    user,
    sessionValidated,
    isAuthenticated,
    login,
    register,
    fetchMe,
    logout,
    clearSessionLocally,
    updateUser,
  }
})
