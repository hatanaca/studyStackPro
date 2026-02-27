<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import TechnologyCard from './TechnologyCard.vue'
import TechnologyForm from './TechnologyForm.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import type { Technology } from '@/types/domain.types'

const store = useTechnologiesStore()
const editingTech = ref<Technology | null>(null)
const showForm = ref(false)

const technologies = computed(() => store.technologies)
const loading = computed(() => store.loading)

onMounted(() => {
  store.fetchTechnologies()
})

function openCreate() {
  editingTech.value = null
  showForm.value = true
}

function openEdit(tech: Technology) {
  editingTech.value = tech
  showForm.value = true
}

function handleSubmit(payload: {
  name: string
  color: string
  description?: string
}) {
  if (editingTech.value) {
    store.updateTechnology(editingTech.value.id, payload)
  } else {
    store.createTechnology(payload)
  }
  showForm.value = false
  editingTech.value = null
}

function handleCancel() {
  showForm.value = false
  editingTech.value = null
}

async function handleDelete(tech: Technology) {
  if (!confirm(`Excluir "${tech.name}"?`)) return
  await store.deleteTechnology(tech.id)
}
</script>

<template>
  <div class="technology-list">
    <div class="technology-list__header">
      <h2>Tecnologias</h2>
      <BaseButton
        v-if="!showForm"
        @click="openCreate"
      >
        Nova tecnologia
      </BaseButton>
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
  margin-bottom: 1rem;
}
.technology-list__header h2 {
  font-size: 1.25rem;
  margin: 0;
}
.technology-list__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}
.loading,
.empty {
  color: #64748b;
  margin-top: 1rem;
}
</style>
