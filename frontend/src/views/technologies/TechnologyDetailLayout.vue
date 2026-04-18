<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import PageView from '@/components/layout/PageView.vue'
import Skeleton from 'primevue/skeleton'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import TechnologyDetailSubNav from '@/features/technologies/components/TechnologyDetailSubNav.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useTechnologiesQuery } from '@/features/technologies/composables/useTechnologiesQuery'
import { technologiesApi } from '@/api/modules/technologies.api'
import type { Technology } from '@/types/domain.types'

const route = useRoute()
const router = useRouter()

const technologiesStore = useTechnologiesStore()
useTechnologiesQuery()

const technology = ref<Technology | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const id = computed(() => route.params.id as string)

const sectionLabel = computed(() => {
  const map: Record<string, string> = {
    'technology-detail': '',
    'technology-detail-lembretes': 'Lembretes',
    'technology-detail-mural': 'Mural',
    'technology-detail-mapa': 'Mapa de estudos',
    'technology-detail-sessoes': 'Sessões',
  }
  return map[route.name as string] ?? ''
})

async function loadTechnology() {
  if (!id.value) return
  const cached = technologiesStore.technologies.find((t) => t.id === id.value)
  if (cached) {
    technology.value = cached
    return
  }
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

const breadcrumbItems = computed(() => {
  const items: { label: string; to?: string }[] = [
    { label: 'Dashboard', to: '/' },
    { label: 'Tecnologias', to: '/technologies' },
    { label: technology.value?.name ?? '…', to: `/technologies/${id.value}` },
  ]
  const sec = sectionLabel.value
  if (sec) items.push({ label: sec })
  return items
})

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
    <div
      v-if="loading"
      class="technology-detail-layout__header-skeleton"
      role="status"
      aria-live="polite"
      aria-label="Carregando tecnologia"
    >
      <Skeleton width="12rem" height="1.5rem" class="technology-detail-layout__skeleton" />
    </div>

    <template v-if="error">
      <ErrorCard :message="error" :on-retry="fetchData" />
      <div class="technology-detail-layout__back">
        <BaseButton variant="secondary" type="button" @click="goBack">
          Voltar para Tecnologias
        </BaseButton>
      </div>
    </template>

    <template v-else>
      <div
        class="technology-detail-layout"
        :style="technology ? { '--tech-color': technology.color } : {}"
      >
        <TechnologyDetailSubNav />
        <RouterView />
      </div>
    </template>
  </PageView>
</template>

<style scoped>
.technology-detail-layout__header-skeleton {
  margin-bottom: var(--spacing-lg);
}
.technology-detail-layout__skeleton {
  border-radius: var(--radius-sm);
}
.technology-detail-layout__back {
  margin-top: var(--spacing-xl);
}
.technology-detail-layout {
  max-width: 100%;
}
</style>
