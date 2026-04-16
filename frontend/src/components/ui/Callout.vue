<script setup lang="ts">
withDefaults(
  defineProps<{
    /** Variante visual: info, success, warning, error */
    variant?: 'info' | 'success' | 'warning' | 'error'
    /** Título opcional do callout */
    title?: string
    /** Exibir ícone ao lado do conteúdo */
    showIcon?: boolean
  }>(),
  { variant: 'info', title: '', showIcon: true }
)

const variantIcon: Record<string, string> = {
  info: 'ℹ',
  success: '✓',
  warning: '!',
  error: '✕',
}
</script>

<template>
  <div class="callout" :class="`callout--${variant}`" role="status">
    <span v-if="showIcon" class="callout__icon" aria-hidden="true">
      {{ variantIcon[variant] }}
    </span>
    <div class="callout__content">
      <strong v-if="title || $slots.title" class="callout__title">
        <slot name="title">{{ title }}</slot>
      </strong>
      <div class="callout__body">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
.callout {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-sm);
  padding: var(--spacing-lg) var(--spacing-xl);
  border-radius: var(--radius-lg);
  border: 1px solid transparent;
  box-shadow: var(--shadow-sm);
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
}
.callout__icon {
  flex-shrink: 0;
  width: var(--icon-size-sm);
  height: var(--icon-size-sm);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: var(--text-xs);
  font-weight: 700;
}
.callout__content {
  flex: 1;
  min-width: 0;
}
.callout__title {
  display: block;
  margin-bottom: var(--spacing-2xs);
  font-family: var(--font-display);
  font-size: var(--text-base);
  font-weight: 700;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.callout__body {
  color: inherit;
  opacity: 0.95;
}
.callout--info {
  background: var(--color-info-soft);
  border-color: color-mix(in srgb, var(--color-info) 40%, transparent);
  color: var(--color-text);
}
.callout--info .callout__icon {
  background: var(--color-info);
  color: var(--color-primary-contrast);
}
.callout--success {
  background: var(--color-success-soft);
  border-color: color-mix(in srgb, var(--color-success) 40%, transparent);
  color: var(--color-text);
}
.callout--success .callout__icon {
  background: var(--color-success);
  color: var(--color-primary-contrast);
}
.callout--warning {
  background: var(--color-warning-soft);
  border-color: color-mix(in srgb, var(--color-warning) 40%, transparent);
  color: var(--color-text);
}
.callout--warning .callout__icon {
  background: var(--color-warning);
  color: var(--color-primary-contrast);
}
.callout--error {
  background: var(--color-error-soft);
  border-color: color-mix(in srgb, var(--color-error) 40%, transparent);
  color: var(--color-text);
}
.callout--error .callout__icon {
  background: var(--color-error);
  color: var(--color-primary-contrast);
}
</style>
