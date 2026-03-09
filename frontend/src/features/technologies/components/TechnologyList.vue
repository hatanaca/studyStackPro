<script setup lang="ts">
import { ref, computed } from 'vue'
import TechnologyCard from './TechnologyCard.vue'
import TechnologyForm from './TechnologyForm.vue'
import Button from 'primevue/button'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useTechnologiesQuery, useInvalidateTechnologies } from '@/features/technologies/composables/useTechnologiesQuery'
import type { Technology } from '@/types/domain.types'

const technologiesQuery = useTechnologiesQuery()
const store = useTechnologiesStore()
const invalidateTechnologies = useInvalidateTechnologies()
const editingTech = ref<Technology | null>(null)
const showForm = ref(false)

const technologies = computed(() => store.technologies)
const loading = computed(() => technologiesQuery.isPending.value)

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

async function handleDelete(tech: Technology) {
  if (!confirm(`Excluir "${tech.name}"?`)) return
  await store.deleteTechnology(tech.id)
  await invalidateTechnologies()
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
    >
      Carregando...
    </div>
    <div
      v-else-if="technologies.length"
      class="technology-list__grid"
    >
      <TechnologyCard
        v-for="t in technologies"
        :key="t.id"
        :technology="t"
        @edit="openEdit"
        @delete="handleDelete"
      />
    </div>
    <p
      v-else
      class="empty"
    >
      Nenhuma tecnologia cadastrada.
    </p>
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
  gap: var(--spacing-sm);
  padding: var(--spacing-md) var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
.technology-list__header h2 {
  font-size: var(--text-lg);
  font-weight: 600;
  margin: 0;
  color: var(--color-text);
  letter-spacing: -0.01em;
}
.technology-list__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--widget-gap);
  margin-top: var(--spacing-md);
}
@media (min-width: 640px) {
  .technology-list__grid {
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: var(--spacing-md);
  }
}
.loading,
.empty {
  padding: var(--spacing-lg);
  text-align: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  background: color-mix(in srgb, var(--color-bg-soft) 50%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
  margin-top: var(--spacing-md);
}
</style>
