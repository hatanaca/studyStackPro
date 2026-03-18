<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useEndSession } from '@/features/sessions/composables/useEndSession'

const router = useRouter()
const { activeSession, formattedTime, ending, endSession: doEndSession } = useEndSession()

async function endSession() {
  const success = await doEndSession()
  if (success) router.push('/sessions')
}
</script>

<template>
  <section class="session-focus" aria-label="Modo foco da sessão ativa">
    <div v-if="activeSession" class="session-focus__card">
      <p class="session-focus__label">Sessão em andamento</p>
      <h1 class="session-focus__timer">{{ formattedTime }}</h1>
      <p v-if="activeSession.technology" class="session-focus__tech">
        {{ activeSession.technology.name }}
      </p>
      <div class="session-focus__actions">
        <button
          type="button"
          class="session-focus__btn session-focus__btn--primary"
          aria-label="Finalizar sessão em andamento"
          :aria-busy="ending"
          :disabled="ending"
          @click="endSession"
        >
          {{ ending ? 'Finalizando...' : 'Finalizar sessão' }}
        </button>
        <button
          type="button"
          class="session-focus__btn"
          aria-label="Ver lista de sessões"
          @click="router.push('/sessions')"
        >
          Ver sessões
        </button>
      </div>
    </div>
    <div v-else class="session-focus__empty">
      <h1>Nenhuma sessão ativa</h1>
      <p>Inicie uma sessão para entrar no modo foco.</p>
      <button
        type="button"
        class="session-focus__btn"
        aria-label="Ir para lista de sessões"
        @click="router.push('/sessions')"
      >
        Ir para Sessões
      </button>
    </div>
  </section>
</template>

<style scoped>
.session-focus {
  min-height: calc(100vh - var(--header-height) - (var(--spacing-lg) * 2));
  display: grid;
  place-items: center;
  padding: var(--spacing-xl);
}
.session-focus__card,
.session-focus__empty {
  width: min(40rem, 100%);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  box-shadow: var(--shadow-md);
  padding: var(--spacing-2xl);
  text-align: center;
}
.session-focus__label {
  margin: 0 0 var(--spacing-sm);
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-wide);
  color: var(--color-text-muted);
  text-transform: uppercase;
}
.session-focus__timer {
  margin: 0;
  font-size: clamp(2.25rem, 8vw, 4rem); /* display grande, fora da escala text-* */
  line-height: var(--leading-tight);
  letter-spacing: var(--tracking-tight);
  font-variant-numeric: tabular-nums;
  color: var(--color-primary);
}
.session-focus__tech {
  margin: var(--spacing-sm) 0 0;
  font-size: var(--text-lg);
  line-height: var(--leading-snug);
  color: var(--color-text);
}
.session-focus__actions {
  margin-top: var(--spacing-2xl);
  display: flex;
  justify-content: center;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.session-focus__btn {
  min-height: 2.75rem; /* 44px touch target a11y */
  padding: var(--spacing-sm) var(--spacing-lg);
  font-size: var(--text-sm);
  font-weight: 600;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-soft);
  color: var(--color-text);
  cursor: pointer;
  transition: background var(--duration-fast) ease, border-color var(--duration-fast) ease, color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.session-focus__btn:hover:not(:disabled) {
  background: var(--color-border);
  border-color: var(--color-text-muted);
  color: var(--color-text);
}
.session-focus__btn--primary {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-primary-contrast);
}
.session-focus__btn--primary:hover:not(:disabled) {
  background: var(--color-primary-hover);
  border-color: var(--color-primary-hover);
}
.session-focus__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.session-focus__btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.session-focus__empty h1 {
  font-size: var(--text-xl);
  font-weight: 700;
  line-height: var(--leading-tight);
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm);
}
.session-focus__empty p {
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-xl);
}
.session-focus__empty .session-focus__btn {
  margin-top: var(--spacing-md);
}
</style>
