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
  submit: [payload: { name: string; email: string; password: string; password_confirmation: string; timezone?: string }]
}>()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const errors = ref<{ name?: string; email?: string; password?: string; password_confirmation?: string }>({})

const nameError = computed(() => errors.value.name)
const emailError = computed(() => errors.value.email)
const passwordError = computed(() => errors.value.password)
const passwordConfirmationError = computed(() => errors.value.password_confirmation)

function validate(): boolean {
  const e: typeof errors.value = {}
  if (!name.value.trim()) {
    e.name = 'Nome é obrigatório'
  }
  if (!email.value.trim()) {
    e.email = 'E-mail é obrigatório'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    e.email = 'E-mail inválido'
  }
  if (!password.value) {
    e.password = 'Senha é obrigatória'
  } else if (password.value.length < 8) {
    e.password = 'Senha deve ter pelo menos 8 caracteres'
  }
  if (password.value !== passwordConfirmation.value) {
    e.password_confirmation = 'Senhas não conferem'
  }
  errors.value = e
  return Object.keys(e).length === 0
}

function onSubmit() {
  if (!validate()) return
  emit('submit', {
    name: name.value.trim(),
    email: email.value.trim(),
    password: password.value,
    password_confirmation: passwordConfirmation.value
  })
}

defineExpose({
  setError: (msg: string) => { errors.value = { email: msg } },
  clearErrors: () => { errors.value = {} }
})
</script>

<template>
  <form
    class="register-form"
    @submit.prevent="onSubmit"
  >
    <div class="p-field">
      <label for="reg-name">Nome</label>
      <InputText
        id="reg-name"
        v-model="name"
        type="text"
        placeholder="Nome"
        autocomplete="name"
        class="w-full"
        :class="{ 'p-invalid': nameError }"
      />
      <small v-if="nameError" class="p-error">{{ nameError }}</small>
    </div>
    <div class="p-field">
      <label for="reg-email">E-mail</label>
      <InputText
        id="reg-email"
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
      <label for="reg-password">Senha</label>
      <InputText
        id="reg-password"
        v-model="password"
        type="password"
        placeholder="Senha (mín. 8 caracteres)"
        autocomplete="new-password"
        class="w-full"
        :class="{ 'p-invalid': passwordError }"
      />
      <small v-if="passwordError" class="p-error">{{ passwordError }}</small>
    </div>
    <div class="p-field">
      <label for="reg-password-confirm">Confirmar senha</label>
      <InputText
        id="reg-password-confirm"
        v-model="passwordConfirmation"
        type="password"
        placeholder="Confirmar senha"
        autocomplete="new-password"
        class="w-full"
        :class="{ 'p-invalid': passwordConfirmationError }"
      />
      <small v-if="passwordConfirmationError" class="p-error">{{ passwordConfirmationError }}</small>
    </div>
    <Button
      type="submit"
      :label="props.loading ? 'Registrando...' : 'Registrar'"
      :loading="props.loading"
      class="w-full"
    />
  </form>
</template>

<style scoped>
.register-form {
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
