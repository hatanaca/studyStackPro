<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { authApi } from '@/api/modules/auth.api'
import { fetchSanctumCsrfCookie } from '@/api/sanctum'
import type { User } from '@/types/domain.types'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  // Lê parâmetros do fragment (#) em vez de query string por segurança.
  // Fragmentos não são enviados ao servidor (logs, Referer headers).
  const fragment = window.location.hash.substring(1)
  const fragmentParams = Object.fromEntries(
    fragment.split('&').map((p) => {
      const [k, ...v] = p.split('=')
      return [k, decodeURIComponent(v.join('='))]
    })
  )
  const status = fragmentParams.status ?? null
  const queryError = fragmentParams.error ?? null
  const token = fragmentParams.token ?? null

  if (queryError) {
    error.value = 'Falha na autenticação com o provedor. Tente novamente.'
    loading.value = false
    return
  }

  if (status !== 'ok') {
    error.value = 'Parâmetros de autenticação inválidos.'
    loading.value = false
    return
  }

  if (!token || typeof token !== 'string') {
    error.value = 'Token OAuth ausente. Tente novamente.'
    loading.value = false
    return
  }

  try {
    // Garante cookie XSRF-TOKEN antes do POST stateful
    await fetchSanctumCsrfCookie()
    const { data } = await authApi.oauthComplete(token)
    if (data.success && data.data) {
      const { user: u, token: bearerToken } = data.data as { user: User; token?: string }
      authStore.updateUser(u)
      authStore.sessionValidated = true
      if (bearerToken) authStore.storeToken(bearerToken)
      router.replace({ name: 'dashboard' })
    } else {
      error.value = 'Não foi possível validar a sessão. Tente novamente.'
    }
  } catch (e) {
    console.warn('[AuthCallback] OAuth complete failed:', e)
    error.value = 'Não foi possível validar a sessão. Tente novamente.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="auth-callback">
    <div class="auth-callback__card">
      <div v-if="loading" class="auth-callback__loading">
        <div class="auth-callback__spinner" aria-hidden="true" />
        <p>Finalizando autenticação...</p>
      </div>
      <div v-else-if="error" class="auth-callback__error">
        <h2>Falha no login</h2>
        <p>{{ error }}</p>
        <router-link to="/login" class="auth-callback__retry">Tentar novamente</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-callback {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: var(--color-bg);
}
.auth-callback__card {
  background: var(--color-bg-card);
  border-radius: var(--radius-xl);
  padding: var(--spacing-2xl) var(--spacing-3xl);
  text-align: center;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--color-border);
  max-width: 420px;
  width: 100%;
}
.auth-callback__spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto var(--spacing-md);
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
@media (prefers-reduced-motion: reduce) {
  .auth-callback__spinner {
    animation: none;
  }
}
.auth-callback__error h2 {
  color: var(--color-danger, #ef4444);
  margin: 0 0 var(--spacing-sm);
  font-size: var(--text-lg);
}
.auth-callback__error p {
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-lg);
}
.auth-callback__retry {
  display: inline-block;
  margin-top: var(--spacing-lg);
  padding: var(--spacing-sm) var(--spacing-xl);
  background: var(--color-primary);
  color: var(--color-primary-contrast, #fff);
  border-radius: var(--radius-md);
  text-decoration: none;
  font-weight: 600;
  transition: background var(--duration-fast) ease;
}
.auth-callback__retry:hover {
  background: var(--color-primary-hover);
}
</style>
