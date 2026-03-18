<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageView from '@/components/layout/PageView.vue'
import Skeleton from 'primevue/skeleton'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import TechnologyDetailMural from '@/features/technologies/components/TechnologyDetailMural.vue'
import TechnologyDetailReminders from '@/features/technologies/components/TechnologyDetailReminders.vue'
import { sessionsApi } from '@/api/modules/sessions.api'
import { technologiesApi } from '@/api/modules/technologies.api'
import { formatHours } from '@/utils/formatters'
import type { Technology } from '@/types/domain.types'

const route = useRoute()
const router = useRouter()

const technology = ref<Technology | null>(null)
const totalMinutes = ref(0)
const loading = ref(true)
const error = ref<string | null>(null)

const id = computed(() => route.params.id as string)

async function loadTechnology() {
  if (!id.value) return
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

async function loadTotalHours() {
  if (!id.value) return
  try {
    const { data } = await sessionsApi.list({ technology_id: id.value, per_page: 50 })
    if (data.success && Array.isArray(data.data)) {
      totalMinutes.value = data.data.reduce((sum, s) => sum + (s.duration_min ?? 0), 0)
    }
  } catch {
    totalMinutes.value = 0
  }
}

async function fetchData() {
  if (!id.value) return
  loading.value = true
  error.value = null
  await loadTechnology()
  if (technology.value) await loadTotalHours()
  loading.value = false
}

const totalHoursLabel = computed(() => formatHours(totalMinutes.value))

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
    :title="technology?.name ?? 'Detalhes'"
    :subtitle="technology?.description ?? undefined"
    narrow
  >
    <div
      v-if="loading"
      class="technology-detail__loading"
      role="status"
      aria-live="polite"
      aria-label="Carregando tecnologia"
    >
      <Skeleton class="technology-detail__skeleton" height="10rem" />
    </div>
    <template v-else-if="error">
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
    <template v-else-if="technology">
      <div
        class="technology-detail"
        :style="technology ? { '--tech-color': technology.color } : {}"
      >
        <div class="technology-detail__total">
          <BaseCard class="technology-detail__card" title="Total de horas">
            <p class="technology-detail__total-value">
              {{ totalHoursLabel }}
            </p>
          </BaseCard>
        </div>
        <div class="technology-detail__sections">
          <TechnologyDetailReminders :technology-id="technology.id" />
          <TechnologyDetailMural :technology-id="technology.id" />
        </div>
      </div>
    </template>
  </PageView>
</template>

<style scoped>
.technology-detail__loading {
  padding: var(--spacing-xl) 0;
}
.technology-detail__skeleton {
  min-height: 10rem;
  border-radius: var(--radius-md);
}
.technology-detail__back {
  margin-top: var(--spacing-xl);
}
.technology-detail {
  max-width: 100%;
}
.technology-detail__total {
  margin-bottom: var(--page-section-gap);
}
.technology-detail__card {
  padding: var(--spacing-xl);
}
.technology-detail__card-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-sm);
  letter-spacing: var(--tracking-wide);
}
.technology-detail__total-value {
  font-size: var(--text-2xl);
  font-weight: 700;
  color: var(--tech-color, var(--color-primary));
  margin: 0;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.technology-detail__sections {
  display: flex;
  flex-direction: column;
  gap: var(--page-section-gap);
}
</style>
