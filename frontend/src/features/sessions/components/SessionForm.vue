<script setup lang="ts">
defineProps<{
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [payload: { technology_id: string; started_at: string; ended_at?: string; notes?: string; mood?: number }]
}>()

function getString(fd: FormData, key: string): string | undefined {
  const v = fd.get(key)
  return typeof v === 'string' ? v.trim() || undefined : undefined
}

function getNumber(fd: FormData, key: string): number | undefined {
  const s = getString(fd, key)
  if (s === undefined) return undefined
  const n = Number(s)
  return Number.isNaN(n) ? undefined : n
}

function onSubmit(e: Event) {
  e.preventDefault()
  const form = e.target as HTMLFormElement
  const fd = new FormData(form)
  const technology_id = getString(fd, 'technology_id')
  const started_at = getString(fd, 'started_at')
  if (!technology_id || !started_at) return
  emit('submit', {
    technology_id,
    started_at,
    ended_at: getString(fd, 'ended_at'),
    notes: getString(fd, 'notes'),
    mood: getNumber(fd, 'mood'),
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
