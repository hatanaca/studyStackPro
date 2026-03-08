<script setup lang="ts">
import { ref } from 'vue'
import { getApiErrorMessage } from '@/api/client'
import { useSessionTimer } from '@/features/sessions/composables/useSessionTimer'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useToast } from '@/composables/useToast'

const { activeSession, formattedTime, refresh } = useSessionTimer()
const toast = useToast()
const ending = ref(false)

async function endSession() {
  if (!activeSession.value || ending.value) return
  ending.value = true
  try {
    await sessionsApi.end(activeSession.value.id)
    await refresh()
  } catch (err: unknown) {
    toast.error(getApiErrorMessage(err) || 'Erro ao finalizar sessão.')
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
  padding: var(--spacing-md) var(--widget-padding);
  background: var(--gradient-primary);
  color: var(--color-primary-contrast);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-md);
  gap: var(--spacing-sm);
  flex-wrap: wrap;
  box-shadow: var(--shadow-md);
  border: 1px solid color-mix(in srgb, var(--color-primary) 80%, transparent);
}
.active-session-banner__content {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm) var(--spacing-md);
  flex-wrap: wrap;
}
.active-session-banner__label {
  font-size: var(--text-xs);
  font-weight: 600;
  opacity: 0.95;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.active-session-banner__time {
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  font-size: var(--text-xl);
  letter-spacing: -0.02em;
}
.active-session-banner__tech {
  font-size: var(--text-sm);
  opacity: 0.95;
}
.active-session-banner__btn {
  min-height: 2.75rem; /* >= 44px área de toque a11y */
  padding: var(--spacing-sm) var(--spacing-md);
  background: color-mix(in srgb, var(--color-primary-contrast) 20%, transparent);
  color: var(--color-primary-contrast);
  border: 1px solid color-mix(in srgb, var(--color-primary-contrast) 40%, transparent);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.active-session-banner__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.active-session-banner__btn:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-primary-contrast) 30%, transparent);
  border-color: color-mix(in srgb, var(--color-primary-contrast) 50%, transparent);
}
.active-session-banner__btn:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}
@media (max-width: 480px) {
  .active-session-banner {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }
  .active-session-banner__content {
    justify-content: center;
  }
  .active-session-banner__btn {
    width: 100%;
  }
}
</style>
