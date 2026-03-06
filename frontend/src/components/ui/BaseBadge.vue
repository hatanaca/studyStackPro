<script setup lang="ts">
defineProps<{
  label: string
  color?: string
}>()
</script>

<template>
  <span
    class="base-badge"
    :style="color ? { backgroundColor: `${color}20`, color } : undefined"
  >
    {{ label }}
  </span>
</template>

<style scoped>
.base-badge {
  display: inline-flex;
  align-items: center;
  padding: var(--spacing-2xs) var(--spacing-sm);
  font-size: var(--text-xs);
  font-weight: 600;
  border-radius: 9999px;
  background-color: var(--color-bg-soft);
  color: var(--color-text-muted);
  border: 1px solid transparent;
  transition: border-color var(--duration-fast) ease;
}
.base-badge:not([style*="background"]) {
  border-color: color-mix(in srgb, var(--color-border) 60%, transparent);
}
</style>
