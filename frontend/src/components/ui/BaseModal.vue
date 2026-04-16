<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { useFocusTrap } from '@/composables/useFocusTrap'

const props = defineProps<{
  show: boolean
  title?: string
}>()

const uid = Math.random().toString(36).slice(2, 8)
const modalTitleId = `base-modal-title-${uid}`
const emit = defineEmits<{
  close: []
}>()

const overlayRef = ref<HTMLElement | null>(null)
const isActive = computed(() => props.show)

useFocusTrap(overlayRef, isActive)

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}

function lockScroll() {
  document.body.style.overflow = 'hidden'
}

function unlockScroll() {
  document.body.style.overflow = ''
}

watch(
  () => props.show,
  (val) => {
    if (val) lockScroll()
    else unlockScroll()
  }
)

onBeforeUnmount(() => {
  if (props.show) unlockScroll()
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        ref="overlayRef"
        class="base-modal-overlay"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="title ? modalTitleId : undefined"
        :aria-label="title ? undefined : 'Diálogo'"
        @click.self="$emit('close')"
        @keydown="onKeydown"
      >
        <div class="base-modal">
          <div v-if="title" class="base-modal__header">
            <h3 :id="modalTitleId" class="base-modal__title">
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
  z-index: var(--z-modal, 1000);
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
    min-width: var(--modal-min-width);
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
  transition:
    color var(--duration-fast) ease,
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
