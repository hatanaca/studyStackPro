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
  <form @submit.prevent="onSubmit" class="register-form">
    <BaseInput v-model="name" type="text" placeholder="Nome" label="Nome" :error="nameError" autocomplete="name" />
    <BaseInput
      v-model="email"
      type="email"
      placeholder="E-mail"
      label="E-mail"
      :error="emailError"
      autocomplete="email"
    />
    <BaseInput
      v-model="password"
      type="password"
      placeholder="Senha (mín. 8 caracteres)"
      label="Senha"
      :error="passwordError"
      autocomplete="new-password"
    />
    <BaseInput
      v-model="passwordConfirmation"
      type="password"
      placeholder="Confirmar senha"
      label="Confirmar senha"
      :error="passwordConfirmationError"
      autocomplete="new-password"
    />
    <BaseButton type="submit" :disabled="props.loading" class="w-full">
      {{ props.loading ? 'Registrando...' : 'Registrar' }}
    </BaseButton>
  </form>
</template>

<style scoped>
.register-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.w-full {
  width: 100%;
}
</style>
