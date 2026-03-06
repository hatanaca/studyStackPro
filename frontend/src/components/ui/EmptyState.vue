<script setup lang="ts">
withDefaults(
  defineProps<{
    /** Título principal */
    title?: string
    /** Descrição / subtítulo */
    description?: string
    /** Nome do ícone (emoji ou 'chart', 'sessions', 'tech', 'search') */
    icon?: string
    /** Texto do botão de ação */
    actionLabel?: string
    /** Ocultar área de ação */
    hideAction?: boolean
  }>(),
  { title: 'Nenhum dado encontrado', description: '', icon: '📋', actionLabel: '', hideAction: true }
)

const emit = defineEmits<{
  action: []
}>()

function handleAction() {
  emit('action')
}
</script>

<template>
  <div class="empty-state">
    <div
      class="empty-state__icon"
      aria-hidden="true"
    >
      {{ icon }}
    </div>
    <h3 class="empty-state__title">
      {{ title }}
    </h3>
    <p
      v-if="description || $slots.description"
      class="empty-state__description"
    >
      <slot name="description">
        {{ description }}
      </slot>
    </p>
    <div
      v-if="(!hideAction && actionLabel) || $slots.action"
      class="empty-state__action"
    >
      <slot name="action">
        <button
          v-if="actionLabel"
          type="button"
          class="empty-state__button"
          @click="handleAction"
        >
          {{ actionLabel }}
        </button>
      </slot>
    </div>
  </div>
</template>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl) var(--spacing-lg);
  text-align: center;
  background: color-mix(in srgb, var(--color-bg-soft) 50%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
  min-height: 10rem;
}
.empty-state__icon {
  font-size: 2.25rem;
  line-height: 1;
  margin-bottom: var(--spacing-sm);
  opacity: 0.85;
}
.empty-state__title {
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
  letter-spacing: -0.01em;
}
.empty-state__description {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-md);
  max-width: 28rem;
  line-height: 1.5;
}
.empty-state__action {
  margin-top: var(--spacing-xs);
}
.empty-state__button {
  padding: 0.5rem 1rem;
  font-size: var(--text-sm);
  font-weight: 600;
  color: #fff;
  background: var(--color-primary);
  border: 1px solid var(--color-primary);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: background var(--duration-fast) ease, border-color var(--duration-fast) ease, transform var(--duration-fast) ease;
}
.empty-state__button:hover {
  background: var(--color-primary-hover);
  border-color: var(--color-primary-hover);
  transform: translateY(-1px);
}
</style>
