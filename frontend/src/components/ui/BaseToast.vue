<script setup lang="ts">
import { useToast } from '@/composables/useToast'
import { setApiToast } from '@/api/client'
import { onMounted } from 'vue'

const { toasts, success, error, info, remove } = useToast()

onMounted(() => {
  setApiToast((msg, type = 'success') => {
    if (type === 'error') error(msg)
    else if (type === 'info') info(msg)
    else success(msg)
  })
})
</script>

<template>
  <div class="toast-container">
    <TransitionGroup name="toast">
      <div
        v-for="t in toasts"
        :key="t.id"
        class="toast"
        :class="[`toast--${t.type}`]"
        role="alert"
      >
        <span class="message">{{ t.message }}</span>
        <button
          class="close"
          aria-label="Fechar"
          @click="remove(t.id)"
        >
          ×
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-width: 360px;
}
.toast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.toast--success {
  background: #059669;
  color: white;
}
.toast--error {
  background: #dc2626;
  color: white;
}
.toast--info {
  background: #0284c7;
  color: white;
}
.message {
  flex: 1;
  font-size: 0.875rem;
}
.close {
  background: none;
  border: none;
  color: inherit;
  font-size: 1.25rem;
  cursor: pointer;
  opacity: 0.8;
  padding: 0 0.25rem;
}
.close:hover {
  opacity: 1;
}
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
