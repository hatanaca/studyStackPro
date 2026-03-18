<script setup lang="ts">
withDefaults(
  defineProps<{
    /** Texto opcional no centro da linha (ex.: "ou") */
    label?: string
    /** Margem vertical */
    spacing?: 'none' | 'sm' | 'md'
  }>(),
  { label: '', spacing: 'md' }
)
</script>

<template>
  <div
    class="divider"
    :class="[label ? 'divider--labeled' : '', `divider--spacing-${spacing}`]"
    role="separator"
    :aria-hidden="!label"
  >
    <span
      v-if="label"
      class="divider__label"
    >
      {{ label }}
    </span>
  </div>
</template>

<style scoped>
.divider {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  width: 100%;
}
.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--color-border);
}
.divider--labeled .divider__label {
  font-size: var(--text-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  white-space: nowrap;
}
.divider--spacing-none {
  margin: 0;
}
.divider--spacing-sm {
  margin: var(--spacing-sm) 0;
}
.divider--spacing-md {
  margin: var(--spacing-lg) 0;
}
</style>
