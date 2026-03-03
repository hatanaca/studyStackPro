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
    const err = e as { response?: { data?: { error?: { message?: string }; message?: string }; status?: number }; message?: string }
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
    <p class="subtitle">
      Entrar
    </p>
    <LoginForm
      ref="loginFormRef"
      :loading="loading"
      @submit="onSubmit"
    />
    <p class="footer">
      Não tem conta? <router-link to="/register">
        Registrar
      </router-link>
    </p>
  </AuthLayout>
</template>

<style scoped>
h1 {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}
.subtitle {
  color: #64748b;
  margin-bottom: 1.5rem;
}
.footer {
  margin-top: 1rem;
  font-size: 0.875rem;
  color: #64748b;
}
.footer a {
  color: #3b82f6;
}
</style>
