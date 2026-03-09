<script setup lang="ts">
import { ref, computed } from 'vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const props = withDefaults(
  defineProps<{
    loading?: boolean
  }>(),
  { loading: false }
)

const emit = defineEmits<{
  submit: [payload: { email: string; password: string }]
}>()

const email = ref('')
const password = ref('')

const errors = ref<{ email?: string; password?: string }>({})

const emailError = computed(() => errors.value.email)
const passwordError = computed(() => errors.value.password)

function validate(): boolean {
  const e: { email?: string; password?: string } = {}
  if (!email.value.trim()) {
    e.email = 'E-mail é obrigatório'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    e.email = 'E-mail inválido'
  }
  if (!password.value) {
    e.password = 'Senha é obrigatória'
  }
  errors.value = e
  return Object.keys(e).length === 0
}

function onSubmit() {
  if (!validate()) return
  emit('submit', { email: email.value.trim(), password: password.value })
}

defineExpose({
  setError: (msg: string) => { errors.value = { password: msg } },
  clearErrors: () => { errors.value = {} }
})
</script>

<template>
  <form
    class="login-form"
    @submit.prevent="onSubmit"
  >
    <div class="p-field">
      <label for="login-email">E-mail</label>
      <InputText
        id="login-email"
        v-model="email"
        type="email"
        placeholder="E-mail"
        autocomplete="email"
        class="w-full"
        :class="{ 'p-invalid': emailError }"
      />
      <small v-if="emailError" class="p-error">{{ emailError }}</small>
    </div>
    <div class="p-field">
      <label for="login-password">Senha</label>
      <InputText
        id="login-password"
        v-model="password"
        type="password"
        placeholder="Senha"
        autocomplete="current-password"
        class="w-full"
        :class="{ 'p-invalid': passwordError }"
      />
      <small v-if="passwordError" class="p-error">{{ passwordError }}</small>
    </div>
    <Button
      type="submit"
      :label="props.loading ? 'Entrando...' : 'Entrar'"
      :loading="props.loading"
      class="w-full"
    />
  </form>
</template>

<style scoped>
.login-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.p-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.p-field label { font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); }
.w-full { width: 100%; }
</style>
