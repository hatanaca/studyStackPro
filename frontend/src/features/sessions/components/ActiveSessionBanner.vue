<script setup lang="ts">
import { useRouter } from 'vue-router'
import { prefetchSessionFocusView } from '@/router/prefetch'
import { useEndSession } from '@/features/sessions/composables/useEndSession'

const { activeSession, formattedTime, ending, endSession } = useEndSession()
const router = useRouter()

function goToFocus() {
  router.push({ name: 'session-focus' })
}
</script>

<template>
  <div v-if="activeSession" class="active-session-banner" @mouseenter="prefetchSessionFocusView">
    <div class="active-session-banner__content">
      <span class="active-session-banner__label">Sessão ativa</span>
      <span class="active-session-banner__time" role="timer" aria-live="off" aria-atomic="true">{{
        formattedTime
      }}</span>
      <span v-if="activeSession.technology" class="active-session-banner__tech">
        {{ activeSession.technology.name }}
      </span>
    </div>
    <button
      type="button"
      class="active-session-banner__btn active-session-banner__btn--ghost"
      aria-label="Ir para modo foco da sessão"
      @click="goToFocus"
    >
      Modo foco
    </button>
    <button
      type="button"
      class="active-session-banner__btn"
      aria-label="Finalizar sessão em andamento"
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
  padding: var(--spacing-lg) var(--widget-padding);
  background: var(--gradient-primary);
  color: var(--color-primary-contrast);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-lg);
  gap: var(--spacing-sm);
  flex-wrap: wrap;
  box-shadow: var(--shadow-md);
  border: 1px solid color-mix(in srgb, var(--color-primary) 80%, transparent);
}
.active-session-banner__content {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm) var(--spacing-lg);
  flex-wrap: wrap;
  line-height: var(--leading-snug);
}
.active-session-banner__label {
  font-size: var(--text-xs);
  font-weight: 600;
  letter-spacing: var(--tracking-wide);
  opacity: 0.95;
  text-transform: uppercase;
}
.active-session-banner__time {
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  font-size: var(--text-xl);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.active-session-banner__tech {
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  opacity: 0.95;
}
.active-session-banner__btn {
  min-height: var(--touch-target-min);
  padding: var(--spacing-sm) var(--spacing-lg);
  background: color-mix(in srgb, var(--color-primary-contrast) 20%, transparent);
  color: var(--color-primary-contrast);
  border: 1px solid color-mix(in srgb, var(--color-primary-contrast) 40%, transparent);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition:
    background var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.active-session-banner__btn--ghost {
  background: color-mix(in srgb, var(--color-primary-contrast) 12%, transparent);
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
@media (max-width: 640px) {
  .active-session-banner {
    flex-direction: column;
    align-items: stretch;
    padding: var(--spacing-xl);
    text-align: center;
  }
  .active-session-banner__content {
    justify-content: center;
  }
  .active-session-banner__btn {
    width: 100%;
    min-height: var(--touch-target-min);
  }
}
</style>
