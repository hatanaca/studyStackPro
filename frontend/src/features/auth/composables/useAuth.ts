import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

/**
 * Composable de autenticação: login, register, logout e redirecionamento pós-auth
 */
export function useAuth() {
  const authStore = useAuthStore()
  const router = useRouter()

  async function login(email: string, password: string) {
    await authStore.login(email, password)
    router.push('/')
  }

  async function register(
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
    timezone = 'UTC'
  ) {
    await authStore.register(name, email, password, passwordConfirmation, timezone)
    router.push('/')
  }

  async function logout() {
    await authStore.logout()
    router.push('/login')
  }

  return {
    ...authStore,
    login,
    register,
    logout,
  }
}
