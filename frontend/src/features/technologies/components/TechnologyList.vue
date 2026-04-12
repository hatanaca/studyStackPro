<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'
import TechnologyCard from './TechnologyCard.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import { useConfirm } from 'primevue/useconfirm'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useTechnologiesQuery, useInvalidateTechnologies } from '@/features/technologies/composables/useTechnologiesQuery'
import type { Technology } from '@/types/domain.types'

const TechnologyForm = defineAsyncComponent(() => import('./TechnologyForm.vue'))

const technologiesQuery = useTechnologiesQuery()
const store = useTechnologiesStore()
const invalidateTechnologies = useInvalidateTechnologies()
const confirm = useConfirm()
const editingTech = ref<Technology | null>(null)
const showForm = ref(false)

const loading = computed(() => technologiesQuery.isPending.value)
const hasError = computed(() => technologiesQuery.isError.value)

function openCreate() {
  editingTech.value = null
  showForm.value = true
}

function openEdit(tech: Technology) {
  editingTech.value = tech
  showForm.value = true
}

async function handleSubmit(payload: {
  name: string
  color: string
  description?: string
}) {
  if (editingTech.value) {
    await store.updateTechnology(editingTech.value.id, payload)
  } else {
    await store.createTechnology(payload)
  }
  showForm.value = false
  editingTech.value = null
  await invalidateTechnologies()
}

function handleCancel() {
  showForm.value = false
  editingTech.value = null
}

function handleDelete(tech: Technology) {
  confirm.require({
    header: 'Excluir tecnologia',
    message: `Excluir "${tech.name}"? Esta ação não pode ser desfeita.`,
    acceptLabel: 'Excluir',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      await store.deleteTechnology(tech.id)
      await invalidateTechnologies()
    },
  })
}
</script>

<template>
  <div class="technology-list">
    <div class="technology-list__header">
      <h2>Tecnologias</h2>
      <Button
        v-if="!showForm"
        label="Nova tecnologia"
        @click="openCreate"
      />
    </div>

    <TechnologyForm
      v-if="showForm"
      :model-value="editingTech"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />

    <div
      v-if="loading"
      class="loading"
      role="status"
      aria-live="polite"
      aria-label="Carregando tecnologias"
    >
      <Skeleton class="loading__skeleton" height="8rem" />
      <Skeleton class="loading__skeleton" height="8rem" />
      <Skeleton class="loading__skeleton" height="8rem" />
    </div>
    <ErrorCard
      v-else-if="hasError"
      title="Erro ao carregar tecnologias"
      message="Não foi possível carregar a lista de tecnologias."
      :on-retry="() => technologiesQuery.refetch()"
    />
    <div
      v-else-if="store.technologies.length"
      class="technology-list__grid"
    >
      <TechnologyCard
        v-for="t in store.technologies"
        :key="t.id"
        :technology="t"
        @edit="openEdit"
        @delete="handleDelete"
      />
    </div>
    <EmptyState
      v-else
      icon="⚡"
      title="Nenhuma tecnologia cadastrada"
      description="Adicione uma tecnologia para categorizar suas sessões de estudo."
      action-label="Nova tecnologia"
      :hide-action="false"
      @action="openCreate"
    />
  </div>
</template>

<style scoped>
.technology-list {
  padding: 0;
}
.technology-list__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--page-section-gap);
  flex-wrap: wrap;
  gap: var(--spacing-md);
  padding: var(--spacing-lg) var(--spacing-xl);
  background: var(--surface-page-header-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--surface-page-header-shadow);
}
.technology-list__header h2 {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  margin: 0;
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.technology-list__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-lg);
  margin-top: var(--spacing-lg);
}
@media (min-width: 640px) {
  .technology-list__grid {
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: var(--spacing-xl);
  }
}
.loading {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  padding: var(--spacing-xl);
  margin-top: var(--spacing-lg);
}
.loading__skeleton {
  border-radius: var(--radius-md);
}
.loading {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  background: color-mix(in srgb, var(--color-bg-soft) 48%, var(--color-bg-card));
  border: var(--empty-state-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}
</style>
