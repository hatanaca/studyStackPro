<script setup lang="ts">
withDefaults(
  defineProps<{
    width?: string
    height?: string
    borderRadius?: string
    variant?: 'text' | 'rectangular' | 'circular'
  }>(),
  {
    width: '100%',
    height: '1rem',
    borderRadius: '0.25rem',
    variant: 'rectangular'
  }
)
</script>

<template>
  <div
    class="skeleton"
    :class="[`skeleton--${variant}`]"
    :style="{
      width,
      height,
      borderRadius: variant === 'circular' ? '50%' : borderRadius
    }"
  />
</template>

<style scoped>
.skeleton {
  background: linear-gradient(
    90deg,
    #e2e8f0 0%,
    #f1f5f9 20%,
    #e2e8f0 40%,
    #e2e8f0 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}
@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
.skeleton--text {
  border-radius: 0.25rem;
}
.skeleton--circular {
  aspect-ratio: 1;
}
</style>
