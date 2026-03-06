import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/domain.types'
import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'

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
    const { data } = await apiClient.post<ApiResponse<{ user: User; token: string; token_type: string }>>(ENDPOINTS.auth.login, {
      email,
      password
    })
    if (data.success && data.data) {
      token.value = data.data.token
      user.value = data.data.user
      localStorage.setItem(TOKEN_KEY, data.data.token)
      localStorage.setItem(USER_KEY, JSON.stringify(data.data.user))
    }
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string, timezone = 'UTC') {
    const { data } = await apiClient.post<ApiResponse<User>>(ENDPOINTS.auth.register, {
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
      timezone
    })
    if (data.success && data.data) {
      await login(email, password)
    }
  }

  async function fetchMe() {
    const { data } = await apiClient.get<ApiResponse<User>>(ENDPOINTS.auth.me)
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
      await apiClient.post(ENDPOINTS.auth.logout)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(USER_KEY)
    }
  }

  return { token, user, isAuthenticated, login, register, fetchMe, logout, updateUser }
})
