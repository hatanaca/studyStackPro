<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useToast } from '@/composables/useToast'
import { formatDateTime } from '@/utils/formatters'
import KeyValueList from '@/components/ui/KeyValueList.vue'
import type { StudySession } from '@/types/domain.types'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const session = ref<StudySession | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const id = computed(() => route.params.id as string)

onMounted(async () => {
  try {
    const res = await sessionsApi.getOne(id.value)
    if (res.data?.success && res.data?.data) {
      session.value = res.data.data
    } else {
      const msg = (res.data as { error?: { message?: string } })?.error?.message ?? 'Sessão não encontrada.'
      error.value = msg
      toast.error(msg)
      router.replace({ name: 'sessions' })
    }
  } catch (err: unknown) {
    const msg = getApiErrorMessage(err) || 'Sessão não encontrada.'
    error.value = msg
    toast.error(msg)
    router.replace({ name: 'sessions' })
  } finally {
    loading.value = false
  }
})

function goBack() {
  router.push({ name: 'sessions' })
}
</script>

<template>
  <div class="session-detail">
    <div
      v-if="loading"
      class="session-detail__loading"
    >
      Carregando...
    </div>
    <div
      v-else-if="error"
      class="session-detail__error"
    >
      {{ error }}
    </div>
    <template v-else-if="session">
      <div class="session-detail__header">
        <button
          type="button"
          class="session-detail__back"
          @click="goBack"
        >
          ← Voltar
        </button>
      </div>
      <div class="session-detail__card">
        <h2 class="session-detail__title">
          Sessão de estudo
        </h2>
        <KeyValueList
          :items="sessionMetaItems"
          layout="row"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.session-detail {
  padding: 0;
  max-width: var(--page-max-width-narrow);
}
.session-detail__header {
  margin-bottom: var(--spacing-md);
}
.session-detail__back {
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  font-weight: 500;
  box-shadow: var(--shadow-sm);
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.session-detail__back:hover {
  background: var(--color-bg-soft);
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.session-detail__card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg) var(--spacing-xl);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
}
.session-detail__title {
  font-size: var(--text-xl);
  font-weight: 600;
  margin-bottom: var(--spacing-md);
  color: var(--color-text);
  letter-spacing: -0.01em;
}
.session-detail__loading,
.session-detail__error {
  padding: var(--spacing-xl);
  text-align: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  background: color-mix(in srgb, var(--color-bg-soft) 50%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
}
@media (max-width: 480px) {
  .session-detail__card {
    padding: var(--spacing-md);
  }
}
</style>
