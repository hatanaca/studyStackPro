import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/domain.types'
import { authApi } from '@/api/modules/auth.api'

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
    }
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string, timezone = 'UTC') {
    await authApi.register({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
      timezone,
    })
    await login(email, password)
  }

  /** Atualiza dados do usuário na API e sincroniza cache local */
  async function fetchMe() {
    const { data } = await authApi.me()
    if (data.success && data.data) {
      user.value = data.data
      localStorage.setItem(USER_KEY, JSON.stringify(data.data))
    }
  }

  /** Atualiza usuário no store e localStorage (sem chamar API) */
  function updateUser(updated: User) {
    user.value = updated
    localStorage.setItem(USER_KEY, JSON.stringify(updated))
  }

  /** Revoga token na API e limpa store + localStorage */
  async function logout() {
    try {
      await authApi.logout()
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(USER_KEY)
    }
  }

  return { token, user, isAuthenticated, login, register, fetchMe, logout, updateUser }
})
