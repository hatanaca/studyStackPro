<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageView from '@/components/layout/PageView.vue'
import Skeleton from 'primevue/skeleton'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import TechnologyDetailMural from '@/features/technologies/components/TechnologyDetailMural.vue'
import TechnologyDetailReminders from '@/features/technologies/components/TechnologyDetailReminders.vue'
import SessionList from '@/features/sessions/components/SessionList.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useTechnologiesQuery } from '@/features/technologies/composables/useTechnologiesQuery'
import { technologiesApi } from '@/api/modules/technologies.api'
import type { Technology } from '@/types/domain.types'

const route = useRoute()
const router = useRouter()

const technologiesStore = useTechnologiesStore()
// Garante que a store está populada (necessário para o formulário de edição de sessões)
useTechnologiesQuery()

const technology = ref<Technology | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const id = computed(() => route.params.id as string)

async function loadTechnology() {
  if (!id.value) return
  // Caminho rápido: tecnologia já está na store (usuário veio da lista)
  const cached = technologiesStore.technologies.find((t) => t.id === id.value)
  if (cached) {
    technology.value = cached
    return
  }
  // Caminho lento: busca na API (acesso direto / deep link)
  try {
    const { data } = await technologiesApi.getOne(id.value)
    if (data.success && data.data) {
      technology.value = data.data
    } else {
      error.value = 'Tecnologia não encontrada.'
    }
  } catch {
    error.value = 'Tecnologia não encontrada.'
  }
}

async function fetchData() {
  if (!id.value) return
  loading.value = true
  error.value = null
  await loadTechnology()
  loading.value = false
}

const breadcrumbItems = computed(() => [
  { label: 'Dashboard', to: '/' },
  { label: 'Tecnologias', to: '/technologies' },
  { label: technology.value?.name ?? 'Detalhes', to: undefined },
])

onMounted(() => {
  fetchData()
})

watch(id, () => {
  fetchData()
})

function goBack() {
  router.push({ name: 'technologies' })
}
</script>

<template>
  <PageView
    :breadcrumb="breadcrumbItems"
    :title="loading ? '' : (technology?.name ?? 'Detalhes')"
    :subtitle="technology?.description ?? undefined"
    narrow
  >
    <!-- Cabeçalho da tecnologia: só bloqueia o título, não o conteúdo -->
    <div
      v-if="loading"
      class="technology-detail__header-skeleton"
      role="status"
      aria-live="polite"
      aria-label="Carregando tecnologia"
    >
      <Skeleton width="12rem" height="1.5rem" class="technology-detail__skeleton" />
    </div>

    <template v-if="error">
      <ErrorCard
        :message="error"
        :on-retry="fetchData"
      />
      <div class="technology-detail__back">
        <BaseButton
          variant="secondary"
          type="button"
          @click="goBack"
        >
          Voltar para Tecnologias
        </BaseButton>
      </div>
    </template>

    <!-- Conteúdo monta imediatamente usando o id da rota; não espera technology -->
    <template v-else>
      <div
        class="technology-detail"
        :style="technology ? { '--tech-color': technology.color } : {}"
      >
        <div class="technology-detail__sections">
          <TechnologyDetailReminders :technology-id="id" />
          <TechnologyDetailMural :technology-id="id" />
        </div>
        <div class="technology-detail__sessions">
          <SessionList :technology-id="id" />
        </div>
      </div>
    </template>
  </PageView>
</template>

<style scoped>
.technology-detail__header-skeleton {
  margin-bottom: var(--spacing-lg);
}
.technology-detail__skeleton {
  border-radius: var(--radius-sm);
}
.technology-detail__back {
  margin-top: var(--spacing-xl);
}
.technology-detail {
  max-width: 100%;
}
.technology-detail__sections {
  display: flex;
  flex-direction: column;
  gap: var(--page-section-gap);
  margin-bottom: var(--page-section-gap);
}
.technology-detail__sessions {
  margin-top: var(--page-section-gap);
}
</style>
