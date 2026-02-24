import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/domain.types'
import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/types/api.types'

const TOKEN_KEY = 'studytrack_token'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  async function login(email: string, password: string) {
    const { data } = await apiClient.post<ApiResponse<{ user: User; token: string; token_type: string }>>('/auth/login', {
      email,
      password
    })
    if (data.success && data.data) {
      token.value = data.data.token
      user.value = data.data.user
      localStorage.setItem(TOKEN_KEY, data.data.token)
    }
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string, timezone = 'UTC') {
    const { data } = await apiClient.post<ApiResponse<User>>('/auth/register', {
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
    const { data } = await apiClient.get<ApiResponse<User>>('/auth/me')
    if (data.success && data.data) {
      user.value = data.data
    }
  }

  async function logout() {
    try {
      await apiClient.post('/auth/logout')
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem(TOKEN_KEY)
    }
  }

  return { token, user, isAuthenticated, login, register, fetchMe, logout }
})
