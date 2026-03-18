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
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: visible;
  transition: box-shadow var(--duration-normal) var(--ease-in-out),
    border-color var(--duration-fast) ease;
}
.base-card:hover {
  box-shadow: var(--shadow-card-hover);
  border-color: var(--color-border);
}
.base-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--widget-padding, var(--spacing-lg));
  padding-bottom: 0;
}
.base-card__title {
  font-size: var(--widget-title-size, var(--text-sm));
  font-weight: var(--widget-title-weight, 600);
  color: var(--color-text);
  margin: 0;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-snug);
}
.base-card__body {
  padding: var(--widget-padding, var(--spacing-lg));
  min-width: 0;
}
</style>
