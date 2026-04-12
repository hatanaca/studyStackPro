<script setup lang="ts">
</script>

<template>
  <div class="auth-layout">
    <div class="auth-layout__bg" />
    <main class="auth-layout__card">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
  padding: var(--spacing-lg);
  position: relative;
  overflow: hidden;
}
.auth-layout__bg {
  position: absolute;
  inset: 0;
  background: var(--gradient-mesh);
  pointer-events: none;
}
.auth-layout__card {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: var(--auth-card-max-width);
  min-height: calc(var(--spacing-3xl) * 6);
  padding: var(--spacing-2xl) var(--spacing-3xl);
  border-radius: var(--radius-xl);
  background: color-mix(in srgb, var(--color-bg-card) 97%, transparent);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: var(--shadow-lg);
  border: 1px solid color-mix(in srgb, var(--color-border) 70%, transparent);
}
@media (max-width: 640px) {
  .auth-layout__card {
    padding: var(--spacing-xl) var(--spacing-lg);
    border-radius: var(--radius-lg);
    min-height: var(--auth-card-min-height-mobile);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    background: var(--color-bg-card);
  }
}
</style>
