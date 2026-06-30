<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth.store'
import { authApi } from '@/api/modules/auth.api'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const form = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const errors = ref<Record<string, string>>({})

function validate(): boolean {
  const e: Record<string, string> = {}
  if (!form.value.current_password) e.current_password = 'Senha atual é obrigatória'
  if (!form.value.password) e.password = 'Nova senha é obrigatória'
  else if (form.value.password.length < 8) e.password = 'Mínimo 8 caracteres'
  else if (form.value.password !== form.value.password_confirmation) {
    e.password_confirmation = 'Confirmação não confere'
  }
  errors.value = e
  return Object.keys(e).length === 0
}

async function submit() {
  if (!validate()) return
  loading.value = true
  try {
    const { data } = await authApi.changePassword(form.value)
    if (data.success) {
      toast.success('Senha alterada. Você será desconectado.')
      authStore.logout()
      router.push('/login')
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { error?: { message?: string } } } }
    const msg = err.response?.data?.error?.message ?? 'Erro ao alterar senha'
    if (msg.toLowerCase().includes('incorreta')) {
      errors.value = { current_password: 'Senha atual incorreta' }
    } else {
      toast.error(msg)
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Card class="password-tab__card">
    <template #content>
      <h2 class="section-title">Alterar senha</h2>
      <p class="section-desc">
        Após alterar a senha, você será desconectado de todos os dispositivos.
      </p>
      <form class="profile-form" @submit.prevent="submit">
        <input
          type="text"
          name="username"
          autocomplete="username"
          style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;"
          tabindex="-1"
          aria-hidden="true"
        />
        <div class="p-field">
          <label>Senha atual</label>
          <InputText
            v-model="form.current_password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            class="w-full"
            :class="{ 'p-invalid': errors.current_password }"
          />
          <small v-if="errors.current_password" class="p-error">{{ errors.current_password }}</small>
        </div>
        <div class="p-field">
          <label>Nova senha</label>
          <InputText
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            autocomplete="new-password"
            class="w-full"
            :class="{ 'p-invalid': errors.password }"
          />
          <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
        </div>
        <div class="p-field">
          <label>Confirmar nova senha</label>
          <InputText
            v-model="form.password_confirmation"
            type="password"
            placeholder="••••••••"
            autocomplete="new-password"
            class="w-full"
            :class="{ 'p-invalid': errors.password_confirmation }"
          />
          <small v-if="errors.password_confirmation" class="p-error">{{ errors.password_confirmation }}</small>
        </div>
        <Button
          type="submit"
          :label="loading ? 'Alterando...' : 'Alterar senha'"
          :loading="loading"
        />
      </form>
    </template>
  </Card>
</template>

<style scoped>
.password-tab__card :deep(.p-card-content) {
  padding: var(--spacing-lg) var(--spacing-xl);
}
.profile-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
.section-title {
  font-family: var(--font-display);
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.section-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
  line-height: var(--leading-normal);
}
.p-field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  margin-bottom: var(--spacing-lg);
}
.p-field label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-tight);
}
.w-full {
  width: 100%;
}
.profile-form :deep(.p-button) {
  min-height: var(--touch-target-min);
}
.profile-form :deep(.p-button:focus-visible),
.profile-form :deep(.p-inputtext:focus-visible) {
  outline: none;
  box-shadow: var(--shadow-focus);
}
</style>
