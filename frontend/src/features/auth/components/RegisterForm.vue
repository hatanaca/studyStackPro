<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = withDefaults(
  defineProps<{
    loading?: boolean
  }>(),
  { loading: false }
)

const emit = defineEmits<{
  submit: [
    payload: {
      name: string
      email: string
      password: string
      password_confirmation: string
      timezone?: string
    },
  ]
}>()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const errors = ref<{
  name?: string
  email?: string
  password?: string
  password_confirmation?: string
}>({})

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
    password_confirmation: passwordConfirmation.value,
  })
}

defineExpose({
  setError: (msg: string) => {
    errors.value = { email: msg }
  },
  clearErrors: () => {
    errors.value = {}
  },
})
</script>

<template>
  <form class="register-form" @submit.prevent="onSubmit">
    <BaseInput
      id="reg-name"
      v-model="name"
      type="text"
      label="Nome"
      placeholder="Nome"
      autocomplete="name"
      :error="nameError"
    />
    <BaseInput
      id="reg-email"
      v-model="email"
      type="email"
      label="E-mail"
      placeholder="E-mail"
      autocomplete="email"
      :error="emailError"
    />
    <BaseInput
      id="reg-password"
      v-model="password"
      type="password"
      label="Senha"
      placeholder="Senha (mín. 8 caracteres)"
      autocomplete="new-password"
      :error="passwordError"
    />
    <BaseInput
      id="reg-password-confirm"
      v-model="passwordConfirmation"
      type="password"
      label="Confirmar senha"
      placeholder="Confirmar senha"
      autocomplete="new-password"
      :error="passwordConfirmationError"
    />
    <BaseButton
      type="submit"
      variant="primary"
      :disabled="props.loading"
      class="register-form__submit"
    >
      {{ props.loading ? 'Registrando...' : 'Registrar' }}
    </BaseButton>
  </form>
</template>

<style scoped>
.register-form {
  display: flex;
  flex-direction: column;
  gap: var(--form-section-gap);
}
.register-form__submit {
  width: 100%;
  min-height: var(--form-input-height);
  margin-top: var(--spacing-xs);
}
</style>
