import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/domain.types'
import { authApi } from '@/api/modules/auth.api'
import { useSessionsStore } from '@/stores/sessions.store'

/** Chave do localStorage para o token JWT */
const TOKEN_KEY = 'studytrack_token'
/** Chave do localStorage para cache do usuário (evita fetch inicial) */
const USER_KEY = 'studytrack_user'

/** Carrega usuário do cache local (para restauração após refresh) */
function loadCachedUser(): User | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? (JSON.parse(raw) as User) : null
  } catch {
    return null
  }
}

/**
 * Store de autenticação. Gerencia token, usuário e persistência em localStorage.
 * login, register, logout, fetchMe, updateUser.
 */
export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const user = ref<User | null>(loadCachedUser())
  /**
   * True após login/register ou GET /auth/me com sucesso.
   * TanStack Query e outros devem usar isso em `enabled` para não disparar antes do guard validar o JWT.
   */
  const sessionValidated = ref(false)

  /** Computed: true se há token (usuário considerado autenticado) */
  const isAuthenticated = computed(() => !!token.value)

  /** Autentica e persiste token + user no store e localStorage */
  async function login(email: string, password: string) {
    const { data } = await authApi.login(email, password)
    if (data.success && data.data) {
      const { user: u, token: t } = data.data
      token.value = t
      user.value = u
      localStorage.setItem(TOKEN_KEY, t)
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
    const { data } = await authApi.register({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
      timezone,
    })
    if (data.success && data.data) {
      const { user: u, token: t } = data.data
      token.value = t
      user.value = u
      localStorage.setItem(TOKEN_KEY, t)
      localStorage.setItem(USER_KEY, JSON.stringify(u))
      sessionValidated.value = true
    } else {
      throw new Error(
        (data as unknown as { error?: { message?: string } }).error?.message ??
          'Falha no cadastro. Verifique os dados.'
      )
    }
  }

  /** Atualiza dados do usuário na API e sincroniza cache local */
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
        // Token inválido — interceptor já limpa
      } else if (token.value) {
        // Erro de rede com token — registrar listener de reconexão
        registerOnlineRecovery()
      }
      throw e
    }
  }

  /** Atualiza usuário no store e localStorage (sem chamar API) */
  function updateUser(updated: User) {
    user.value = updated
    localStorage.setItem(USER_KEY, JSON.stringify(updated))
  }

  /**
   * Limpa sessão local sem chamar a API (usado no 401 para evitar loop:
   * logout → 401 → logout → … e saturação do PHP-FPM).
   */
  function clearSessionLocally() {
    token.value = null
    user.value = null
    sessionValidated.value = false
    localStorage.removeItem(TOKEN_KEY)
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
      if (token.value && !sessionValidated.value) {
        fetchMe().catch(() => {
          /* retry silencioso */
        })
      }
      window.removeEventListener('online', onlineHandler!)
      onlineHandler = null
    }
    window.addEventListener('online', onlineHandler)
  }

  /** Revoga token na API e limpa store + localStorage */
  async function logout() {
    const hadToken = !!token.value
    try {
      if (hadToken) {
        await authApi.logout()
      }
    } catch {
      /* rede / token já inválido */
    } finally {
      clearSessionLocally()
    }
  }

  return {
    token,
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
