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
    <Transition name="fade">
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
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.base-modal {
  background: #fff;
  border-radius: 0.5rem;
  max-width: 90vw;
  max-height: 90vh;
  overflow: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
.base-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}
.base-modal__title {
  font-size: 1.125rem;
  margin: 0;
}
.base-modal__close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
  line-height: 1;
}
.base-modal__close:hover {
  color: #1e293b;
}
.base-modal__body {
  padding: 1.5rem;
}
</style>
