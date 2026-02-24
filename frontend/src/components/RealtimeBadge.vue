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
  gap: 0.375rem;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
}
.realtime-badge .dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}
.realtime-badge.connected {
  color: #059669;
  background: #d1fae5;
}
.realtime-badge.disconnected {
  color: #dc2626;
  background: #fee2e2;
}
</style>
