import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/domain.types'
import { authApi } from '@/api/modules/auth.api'
import { fetchSanctumCsrfCookie } from '@/api/sanctum'
import { useSessionsStore } from '@/stores/sessions.store'
import { getLocale, t } from '@/locales'

const USER_KEY = 'studytrack_user'
const TOKEN_KEY = 'studytrack_token'
const CACHE_TTL_MS = 24 * 60 * 60 * 1000
interface CachedUser {
  user: User
  ts: number
}

function loadCachedUser(): User | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    if (!raw) return null
    const cached = JSON.parse(raw) as CachedUser | User
    if ('ts' in cached && 'user' in cached) {
      if (Date.now() - cached.ts > CACHE_TTL_MS) {
        localStorage.removeItem(USER_KEY)
        return null
      }
      return (cached as CachedUser).user
    }
    return cached as User
  } catch {
    return null
  }
}

export function getStoredToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(loadCachedUser())
  const sessionValidated = ref(false)
  const isAuthenticated = computed(() => sessionValidated.value && !!user.value)
  function cacheUser(u: User) {
    localStorage.setItem(USER_KEY, JSON.stringify({ user: u, ts: Date.now() }))
  }

  function storeToken(token: string) {
    localStorage.setItem(TOKEN_KEY, token)
  }
  function clearToken() {
    localStorage.removeItem(TOKEN_KEY)
  }

  async function login(email: string, password: string) {
    await fetchSanctumCsrfCookie()
    const { data } = await authApi.login(email, password)
    if (data.success && data.data) {
      const { user: u, token } = data.data as { user: User; token?: string }
      user.value = u
      cacheUser(u)
      sessionValidated.value = true
      if (token) storeToken(token)
    } else {
      throw new Error(
        (data as unknown as { error?: { message?: string } }).error?.message ??
          t(getLocale(), 'auth.invalidCredentials')
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
      const { user: u, token } = data.data as { user: User; token?: string }
      user.value = u
      cacheUser(u)
      sessionValidated.value = true
      if (token) storeToken(token)
    } else {
      throw new Error(
        (data as unknown as { error?: { message?: string } }).error?.message ??
          t(getLocale(), 'auth.registerFailed')
      )
    }
  }

  async function fetchMe() {
    try {
      const { data } = await authApi.me()
      if (data.success && data.data) {
        user.value = data.data
        cacheUser(data.data)
        sessionValidated.value = true
      }
    } catch (e) {
      const status = (e as { response?: { status?: number } })?.response?.status
      if (status === 401) {
        user.value = null
        sessionValidated.value = false
        clearToken()
        localStorage.removeItem(USER_KEY)
      } else {
        user.value = null
        registerOnlineRecovery()
      }
      throw e
    }
  }

  function updateUser(updated: User) {
    user.value = updated
    cacheUser(updated)
  }

  function clearSessionLocally() {
    user.value = null
    sessionValidated.value = false
    clearToken()
    localStorage.removeItem(USER_KEY)
    try {
      useSessionsStore().$reset()
    } catch {
      /* ok */
    }
    if (onlineHandler) {
      window.removeEventListener('online', onlineHandler)
      onlineHandler = null
    }
  }

  let onlineHandler: (() => void) | null = null
  function registerOnlineRecovery() {
    if (onlineHandler) return
    onlineHandler = async () => {
      if (!sessionValidated.value) {
        try {
          await fetchMe()
        } catch {
          /* */
        }
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
      /* */
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
    storeToken,
  }
})
