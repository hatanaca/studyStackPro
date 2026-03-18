<script setup lang="ts">
import { useSessionTimer } from '@/features/sessions/composables/useSessionTimer'

const { formattedTime, activeSession } = useSessionTimer()
</script>

<template>
  <div
    v-if="activeSession"
    class="session-timer"
  >
    <span class="session-timer__time">{{ formattedTime }}</span>
    <span
      v-if="activeSession.technology"
      class="session-timer__tech"
    >
      {{ activeSession.technology.name }}
    </span>
  </div>
</template>

<style scoped>
.session-timer {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-variant-numeric: tabular-nums;
}
.session-timer__time {
  font-weight: 600;
  font-size: var(--text-lg);
  color: var(--color-text);
  letter-spacing: var(--tracking-normal);
}
.session-timer__tech {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
</style>
