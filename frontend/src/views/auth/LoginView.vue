<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import LoginForm from '@/features/auth/components/LoginForm.vue'
import { getLocale, t } from '@/locales'

const loading = ref(false)
const loginFormRef = ref<InstanceType<typeof LoginForm> | null>(null)
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const oauthError = ref<string | null>(null)

const apiOrigin = String(import.meta.env.VITE_API_URL ?? '')

onMounted(() => {
  const errorParam = route.query.error
  if (errorParam === 'oauth_failed') {
    oauthError.value = t(getLocale(), 'auth.oauthError')
  }
})

function loginWith(provider: string) {
  window.location.href = `${apiOrigin}/api/v1/auth/${provider}`
}

async function onSubmit(payload: { email: string; password: string }) {
  loginFormRef.value?.clearErrors()
  loading.value = true
  try {
    await authStore.login(payload.email, payload.password)
    router.push('/')
  } catch (e: unknown) {
    const err = e as {
      response?: { data?: { error?: { message?: string }; message?: string }; status?: number }
      message?: string
    }
    const msg =
      err?.response?.data?.error?.message ??
      err?.response?.data?.message ??
      (typeof err?.message === 'string' ? err.message : 'Falha no login.')
    loginFormRef.value?.setError(msg)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <h1>StudyTrack Pro</h1>
    <p class="subtitle">Entrar</p>

    <div v-if="oauthError" class="oauth-error">
      <p>{{ oauthError }}</p>
    </div>

    <LoginForm ref="loginFormRef" :loading="loading" @submit="onSubmit" />

    <div class="oauth-divider">
      <span>ou continue com</span>
    </div>

    <div class="oauth-buttons">
      <button
        type="button"
        class="oauth-btn oauth-btn--google"
        aria-label="Entrar com Google"
        @click="loginWith('google')"
      >
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
          <path
            fill="#4285F4"
            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"
          />
          <path
            fill="#34A853"
            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
          />
          <path
            fill="#FBBC05"
            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
          />
          <path
            fill="#EA4335"
            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
          />
        </svg>
        Google
      </button>
      <button
        type="button"
        class="oauth-btn oauth-btn--discord"
        aria-label="Entrar com Discord"
        @click="loginWith('discord')"
      >
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
          <path
            fill="#5865F2"
            d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.947 2.418-2.157 2.418z"
          />
        </svg>
        Discord
      </button>
    </div>

    <p class="footer">Não tem conta? <router-link to="/register"> Registrar </router-link></p>
  </AuthLayout>
</template>

<style scoped>
h1 {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 600;
  letter-spacing: var(--tracking-wide);
  text-transform: uppercase;
  line-height: var(--leading-tight);
  margin: 0 0 var(--spacing-sm);
  color: var(--color-accent);
}
.subtitle {
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-xl);
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-tight);
}
.footer {
  margin-top: var(--spacing-xl);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-normal);
}
.footer a {
  color: var(--color-primary);
  font-weight: 600;
  text-decoration: none;
  transition: color var(--duration-fast) ease;
}
.footer a:hover {
  color: var(--color-primary-hover);
  text-decoration: underline;
}
.footer a:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-radius: var(--radius-sm);
}

.oauth-divider {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin: var(--spacing-lg) 0;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}
.oauth-divider::before,
.oauth-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--color-border);
}
.oauth-buttons {
  display: flex;
  gap: var(--spacing-md);
}
.oauth-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
  font-weight: 500;
  font-size: var(--text-sm);
  cursor: pointer;
  transition:
    background var(--duration-fast) ease,
    border-color var(--duration-fast) ease,
    transform var(--duration-fast) ease;
}
.oauth-btn:hover {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
  transform: translateY(-1px);
}
.oauth-btn:active {
  transform: scale(0.98);
}
.oauth-btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.oauth-error {
  background: var(--color-error-soft);
  border: 1px solid color-mix(in srgb, var(--color-error) 40%, transparent);
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  color: var(--color-on-error-soft);
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
}
</style>
