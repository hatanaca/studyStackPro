<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'
import TechnologyCard from './TechnologyCard.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import { useConfirm } from 'primevue/useconfirm'
import { useTechnologiesStore } from '@/stores/technologies.store'
import {
  useTechnologiesQuery,
  useInvalidateTechnologies,
} from '@/features/technologies/composables/useTechnologiesQuery'
import { useToast } from '@/composables/useToast'
import { getApiErrorMessage } from '@/api/client'
import type { Technology } from '@/types/domain.types'

const TechnologyForm = defineAsyncComponent(() => import('./TechnologyForm.vue'))

const technologiesQuery = useTechnologiesQuery()
const store = useTechnologiesStore()
const invalidateTechnologies = useInvalidateTechnologies()
const confirm = useConfirm()
const toast = useToast()
const editingTech = ref<Technology | null>(null)
const showForm = ref(false)
const formSubmitting = ref(false)
const technologyFormRef = ref<{ setError: (msg: string) => void } | null>(null)

/** Trecho seguro para mensagens de confirmação (evita quebra de linha / marcadores). */
function safeConfirmSnippet(text: string, max = 100): string {
  return text
    .replace(/[\r\n<>]/g, ' ')
    .trim()
    .slice(0, max)
}

const loading = computed(() => technologiesQuery.isPending.value)
const hasError = computed(() => technologiesQuery.isError.value)
const formDialogTitle = computed(() =>
  editingTech.value ? 'Editar tecnologia' : 'Nova tecnologia'
)

function openEdit(tech: Technology) {
  editingTech.value = tech
  showForm.value = true
}

async function handleSubmit(payload: { name: string; color: string; description?: string }) {
  if (formSubmitting.value) return
  const wasEdit = !!editingTech.value
  formSubmitting.value = true
  try {
    if (editingTech.value) {
      await store.updateTechnology(editingTech.value.id, payload)
    } else {
      await store.createTechnology(payload)
    }
    showForm.value = false
    editingTech.value = null
    await invalidateTechnologies()
    toast.success(wasEdit ? 'Tecnologia atualizada.' : 'Tecnologia criada.')
  } catch (err) {
    const msg = getApiErrorMessage(err)
    toast.error(msg)
    technologyFormRef.value?.setError(msg)
  } finally {
    formSubmitting.value = false
  }
}

function handleCancel() {
  showForm.value = false
  editingTech.value = null
}

function openCreate() {
  editingTech.value = null
  showForm.value = true
}

function handleDelete(tech: Technology) {
  const label = safeConfirmSnippet(tech.name)
  confirm.require({
    header: 'Excluir tecnologia',
    message: `Excluir "${label}"? Esta ação não pode ser desfeita.`,
    acceptLabel: 'Excluir',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await store.deleteTechnology(tech.id)
        await invalidateTechnologies()
        toast.success('Tecnologia excluída.')
      } catch (err) {
        toast.error(getApiErrorMessage(err))
      }
    },
  })
}

defineExpose({ openCreate })
</script>

<template>
  <div class="technology-list">
    <Dialog
      v-model:visible="showForm"
      modal
      dismissable-mask
      :header="formDialogTitle"
      :style="{ width: 'min(90vw, 440px)' }"
      :breakpoints="{ '640px': '95vw' }"
      @hide="handleCancel"
    >
      <TechnologyForm
        v-if="showForm"
        ref="technologyFormRef"
        :key="editingTech?.id ?? 'new'"
        :model-value="editingTech"
        :submitting="formSubmitting"
        @submit="handleSubmit"
        @cancel="handleCancel"
      />
    </Dialog>

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
    <div v-else-if="store.technologies.length" class="technology-list__grid">
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
.technology-list__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  margin-top: 0;
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
