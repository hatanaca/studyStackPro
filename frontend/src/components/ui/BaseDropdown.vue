<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'

const props = withDefaults(
  defineProps<{
    /** Alinhamento do painel: left, right, center */
    align?: 'left' | 'right' | 'center'
    /** Desabilitar dropdown */
    disabled?: boolean
    /** Fechar ao clicar fora */
    closeOnClickOutside?: boolean
  }>(),
  { align: 'left', disabled: false, closeOnClickOutside: true }
)

const emit = defineEmits<{
  open: []
  close: []
}>()

const isOpen = ref(false)
const triggerRef = ref<HTMLElement | null>(null)
const panelRef = ref<HTMLElement | null>(null)

const alignClass = computed(() => `base-dropdown--${props.align}`)

function toggle() {
  if (props.disabled) return
  isOpen.value = !isOpen.value
  if (isOpen.value) emit('open')
  else emit('close')
}

function close() {
  if (isOpen.value) {
    isOpen.value = false
    emit('close')
  }
}

function onDocumentClick(e: MouseEvent) {
  const target = e.target as Node
  if (
    props.closeOnClickOutside &&
    isOpen.value &&
    triggerRef.value &&
    !triggerRef.value.contains(target) &&
    panelRef.value &&
    !panelRef.value.contains(target)
  ) {
    close()
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') close()
}

watch(isOpen, (open) => {
  if (open) {
    requestAnimationFrame(() => {
      document.addEventListener('click', onDocumentClick, true)
      document.addEventListener('keydown', onKeydown)
    })
  } else {
    document.removeEventListener('click', onDocumentClick, true)
    document.removeEventListener('keydown', onKeydown)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick, true)
  document.removeEventListener('keydown', onKeydown)
})

defineExpose({ close })
</script>

<template>
  <div class="base-dropdown">
    <div
      ref="triggerRef"
      class="base-dropdown__trigger"
      :class="{ 'base-dropdown__trigger--disabled': disabled }"
      role="button"
      tabindex="0"
      aria-haspopup="true"
      :aria-expanded="isOpen"
      @click="toggle"
      @keydown.enter.space.prevent="toggle"
    >
      <slot name="trigger" />
    </div>
    <Transition name="dropdown">
      <div
        v-show="isOpen"
        ref="panelRef"
        class="base-dropdown__panel"
        :class="alignClass"
        role="menu"
      >
        <slot name="default" />
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.base-dropdown {
  position: relative;
  display: inline-block;
}
.base-dropdown__trigger {
  cursor: pointer;
  outline: none;
}
.base-dropdown__trigger:focus-visible {
  box-shadow: var(--shadow-focus);
  border-radius: var(--radius-sm);
}
.base-dropdown__trigger--disabled {
  opacity: var(--state-disabled-opacity);
  cursor: not-allowed;
  pointer-events: none;
}
.base-dropdown__panel {
  position: absolute;
  z-index: var(--z-dropdown, 100);
  min-width: 10rem;
  margin-top: var(--spacing-xs);
  padding: var(--spacing-xs);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--dropdown-shadow, var(--shadow-lg));
}
.base-dropdown--left { left: 0; }
.base-dropdown--right { right: 0; left: auto; }
.base-dropdown--center { left: 50%; transform: translateX(-50%); }

.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity var(--duration-fast) var(--ease-in-out), transform var(--duration-fast) var(--ease-out-expo);
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(calc(-1 * var(--spacing-xs)));
}
</style>
