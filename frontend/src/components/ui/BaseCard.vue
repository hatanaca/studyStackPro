<script setup lang="ts">
defineProps<{
  title?: string
}>()
</script>

<template>
  <div class="base-card">
    <div
      v-if="title || $slots.actions"
      class="base-card__header"
    >
      <h3
        v-if="title"
        class="base-card__title"
      >
        {{ title }}
      </h3>
      <div
        v-if="$slots.actions"
        class="base-card__actions"
      >
        <slot name="actions" />
      </div>
    </div>
    <div class="base-card__body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.base-card {
  background: var(--color-bg-card);
  border-radius: var(--card-chrome-radius);
  box-shadow: var(--card-chrome-shadow);
  border: var(--card-chrome-border);
  overflow: visible;
  transition: box-shadow var(--duration-normal) var(--ease-in-out),
    border-color var(--duration-fast) ease;
}
.base-card:hover {
  box-shadow: var(--shadow-card-hover);
  border-color: color-mix(in srgb, var(--color-primary) 18%, var(--color-border));
}
.base-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--widget-padding, var(--spacing-lg));
  padding-bottom: 0;
}
.base-card__title {
  font-family: var(--font-display);
  font-size: var(--widget-title-size, var(--text-base));
  font-weight: var(--widget-title-weight, 700);
  color: var(--color-text);
  margin: 0;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.base-card__body {
  padding: var(--widget-padding, var(--spacing-lg));
  min-width: 0;
}
</style>
