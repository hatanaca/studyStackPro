import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/domain.types'
import { authApi } from '@/api/modules/auth.api'

const TOKEN_KEY = 'studytrack_token'
const USER_KEY = 'studytrack_user'

function loadCachedUser(): User | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? (JSON.parse(raw) as User) : null
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const user = ref<User | null>(loadCachedUser())

  const isAuthenticated = computed(() => !!token.value)

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

  async function fetchMe() {
    const { data } = await authApi.me()
    if (data.success && data.data) {
      user.value = data.data
      localStorage.setItem(USER_KEY, JSON.stringify(data.data))
    }
  }

  function updateUser(updated: User) {
    user.value = updated
    localStorage.setItem(USER_KEY, JSON.stringify(updated))
  }

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
