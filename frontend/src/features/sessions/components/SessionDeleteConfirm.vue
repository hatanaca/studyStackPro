<script setup lang="ts">
defineProps<{
  sessionName: string
  loading: boolean
}>()

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <div class="delete-confirm">
    <p class="delete-confirm__msg">
      Tem certeza que deseja excluir a sessão
      <strong>{{ sessionName || 'estudo' }}</strong
      >?
    </p>
    <p class="delete-confirm__hint">Esta ação não pode ser desfeita.</p>
    <div class="delete-confirm__actions">
      <button
        type="button"
        class="delete-confirm__btn delete-confirm__btn--danger"
        :disabled="loading"
        @click="emit('confirm')"
      >
        {{ loading ? 'Excluindo...' : 'Excluir' }}
      </button>
      <button type="button" class="delete-confirm__btn" @click="emit('cancel')">Cancelar</button>
    </div>
  </div>
</template>

<style scoped>
.delete-confirm__msg {
  margin: 0 0 var(--spacing-sm);
  color: var(--color-text);
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
}
.delete-confirm__hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
}
.delete-confirm__actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.delete-confirm__btn {
  min-height: var(--touch-target-min);
  padding: var(--spacing-sm) var(--spacing-lg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: var(--text-sm);
  font-weight: 500;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition:
    border-color var(--duration-fast) ease,
    background var(--duration-fast) ease,
    color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.delete-confirm__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.delete-confirm__btn--danger:focus-visible {
  outline: none;
  box-shadow: var(--form-input-shadow-error);
}
.delete-confirm__btn:hover {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.delete-confirm__btn--danger {
  background: var(--color-error);
  border-color: var(--color-error);
  color: var(--color-primary-contrast);
}
.delete-confirm__btn--danger:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-error) 88%, var(--color-bg));
  border-color: color-mix(in srgb, var(--color-error) 88%, var(--color-bg));
}
.delete-confirm__btn--danger:disabled {
  opacity: var(--state-disabled-opacity);
  cursor: not-allowed;
}
</style>
