<script setup lang="ts">
import { ref, onMounted } from 'vue'
import GoalCard from './GoalCard.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import GoalForm from './GoalForm.vue'
import type { Goal } from '@/types/goals.types'
import type { CreateGoalPayload } from '@/types/goals.types'
import { useGoalsStore } from '@/stores/goals.store'
import { useToast } from '@/composables/useToast'
import { GOAL_TYPE_LABELS } from '@/types/goals.types'

const goalsStore = useGoalsStore()
const toast = useToast()
const showForm = ref(false)
const goalToEdit = ref<Goal | null>(null)
const goalToDelete = ref<Goal | null>(null)
const deleteLoading = ref(false)

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
  goalToDelete.value = goal
}

async function doDelete() {
  if (!goalToDelete.value) return
  deleteLoading.value = true
  try {
    await goalsStore.deleteGoal(goalToDelete.value.id)
    toast.success('Meta excluída.')
    goalToDelete.value = null
  } catch {
    toast.error(goalsStore.error ?? 'Erro ao excluir.')
  } finally {
    deleteLoading.value = false
  }
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
      <BaseButton
        @click="showForm = true"
      >
        Nova meta
      </BaseButton>
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
      <EmptyState
        v-else
        title="Nenhuma meta"
        description="Crie uma meta de estudo para acompanhar seu progresso."
        icon="🎯"
        action-label="Nova meta"
        :hide-action="false"
        @action="showForm = true"
      />
    </template>

    <BaseModal
      :show="showForm"
      title="Nova meta"
      @close="showForm = false"
    >
      <GoalForm
        :loading="false"
        @submit="handleCreate"
        @cancel="showForm = false"
      />
    </BaseModal>

    <BaseModal
      :show="!!goalToEdit"
      :title="goalToEdit ? `Editar: ${GOAL_TYPE_LABELS[goalToEdit.type]}` : ''"
      @close="goalToEdit = null"
    >
      <GoalForm
        v-if="goalToEdit"
        :initial-goal="goalToEdit"
        :loading="false"
        @update="handleUpdate"
        @cancel="goalToEdit = null"
      />
    </BaseModal>

    <ConfirmDialog
      :show="!!goalToDelete"
      title="Excluir meta"
      message="Tem certeza que deseja excluir esta meta? Esta ação não pode ser desfeita."
      confirm-label="Excluir"
      cancel-label="Cancelar"
      variant="danger"
      :loading="deleteLoading"
      @close="goalToDelete = null"
      @confirm="doDelete"
    />
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
