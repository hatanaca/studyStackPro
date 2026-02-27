<script setup lang="ts">
import { ref } from 'vue'
import { useSessionTimer } from '@/features/sessions/composables/useSessionTimer'
import { sessionsApi } from '@/api/modules/sessions.api'

const { activeSession, formattedTime, refresh } = useSessionTimer()
const ending = ref(false)

async function endSession() {
  if (!activeSession.value || ending.value) return
  ending.value = true
  try {
    await sessionsApi.end(activeSession.value.id)
    await refresh()
  } finally {
    ending.value = false
  }
}
</script>

<template>
  <div
    v-if="activeSession"
    class="active-session-banner"
  >
    <div class="active-session-banner__content">
      <span class="active-session-banner__label">Sessão ativa</span>
      <span class="active-session-banner__time">{{ formattedTime }}</span>
      <span
        v-if="activeSession.technology"
        class="active-session-banner__tech"
      >
        {{ activeSession.technology.name }}
      </span>
    </div>
    <button
      type="button"
      class="active-session-banner__btn"
      :disabled="ending"
      @click="endSession"
    >
      {{ ending ? 'Finalizando...' : 'Finalizar sessão' }}
    </button>
  </div>
</template>

<style scoped>
.active-session-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: #fff;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}
.active-session-banner__content {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.active-session-banner__label {
  font-size: 0.75rem;
  opacity: 0.9;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.active-session-banner__time {
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  font-size: 1.25rem;
}
.active-session-banner__tech {
  font-size: 0.875rem;
  opacity: 0.9;
}
.active-session-banner__btn {
  padding: 0.375rem 0.75rem;
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  cursor: pointer;
}
.active-session-banner__btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.3);
}
.active-session-banner__btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
