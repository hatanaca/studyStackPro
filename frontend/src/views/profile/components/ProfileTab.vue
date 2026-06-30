<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth.store'
import { authApi } from '@/api/modules/auth.api'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)
const form = ref({ name: '', timezone: 'UTC' })
const errors = ref<{ name?: string; timezone?: string }>({})

onMounted(() => {
  if (authStore.user) {
    form.value = {
      name: authStore.user.name,
      timezone: authStore.user.timezone ?? 'UTC',
    }
  }
})

async function save() {
  errors.value = {}
  if (!form.value.name.trim()) {
    errors.value.name = 'Nome é obrigatório'
    return
  }
  loading.value = true
  try {
    const { data } = await authApi.updateProfile({
      name: form.value.name,
      timezone: form.value.timezone,
    })
    if (data.success && data.data) {
      authStore.updateUser(data.data)
      toast.success('Perfil atualizado com sucesso')
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { error?: { details?: Record<string, string[]> } } } }
    const details = err.response?.data?.error?.details
    if (details) {
      errors.value = Object.fromEntries(
        Object.entries(details).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)])
      )
    } else {
      toast.error('Erro ao atualizar perfil')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Card class="profile-tab__card">
    <template #content>
      <h2 class="section-title">Dados do perfil</h2>
      <form class="profile-form" @submit.prevent="save">
        <div class="p-field">
          <label for="profile-name">Nome</label>
          <InputText
            id="profile-name"
            v-model="form.name"
            placeholder="Seu nome"
            class="w-full"
            :class="{ 'p-invalid': errors.name }"
          />
          <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
        </div>
        <div class="p-field">
          <label for="profile-timezone">Fuso horário</label>
          <InputText
            id="profile-timezone"
            v-model="form.timezone"
            placeholder="UTC"
            class="w-full"
            :class="{ 'p-invalid': errors.timezone }"
          />
          <small v-if="errors.timezone" class="p-error">{{ errors.timezone }}</small>
        </div>
        <Button
          type="submit"
          :label="loading ? 'Salvando...' : 'Salvar perfil'"
          :loading="loading"
        />
      </form>
    </template>
  </Card>
</template>

<style scoped>
.profile-tab__card :deep(.p-card-content) {
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
