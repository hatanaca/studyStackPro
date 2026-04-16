<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    /** Valor entre 0 e 100 (ou 0 e max) */
    value: number
    /** Valor máximo (default 100) */
    max?: number
    /** Altura da barra: sm, md, lg */
    size?: 'sm' | 'md' | 'lg'
    /** Cor semântica: primary, success, warning, error */
    variant?: 'primary' | 'success' | 'warning' | 'error'
    /** Exibir label de porcentagem */
    showLabel?: boolean
    /** Texto customizado no lugar da porcentagem */
    label?: string
    /** Barra indeterminada (animação) */
    indeterminate?: boolean
  }>(),
  { max: 100, size: 'md', variant: 'primary', showLabel: false, label: '', indeterminate: false }
)

const percentage = computed(() => {
  if (props.indeterminate) return 0
  const v = Math.min(Math.max(props.value, 0), props.max)
  return props.max ? Math.round((v / props.max) * 100) : 0
})

const sizeClass = computed(() => `base-progress--${props.size}`)
const variantClass = computed(() => `base-progress--${props.variant}`)
</script>

<template>
  <div class="base-progress">
    <div v-if="showLabel || label" class="base-progress__header">
      <span v-if="label" class="base-progress__label">{{ label }}</span>
      <span v-else-if="showLabel && !indeterminate" class="base-progress__percent"
        >{{ percentage }}%</span
      >
    </div>
    <div
      class="base-progress__track"
      :class="[sizeClass, variantClass, { 'base-progress--indeterminate': indeterminate }]"
      role="progressbar"
      :aria-valuenow="indeterminate ? undefined : value"
      :aria-valuemin="0"
      :aria-valuemax="max"
      :aria-label="label || (showLabel ? `${percentage}%` : undefined)"
    >
      <div v-if="!indeterminate" class="base-progress__bar" :style="{ width: `${percentage}%` }" />
      <div v-else class="base-progress__bar base-progress__bar--indeterminate" />
    </div>
  </div>
</template>

<style scoped>
.base-progress {
  width: 100%;
}
.base-progress__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xs);
}
.base-progress__label,
.base-progress__percent {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
}
.base-progress__track {
  position: relative;
  width: 100%;
  background: var(--color-bg-soft);
  border-radius: var(--radius-full);
  overflow: hidden;
}
.base-progress--sm {
  height: 0.375rem;
}
.base-progress--md {
  height: 0.5rem;
}
.base-progress--lg {
  height: 0.625rem;
}

.base-progress__bar {
  height: 100%;
  border-radius: var(--radius-full);
  background: var(--color-primary);
  transition: width var(--duration-normal) var(--ease-out-expo);
}
.base-progress--success .base-progress__bar {
  background: var(--color-success);
}
.base-progress--warning .base-progress__bar {
  background: var(--color-warning);
}
.base-progress--error .base-progress__bar {
  background: var(--color-error);
}

.base-progress__bar--indeterminate {
  width: 40% !important;
  animation: base-progress-indeterminate 1.5s ease-in-out infinite;
}
@keyframes base-progress-indeterminate {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(350%);
  }
}
</style>
