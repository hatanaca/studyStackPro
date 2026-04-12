<script setup lang="ts">
import { useWebSocket } from '@/composables/useWebSocket'

const { isConnected } = useWebSocket()
</script>

<template>
  <span
    class="realtime-badge"
    :class="{ connected: isConnected, disconnected: !isConnected }"
    :title="isConnected ? 'Tempo real ativo' : 'Desconectado'"
  >
    <span class="dot" />
    {{ isConnected ? 'Tempo real' : 'Desconectado' }}
  </span>
</template>

<style scoped>
.realtime-badge {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--text-xs);
  font-weight: 600;
  padding: var(--spacing-2xs) var(--spacing-sm);
  border-radius: var(--radius-full);
  border: 1px solid transparent;
}
.realtime-badge .dot {
  width: 0.375rem;
  height: 0.375rem;
  border-radius: 50%;
  background: currentColor;
}
.realtime-badge.connected {
  color: var(--color-success);
  background: var(--color-success-soft);
  border-color: color-mix(in srgb, var(--color-success) 35%, transparent);
}
.realtime-badge.disconnected {
  color: var(--color-error);
  background: var(--color-error-soft);
  border-color: color-mix(in srgb, var(--color-error) 35%, transparent);
}
</style>
