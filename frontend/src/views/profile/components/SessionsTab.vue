<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '@/stores/auth.store'
import { authApi, type TokenInfo } from '@/api/modules/auth.api'
import { useToast } from '@/composables/useToast'
import { formatDateTime } from '@/utils/formatters'

const authStore = useAuthStore()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const tokens = ref<TokenInfo[]>([])
const tokensLoading = ref(false)
const revokeLoading = ref(false)

onMounted(loadTokens)

async function loadTokens() {
  tokensLoading.value = true
  try {
    const { data } = await authApi.getTokens()
    if (data.success && data.data) {
      tokens.value = data.data
    }
  } catch {
    toast.error('Erro ao carregar sessões')
  } finally {
    tokensLoading.value = false
  }
}

function revokeAll() {
  confirm.require({
    header: 'Revogar todas as sessões',
    message: 'Você será desconectado de todos os dispositivos e precisará fazer login novamente.',
    acceptLabel: 'Revogar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    async accept() {
      revokeLoading.value = true
      try {
        const { data } = await authApi.revokeAllTokens()
        if (data.success && data.data) {
          toast.success(`${data.data.revoked_count} sessão(ões) revogada(s)`)
          authStore.logout()
          router.push('/login')
        }
      } catch {
        toast.error('Erro ao revogar sessões')
      } finally {
        revokeLoading.value = false
      }
    },
  })
}
</script>

<template>
  <Card class="sessions-tab__card">
    <template #content>
      <h2 class="section-title">Sessões ativas</h2>
      <p class="section-desc">Gerencie os dispositivos onde você está logado.</p>
      <div
        v-if="tokensLoading"
        class="sessions-tab__skeleton"
        role="status"
        aria-live="polite"
        aria-label="Carregando sessões ativas"
      >
        <Skeleton height="4.5rem" class="sessions-tab__skel" />
        <Skeleton height="4.5rem" class="sessions-tab__skel" />
      </div>
      <template v-else>
        <ul v-if="tokens.length" class="sessions-tab__list">
          <li v-for="t in tokens" :key="t.id" class="sessions-tab__item">
            <span class="sessions-tab__name">{{ t.name }}</span>
            <span class="sessions-tab__date">Criado: {{ formatDateTime(t.created_at) }}</span>
            <span class="sessions-tab__date">Último uso: {{ formatDateTime(t.last_used_at) }}</span>
          </li>
        </ul>
        <p v-else class="sessions-tab__empty">Nenhuma sessão ativa.</p>
        <Button
          class="sessions-tab__revoke"
          severity="danger"
          :label="revokeLoading ? 'Revogando...' : 'Sair de todos os dispositivos'"
          :loading="revokeLoading"
          :disabled="tokens.length <= 1"
          @click="revokeAll"
        />
      </template>
    </template>
  </Card>
</template>

<style scoped>
.sessions-tab__card :deep(.p-card-content) {
  padding: var(--spacing-lg) var(--spacing-xl);
}
.section-title {
  font-family: var(--font-display);
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.section-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
  line-height: var(--leading-normal);
}
.sessions-tab__skeleton {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) 0 var(--spacing-lg);
}
.sessions-tab__skel {
  border-radius: var(--radius-md);
}
.sessions-tab__list {
  list-style: none;
  padding: 0;
  margin: 0 0 var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.sessions-tab__item {
  padding: var(--spacing-lg);
  background: color-mix(in srgb, var(--color-bg-soft) 70%, var(--color-bg-card));
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.sessions-tab__item:hover {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}
.sessions-tab__name {
  font-weight: 600;
  font-size: var(--text-sm);
  color: var(--color-text);
}
.sessions-tab__date {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.sessions-tab__empty {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-lg);
}
.sessions-tab__revoke {
  margin-top: var(--spacing-sm);
}
.sessions-tab__revoke :deep(.p-button) {
  min-height: var(--touch-target-min);
}
.sessions-tab__revoke :deep(.p-button:focus-visible) {
  outline: none;
  box-shadow: var(--shadow-focus);
}
</style>
