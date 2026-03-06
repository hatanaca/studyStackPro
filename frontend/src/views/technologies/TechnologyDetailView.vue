<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseBreadcrumb from '@/components/ui/BaseBreadcrumb.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import TechnologyDetailReminders from '@/features/technologies/components/TechnologyDetailReminders.vue'
import TechnologyDetailMural from '@/features/technologies/components/TechnologyDetailMural.vue'
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

const breadcrumbItems = computed(() => [
  { label: 'Tecnologias', to: '/technologies' },
  { label: technology.value?.name ?? 'Detalhes', to: undefined },
])

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
    const { data } = await sessionsApi.list({ technology_id: id.value, per_page: 500 })
    if (data.success && Array.isArray(data.data)) {
      totalMinutes.value = data.data.reduce((sum, s) => sum + (s.duration_min ?? 0), 0)
    }
  } catch {
    totalMinutes.value = 0
  }
}

const totalHoursLabel = computed(() => formatHours(totalMinutes.value))

onMounted(async () => {
  loading.value = true
  error.value = null
  await loadTechnology()
  if (technology.value) await loadTotalHours()
  loading.value = false
})

watch(id, () => {
  loadTechnology().then(() => technology.value && loadTotalHours())
})

function goBack() {
  router.push({ name: 'technologies' })
}
</script>

<template>
  <div
    class="technology-detail"
    :style="technology ? { '--tech-color': technology.color } : {}"
  >
    <BaseBreadcrumb :items="breadcrumbItems" />
    <div
      v-if="loading"
      class="technology-detail__loading"
    >
      Carregando...
    </div>
    <div
      v-else-if="error"
      class="technology-detail__error"
    >
      <p>{{ error }}</p>
      <BaseButton
        variant="outline"
        @click="goBack"
      >
        Voltar
      </BaseButton>
    </div>
    <template v-else-if="technology">
      <header class="technology-detail__header">
        <button
          type="button"
          class="technology-detail__back"
          @click="goBack"
        >
          ← Voltar
        </button>
        <div class="technology-detail__title-wrap">
          <span class="technology-detail__bar" />
          <h1 class="technology-detail__title">
            {{ technology.name }}
          </h1>
          <p
            v-if="technology.description"
            class="technology-detail__desc"
          >
            {{ technology.description }}
          </p>
        </div>
      </header>

      <div class="technology-detail__total">
        <BaseCard class="technology-detail__card">
          <h2 class="technology-detail__card-title">
            Total de horas
          </h2>
          <p class="technology-detail__total-value">
            {{ totalHoursLabel }}
          </p>
        </BaseCard>
      </div>

      <div class="technology-detail__sections">
        <TechnologyDetailReminders :technology-id="technology.id" />
        <TechnologyDetailMural :technology-id="technology.id" />
      </div>
    </template>
  </div>
</template>

<style scoped>
.technology-detail {
  max-width: var(--page-max-width-narrow);
}
.technology-detail__loading,
.technology-detail__error {
  padding: var(--spacing-xl);
  text-align: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  background: color-mix(in srgb, var(--color-bg-soft) 50%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
}
.technology-detail__error p {
  margin-bottom: var(--spacing-md);
}
.technology-detail__header {
  margin-bottom: var(--page-header-margin-bottom);
}
.technology-detail__back {
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  margin-bottom: var(--spacing-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  font-weight: 500;
  cursor: pointer;
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.technology-detail__back:hover {
  background: var(--color-bg-soft);
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.technology-detail__title-wrap {
  position: relative;
  padding-left: var(--spacing-md);
}
.technology-detail__bar {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  border-radius: 2px;
  background: var(--tech-color, var(--color-primary));
}
.technology-detail__title {
  font-size: var(--text-xl);
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
  letter-spacing: -0.02em;
}
.technology-detail__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: var(--spacing-sm) 0 0;
  line-height: 1.5;
}
.technology-detail__total {
  margin-bottom: var(--page-section-gap);
}
.technology-detail__card-title {
  font-size: var(--widget-title-size);
  font-weight: 600;
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-xs);
}
.technology-detail__total-value {
  font-size: var(--text-2xl);
  font-weight: 700;
  color: var(--tech-color, var(--color-primary));
  margin: 0;
  letter-spacing: -0.02em;
}
.technology-detail__sections {
  display: flex;
  flex-direction: column;
  gap: var(--page-section-gap);
}
</style>
