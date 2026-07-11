<script setup lang="ts">
/**
 * @module ChartPanel
 *
 * Wrapper de painel de gráfico com visual glassmorphism moderno.
 * Bordas com gradiente, glow no hover, fundo semi-transparente.
 */
defineProps<{
  title?: string
  loading?: boolean
}>()
</script>

<template>
  <div class="cp" :class="{ 'cp--loading': loading }">
    <div class="cp__glow" />
    <div v-if="title" class="cp__header">
      <h3 class="cp__title">{{ title }}</h3>
      <slot name="actions" />
    </div>
    <div class="cp__body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.cp {
  position: relative;
  background: linear-gradient(135deg,
    color-mix(in srgb, var(--color-bg-card) 95%, transparent) 0%,
    color-mix(in srgb, var(--color-bg-soft) 90%, transparent) 100%
  );
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid color-mix(in srgb, var(--color-primary) 8%, transparent);
  border-radius: var(--radius-xl);
  padding: var(--spacing-lg);
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.cp:hover {
  border-color: color-mix(in srgb, var(--color-primary) 20%, transparent);
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--color-primary) 4%, transparent),
    0 4px 24px color-mix(in srgb, var(--color-bg) 30%, transparent),
    0 1px 4px color-mix(in srgb, var(--color-primary) 6%, transparent);
  transform: translateY(-2px);
}

/* Subtle gradient glow on hover */
.cp__glow {
  position: absolute;
  top: -30%;
  right: -20%;
  width: 50%;
  height: 120%;
  background: radial-gradient(ellipse, color-mix(in srgb, var(--color-primary) 4%, transparent) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}
.cp:hover .cp__glow {
  opacity: 1;
}

.cp__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
  position: relative;
  z-index: 1;
}
.cp__title {
  font-size: var(--text-sm);
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
  letter-spacing: -0.01em;
}
.cp__body {
  position: relative;
  z-index: 1;
  min-height: var(--widget-chart-min-height, 220px);
  width: 100%;
  overflow: visible;
}

/* Loading state */
.cp--loading {
  pointer-events: none;
}
</style>
