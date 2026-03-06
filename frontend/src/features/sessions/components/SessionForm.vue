<script setup lang="ts">
defineProps<{
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [payload: { technology_id: string; started_at: string; ended_at?: string; notes?: string; mood?: number }]
}>()

function onSubmit(e: Event) {
  e.preventDefault()
  const form = e.target as HTMLFormElement
  const fd = new FormData(form)
  emit('submit', {
    technology_id: fd.get('technology_id') as string,
    started_at: fd.get('started_at') as string,
    ended_at: (fd.get('ended_at') as string) || undefined,
    notes: (fd.get('notes') as string) || undefined,
    mood: fd.get('mood') ? Number(fd.get('mood')) : undefined
  })
}
</script>

<template>
  <form
    class="session-form"
    @submit="onSubmit"
  >
    <slot />
    <button
      type="submit"
      :disabled="loading"
    >
      {{ loading ? 'Salvando...' : 'Salvar sessão' }}
    </button>
  </form>
</template>

<style scoped>
.session-form button {
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  font-size: var(--text-sm);
  font-weight: 600;
  background: var(--color-primary);
  color: var(--color-primary-contrast, #fff);
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: background var(--duration-fast) ease, transform var(--duration-fast) ease;
}
.session-form button:hover:not(:disabled) {
  background: var(--color-primary-hover);
}
.session-form button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
