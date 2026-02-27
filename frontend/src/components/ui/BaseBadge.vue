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
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 9999px;
  background-color: #e2e8f0;
  color: #475569;
}
</style>
