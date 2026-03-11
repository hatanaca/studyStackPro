<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import GoalCard from './GoalCard.vue'
import Message from 'primevue/message'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import GoalForm from './GoalForm.vue'
import type { Goal } from '@/types/goals.types'
import type { CreateGoalPayload } from '@/types/goals.types'
import { useGoalsStore } from '@/stores/goals.store'
import { useToast } from '@/composables/useToast'
import { GOAL_TYPE_LABELS } from '@/types/goals.types'

const goalsStore = useGoalsStore()
const toast = useToast()
const confirm = useConfirm()
const showForm = ref(false)
const goalToEdit = ref<Goal | null>(null)
const showEditModal = computed({
  get: () => !!goalToEdit.value,
  set: (v) => { if (!v) goalToEdit.value = null },
})

onMounted(() => {
  goalsStore.fetchGoals()
})

async function handleCreate(payload: CreateGoalPayload) {
  try {
    await goalsStore.createGoal(payload)
    toast.success('Meta criada com sucesso.')
    showForm.value = false
  } catch {
    toast.error(goalsStore.error ?? 'Erro ao criar meta.')
  }
}

function confirmDelete(goal: Goal) {
  confirm.require({
    header: 'Excluir meta',
    message: 'Tem certeza que deseja excluir esta meta? Esta ação não pode ser desfeita.',
    acceptLabel: 'Excluir',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await goalsStore.deleteGoal(goal.id)
        toast.success('Meta excluída.')
      } catch {
        toast.error(goalsStore.error ?? 'Erro ao excluir.')
      }
    },
  })
}

function openEdit(goal: Goal) {
  goalToEdit.value = goal
}

async function handleUpdate(payload: { id: string; target_value: number }) {
  try {
    await goalsStore.updateGoal(payload.id, { target_value: payload.target_value })
    toast.success('Meta atualizada.')
    goalToEdit.value = null
  } catch {
    toast.error(goalsStore.error ?? 'Erro ao atualizar meta.')
  }
}
</script>

<template>
  <div class="goal-list">
    <div class="goal-list__toolbar">
      <Button label="Nova meta" @click="showForm = true" />
    </div>
    <div
      v-if="goalsStore.loading"
      class="goal-list__loading"
    >
      Carregando metas...
    </div>
    <template v-else>
      <div
        v-if="goalsStore.items.length"
        class="goal-list__grid"
      >
        <GoalCard
          v-for="goal in goalsStore.items"
          :key="goal.id"
          :goal="goal"
          @edit="openEdit"
          @delete="confirmDelete"
        />
      </div>
      <Message
        v-else
        severity="info"
        :closable="false"
        class="goal-list__empty"
      >
        <strong>Nenhuma meta</strong><br>
        Crie uma meta de estudo para acompanhar seu progresso.
        <Button label="Nova meta" size="small" class="mt-2" @click="showForm = true" />
      </Message>
    </template>

    <Dialog
      v-model:visible="showForm"
      header="Nova meta"
      modal
      :style="{ width: 'min(92vw, 28rem)' }"
      @hide="showForm = false"
    >
      <GoalForm
        :loading="false"
        @submit="handleCreate"
        @cancel="showForm = false"
      />
    </Dialog>

    <Dialog
      v-model:visible="showEditModal"
      :header="goalToEdit ? `Editar: ${GOAL_TYPE_LABELS[goalToEdit.type]}` : ''"
      modal
      :style="{ width: 'min(92vw, 28rem)' }"
      @hide="goalToEdit = null"
    >
      <GoalForm
        v-if="goalToEdit"
        :initial-goal="goalToEdit"
        :loading="false"
        @update="handleUpdate"
        @cancel="goalToEdit = null"
      />
    </Dialog>
  </div>
</template>

<style scoped>
.goal-list__toolbar {
  margin-bottom: var(--page-section-gap);
  padding: var(--spacing-md) var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
.goal-list__loading {
  padding: var(--spacing-xl);
  text-align: center;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  background: color-mix(in srgb, var(--color-bg-soft) 50%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
}
.goal-list__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--widget-gap);
}
@media (min-width: 640px) {
  .goal-list__grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (min-width: 1024px) {
  .goal-list__grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
