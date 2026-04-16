<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import LoginForm from '@/features/auth/components/LoginForm.vue'

const loading = ref(false)
const loginFormRef = ref<InstanceType<typeof LoginForm> | null>(null)
const router = useRouter()
const authStore = useAuthStore()

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
    <LoginForm ref="loginFormRef" :loading="loading" @submit="onSubmit" />
    <p class="footer">Não tem conta? <router-link to="/register"> Registrar </router-link></p>
  </AuthLayout>
</template>

<style scoped>
h1 {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-weight: 700;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
  margin: 0 0 var(--spacing-sm);
  color: var(--color-text);
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
</style>
