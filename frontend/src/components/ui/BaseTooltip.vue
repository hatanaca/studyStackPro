<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = withDefaults(
  defineProps<{
    /** Conteúdo do tooltip */
    content: string
    /** Posição: top, bottom, left, right */
    placement?: 'top' | 'bottom' | 'left' | 'right'
    /** Atraso em ms antes de mostrar */
    delay?: number
    /** Desabilitar tooltip */
    disabled?: boolean
  }>(),
  { placement: 'top', delay: 200, disabled: false }
)

const triggerRef = ref<HTMLElement | null>(null)
const tooltipRef = ref<HTMLElement | null>(null)
const isVisible = ref(false)
const tooltipId = `base-tooltip-${Math.random().toString(36).slice(2, 8)}`
let showTimeout: ReturnType<typeof setTimeout> | null = null

const placementClass = computed(() => `base-tooltip--${props.placement}`)

function show() {
  if (props.disabled) return
  showTimeout = setTimeout(() => {
    isVisible.value = true
  }, props.delay)
}

function hide() {
  if (showTimeout) {
    clearTimeout(showTimeout)
    showTimeout = null
  }
  isVisible.value = false
}

onMounted(() => {
  const el = triggerRef.value
  if (!el) return
  el.addEventListener('mouseenter', show)
  el.addEventListener('mouseleave', hide)
  el.addEventListener('focus', show)
  el.addEventListener('blur', hide)
})

onUnmounted(() => {
  const el = triggerRef.value
  if (!el) return
  el.removeEventListener('mouseenter', show)
  el.removeEventListener('mouseleave', hide)
  el.removeEventListener('focus', show)
  el.removeEventListener('blur', hide)
  if (showTimeout) clearTimeout(showTimeout)
})
</script>

<template>
  <div class="base-tooltip-wrapper">
    <div
      ref="triggerRef"
      class="base-tooltip-trigger"
      :aria-describedby="isVisible ? tooltipId : undefined"
    >
      <slot />
    </div>
    <Transition name="tooltip-fade">
      <div
        v-show="isVisible"
        :id="tooltipId"
        ref="tooltipRef"
        class="base-tooltip"
        :class="placementClass"
        role="tooltip"
      >
        {{ content }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.base-tooltip-wrapper {
  position: relative;
  display: inline-flex;
}
.base-tooltip-trigger {
  display: inline-flex;
  cursor: default;
}
.base-tooltip {
  position: absolute;
  z-index: var(--z-tooltip, 1200);
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: var(--text-xs);
  font-weight: 500;
  line-height: var(--leading-snug);
  color: var(--color-bg-card);
  background: var(--color-text);
  border-radius: var(--radius-sm);
  white-space: nowrap;
  box-shadow: var(--shadow-md);
  pointer-events: none;
}
[data-theme='dark'] .base-tooltip {
  background: var(--color-bg-soft);
  color: var(--color-text);
}
.base-tooltip--top {
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(-var(--tooltip-offset, 0.5rem));
  margin-bottom: var(--spacing-xs);
}
.base-tooltip--bottom {
  top: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(var(--tooltip-offset, 0.5rem));
  margin-top: var(--spacing-xs);
}
.base-tooltip--left {
  right: 100%;
  top: 50%;
  transform: translateY(-50%) translateX(-var(--tooltip-offset, 0.5rem));
  margin-right: var(--spacing-xs);
}
.base-tooltip--right {
  left: 100%;
  top: 50%;
  transform: translateY(-50%) translateX(var(--tooltip-offset, 0.5rem));
  margin-left: var(--spacing-xs);
}
</style>
