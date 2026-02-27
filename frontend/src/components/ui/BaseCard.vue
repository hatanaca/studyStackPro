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
  background: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}
.base-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1rem 0;
}
.base-card__title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
  margin: 0;
}
.base-card__body {
  padding: 1rem;
}
</style>
