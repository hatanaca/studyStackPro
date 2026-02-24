<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import LoginForm from '@/components/forms/LoginForm.vue'

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
    const err = e as { response?: { data?: { error?: { message?: string }; message?: string } } }
    const msg = err?.response?.data?.error?.message ?? err?.response?.data?.message ?? 'Falha no login.'
    loginFormRef.value?.setError(msg)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <h1>StudyTrack Pro</h1>
      <p class="subtitle">Entrar</p>
      <LoginForm ref="loginFormRef" :loading="loading" @submit="onSubmit" />
      <p class="footer">
        Não tem conta? <router-link to="/register">Registrar</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
}
.auth-card {
  background: #fff;
  padding: 2rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 360px;
}
.auth-card h1 {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}
.subtitle {
  color: #64748b;
  margin-bottom: 1.5rem;
}
.auth-card input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  box-sizing: border-box;
}
.auth-card button {
  width: 100%;
  padding: 0.6rem;
  background: #3b82f6;
  color: #fff;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}
.auth-card button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
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
