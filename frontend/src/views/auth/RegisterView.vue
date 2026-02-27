<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import RegisterForm from '@/features/auth/components/RegisterForm.vue'

const loading = ref(false)
const registerFormRef = ref<InstanceType<typeof RegisterForm> | null>(null)
const router = useRouter()
const authStore = useAuthStore()

async function onSubmit(payload: {
  name: string
  email: string
  password: string
  password_confirmation: string
}) {
  registerFormRef.value?.clearErrors()
  loading.value = true
  try {
    await authStore.register(payload.name, payload.email, payload.password, payload.password_confirmation)
    router.push('/')
  } catch (e: unknown) {
    const err = e as { response?: { data?: { error?: { message?: string }; message?: string } } }
    const msg = err?.response?.data?.error?.message ?? err?.response?.data?.message ?? 'Falha no registro.'
    registerFormRef.value?.setError(msg)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <h1>StudyTrack Pro</h1>
    <p class="subtitle">
      Criar conta
    </p>
    <RegisterForm
      ref="registerFormRef"
      :loading="loading"
      @submit="onSubmit"
    />
    <p class="footer">
      Já tem conta? <router-link to="/login">
        Entrar
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
