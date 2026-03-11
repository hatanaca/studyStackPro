<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useSessionTimer } from '@/features/sessions/composables/useSessionTimer'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const toast = useToast()
const { activeSession, formattedTime, refresh } = useSessionTimer()
const ending = ref(false)

async function endSession() {
  if (!activeSession.value || ending.value) return
  ending.value = true
  try {
    await sessionsApi.end(activeSession.value.id)
    await refresh()
    router.push('/sessions')
  } catch (err: unknown) {
    toast.error(getApiErrorMessage(err) || 'Erro ao finalizar sessão.')
  } finally {
    ending.value = false
  }
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
          :disabled="ending"
          @click="endSession"
        >
          {{ ending ? 'Finalizando...' : 'Finalizar sessão' }}
        </button>
        <button
          type="button"
          class="session-focus__btn"
          @click="router.push('/sessions')"
        >
          Ver sessões
        </button>
      </div>
    </div>
    <div v-else class="session-focus__empty">
      <h1>Nenhuma sessão ativa</h1>
      <p>Inicie uma sessão para entrar no modo foco.</p>
      <button type="button" class="session-focus__btn" @click="router.push('/sessions')">
        Ir para Sessões
      </button>
    </div>
  </section>
</template>

<style scoped>
.session-focus {
  min-height: calc(100vh - var(--header-height) - (var(--spacing-md) * 2));
  display: grid;
  place-items: center;
  padding: var(--spacing-lg);
}
.session-focus__card,
.session-focus__empty {
  width: min(640px, 100%);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  box-shadow: var(--shadow-md);
  padding: var(--spacing-xl);
  text-align: center;
}
.session-focus__label {
  margin: 0 0 var(--spacing-sm);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.session-focus__timer {
  margin: 0;
  font-size: clamp(2.25rem, 8vw, 4rem);
  font-variant-numeric: tabular-nums;
  color: var(--color-primary);
}
.session-focus__tech {
  margin: var(--spacing-sm) 0 0;
  color: var(--color-text);
  font-size: var(--text-lg);
}
.session-focus__actions {
  margin-top: var(--spacing-xl);
  display: flex;
  justify-content: center;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.session-focus__btn {
  min-height: 2.75rem;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-soft);
  color: var(--color-text);
  font-weight: 600;
  cursor: pointer;
}
.session-focus__btn--primary {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-primary-contrast);
}
.session-focus__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.session-focus__btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
