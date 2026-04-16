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
  setError: (msg: string) => {
    errors.value = { password: msg }
  },
  clearErrors: () => {
    errors.value = {}
  },
})
</script>

<template>
  <form class="login-form" @submit.prevent="onSubmit">
    <BaseInput
      id="login-email"
      v-model="email"
      type="email"
      label="E-mail"
      placeholder="E-mail"
      autocomplete="email"
      :error="emailError"
    />
    <BaseInput
      id="login-password"
      v-model="password"
      type="password"
      label="Senha"
      placeholder="Senha"
      autocomplete="current-password"
      :error="passwordError"
    />
    <BaseButton
      type="submit"
      variant="primary"
      :disabled="props.loading"
      class="login-form__submit"
    >
      {{ props.loading ? 'Entrando...' : 'Entrar' }}
    </BaseButton>
  </form>
</template>

<style scoped>
.login-form {
  display: flex;
  flex-direction: column;
  gap: var(--form-section-gap);
}
.login-form__submit {
  width: 100%;
  min-height: var(--form-input-height);
  margin-top: var(--spacing-xs);
}
</style>
