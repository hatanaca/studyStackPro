<script setup lang="ts"></script>

<template>
  <div class="auth-layout">
    <div class="auth-layout__bg" />
    <div class="auth-layout__grid" aria-hidden="true" />
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
.auth-layout__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
  background-size: 48px 48px;
  pointer-events: none;
  mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
  -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
}
[data-theme='light'] .auth-layout__grid {
  background-image:
    linear-gradient(rgba(0, 0, 0, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0, 0, 0, 0.03) 1px, transparent 1px);
}
.auth-layout__card {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: var(--auth-card-max-width);
  min-height: calc(var(--spacing-3xl) * 6);
  padding: var(--spacing-2xl) var(--spacing-3xl);
  border-radius: var(--radius-xl);
  background: rgba(30, 33, 48, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  box-shadow:
    var(--shadow-lg),
    0 0 0 1px rgba(255, 255, 255, 0.06),
    0 0 80px rgba(107, 124, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
[data-theme='light'] .auth-layout__card {
  background: rgba(255, 255, 255, 0.9);
  border-color: rgba(0, 0, 0, 0.06);
  box-shadow: var(--shadow-lg);
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
