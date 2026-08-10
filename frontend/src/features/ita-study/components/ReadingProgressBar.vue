<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  progress: number
}>()

const clamped = computed(() => Math.max(0, Math.min(100, props.progress)))
</script>

<template>
  <div class="reading-progress" aria-hidden="true">
    <div class="progress-track">
      <div class="progress-fill" :style="{ width: `${clamped}%` }"></div>
    </div>
    <span class="progress-label">{{ Math.round(clamped) }}%</span>
  </div>
</template>

<style scoped>
.reading-progress {
  position: sticky;
  top: 0;
  z-index: 20;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.375rem 0;
  background: var(--p-surface-0);
  border-bottom: 1px solid var(--p-border-color);
}

.progress-track {
  flex: 1;
  height: 5px;
  background: var(--p-content-200);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--p-primary-color);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--p-text-muted-color);
  min-width: 2.5rem;
  text-align: right;
}
</style>
