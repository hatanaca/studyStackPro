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
  background: var(--color-bg-card);
  padding: var(--spacing-2xl) var(--spacing-3xl);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--color-border);
  width: 100%;
  max-width: 24rem;
  min-height: 18rem;
}
@media (max-width: 640px) {
  .auth-layout__card {
    padding: var(--spacing-xl) var(--spacing-lg);
    border-radius: var(--radius-md);
    min-height: 260px;
  }
}
</style>
