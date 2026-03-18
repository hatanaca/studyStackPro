<script setup lang="ts">
defineProps<{
  show: boolean
  title?: string
}>()
defineEmits<{
  close: []
}>()
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="base-modal-overlay"
        @click.self="$emit('close')"
      >
        <div class="base-modal">
          <div
            v-if="title"
            class="base-modal__header"
          >
            <h3 class="base-modal__title">
              {{ title }}
            </h3>
            <button
              type="button"
              class="base-modal__close"
              aria-label="Fechar"
              @click="$emit('close')"
            >
              ×
            </button>
          </div>
          <div class="base-modal__body">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.base-modal-overlay {
  position: fixed;
  inset: 0;
  background: color-mix(in srgb, var(--color-bg) 55%, transparent);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: var(--spacing-lg);
}
.base-modal {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  max-width: 90vw;
  max-height: 90vh;
  overflow: auto;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--color-border);
  width: 100%;
}
@media (min-width: 640px) {
  .base-modal {
    width: auto;
    min-width: 360px;
  }
}
@media (max-width: 640px) {
  .base-modal {
    max-width: 95vw;
    max-height: 95vh;
  }
  .base-modal__body {
    padding: var(--widget-padding);
  }
  .base-modal__header {
    padding: var(--spacing-lg) var(--widget-padding);
  }
}
.base-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--widget-padding) var(--spacing-xl);
  border-bottom: 1px solid var(--color-border);
}
.base-modal__title {
  font-size: var(--text-lg);
  font-weight: 700;
  letter-spacing: var(--tracking-tight);
  margin: 0;
  color: var(--color-text);
}
.base-modal__close {
  width: var(--icon-size-lg);
  height: var(--icon-size-lg);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-lg);
  cursor: pointer;
  color: var(--color-text-muted);
  line-height: 1;
  transition: color var(--duration-fast) ease,
    border-color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.base-modal__close:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.base-modal__close:hover {
  color: var(--color-primary);
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
}
.base-modal__body {
  padding: var(--widget-padding) var(--spacing-xl);
}
</style>
