<script setup lang="ts">
/**
 * Fila de revisão de um baralho: cartões vencidos, flip frente/verso e
 * avaliação FSRS (De novo / Difícil / Bom / Fácil). O próximo agendamento
 * é calculado com ts-fsrs e persistido via API.
 */
import { ref, watch } from 'vue'
import Button from 'primevue/button'
import FormulaText from '@/components/math/FormulaText.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import StackSkeleton from '@/components/ui/StackSkeleton.vue'
import { useToast } from '@/composables/useToast'
import { RATING_LABELS, scheduleNext } from '@/features/flashcards/composables/useFsrs'
import { useDueQuery, useReviewMutation } from '@/features/flashcards/composables/useFlashcardsQuery'
import type { Flashcard } from '@/features/flashcards/types/flashcards.types'

const props = defineProps<{ deckId: string }>()

const toast = useToast()
const { data: dueCards, isLoading, isError, refetch } = useDueQuery(() => props.deckId)
const reviewMutation = useReviewMutation()

const queue = ref<Flashcard[]>([])
const index = ref(0)
const flipped = ref(false)

watch(
  () => dueCards.value,
  (cards) => {
    if (cards) {
      queue.value = [...cards]
      index.value = 0
      flipped.value = false
    }
  },
  { immediate: true }
)

const current = () => queue.value[index.value] ?? null

async function rate(rating: number) {
  const card = current()
  if (!card) return
  try {
    const { state, dueAt } = scheduleNext(card.scheduling_state, rating)
    await reviewMutation.mutateAsync({ id: card.id, rating, stateAfter: state, dueAt })
  } catch {
    toast.error('Falha ao registrar a revisão.')
    return
  }
  queue.value.splice(index.value, 1)
  if (queue.value.length === 0) {
    await refetch()
  }
  flipped.value = false
}
</script>

<template>
  <div class="study-queue">
    <StackSkeleton v-if="isLoading" :count="2" />

    <div v-else-if="isError" class="study-queue__error">
      <p>Não foi possível carregar as revisões.</p>
      <Button label="Tentar de novo" icon="pi pi-refresh" size="small" @click="() => refetch()" />
    </div>

    <EmptyState
      v-else-if="!queue.length"
      title="Nada para revisar agora. Volte mais tarde ou adicione novos cartões."
    />

    <template v-else>
      <div class="study-queue__counter">
        {{ index + 1 }} de {{ queue.length }}
      </div>

      <div class="study-queue__card" @click="flipped = !flipped">
        <FormulaText
          v-if="flipped"
          :latex="current()!.back_latex"
          display
          class="study-queue__face"
        />
        <FormulaText
          v-else
          :latex="current()!.front_latex"
          display
          class="study-queue__face"
        />
        <span class="study-queue__flip-hint">
          {{ flipped ? 'Frente' : 'Mostrar resposta' }}
        </span>
      </div>

      <div v-if="flipped" class="study-queue__ratings">
        <Button
          v-for="r in RATING_LABELS"
          :key="r.value"
          :label="r.label"
          :severity="
            r.value === 1 ? 'danger' : r.value === 2 ? 'warn' : r.value === 3 ? 'success' : 'info'
          "
          :loading="reviewMutation.isPending.value"
          @click="rate(r.value)"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.study-queue {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
.study-queue__counter {
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  text-align: center;
}
.study-queue__card {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 14rem;
  padding: var(--spacing-xl);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  cursor: pointer;
  user-select: none;
  transition:
    border-color var(--duration-fast) ease,
    transform var(--duration-fast) ease;
}
.study-queue__card:hover {
  border-color: color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
}
.study-queue__face {
  font-size: var(--text-xl);
}
.study-queue__flip-hint {
  position: absolute;
  bottom: var(--spacing-sm);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.study-queue__ratings {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--spacing-xs);
}
@media (max-width: 640px) {
  .study-queue__ratings {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
