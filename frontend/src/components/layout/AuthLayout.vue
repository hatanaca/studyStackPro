<script setup lang="ts">
</script>

<template>
  <div class="auth-layout">
    <div class="auth-layout__card">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
}
.auth-layout__card {
  background: #fff;
  padding: 2rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 360px;
}
</style>
