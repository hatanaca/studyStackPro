<script setup lang="ts">
/**
 * Gestão de baralhos e cartões: criar/remover baralhos, adicionar cartões
 * (frente/verso em LaTeX) e remover cartões existentes.
 */
import { ref } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import FormulaText from '@/components/math/FormulaText.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import StackSkeleton from '@/components/ui/StackSkeleton.vue'
import { useToast } from '@/composables/useToast'
import {
  useCreateCardMutation,
  useCreateDeckMutation,
  useDeckCardsQuery,
  useDecksQuery,
  useDeleteCardMutation,
  useDeleteDeckMutation,
} from '@/features/flashcards/composables/useFlashcardsQuery'
import type { FlashcardDeck } from '@/features/flashcards/types/flashcards.types'

const props = defineProps<{ selected: FlashcardDeck | null }>()
const emit = defineEmits<{ (e: 'select', deck: FlashcardDeck): void }>()

const toast = useToast()
const { data: decks, isLoading } = useDecksQuery()
const createDeckMutation = useCreateDeckMutation()
const deleteDeckMutation = useDeleteDeckMutation()
const createCardMutation = useCreateCardMutation()
const deleteCardMutation = useDeleteCardMutation()

const newDeckName = ref('')
const front = ref('')
const back = ref('')

const { data: cards, isLoading: cardsLoading } = useDeckCardsQuery(() => props.selected?.id ?? null)

async function createDeck() {
  const name = newDeckName.value.trim()
  if (!name) return
  try {
    const deck = await createDeckMutation.mutateAsync(name)
    newDeckName.value = ''
    emit('select', deck)
    toast.success('Baralho criado.')
  } catch {
    toast.error('Falha ao criar o baralho.')
  }
}

async function deleteDeck(deck: FlashcardDeck) {
  try {
    await deleteDeckMutation.mutateAsync(deck.id)
    toast.success('Baralho excluído.')
  } catch {
    toast.error('Falha ao excluir o baralho.')
  }
}

async function addCard() {
  if (!props.selected || !front.value.trim() || !back.value.trim()) return
  try {
    await createCardMutation.mutateAsync({
      deckId: props.selected.id,
      front: front.value.trim(),
      back: back.value.trim(),
    })
    front.value = ''
    back.value = ''
    toast.success('Cartão adicionado.')
  } catch {
    toast.error('Falha ao adicionar o cartão.')
  }
}

async function removeCard(id: string) {
  try {
    await deleteCardMutation.mutateAsync(id)
  } catch {
    toast.error('Falha ao excluir o cartão.')
  }
}
</script>

<template>
  <div class="deck-manager">
    <div class="deck-manager__create">
      <InputText
        v-model="newDeckName"
        placeholder="Nome do novo baralho"
        class="deck-manager__deck-input"
        @keyup.enter="createDeck"
      />
      <Button
        label="Criar"
        icon="pi pi-plus"
        :loading="createDeckMutation.isPending.value"
        @click="createDeck"
      />
    </div>

    <StackSkeleton v-if="isLoading" :count="3" />

    <EmptyState v-else-if="!decks?.length" title="Crie seu primeiro baralho de flashcards." />

    <ul v-else class="deck-manager__decks">
      <li v-for="deck in decks" :key="deck.id">
        <button
          type="button"
          class="deck-manager__deck"
          :class="{ 'deck-manager__deck--active': selected?.id === deck.id }"
          @click="emit('select', deck)"
        >
          <span class="deck-manager__deck-main">
            <span class="deck-manager__deck-name">{{ deck.name }}</span>
            <span class="deck-manager__deck-meta">
              <Tag :value="`${deck.cards_count ?? 0} cartões`" severity="secondary" rounded />
              <Tag
                v-if="(deck.due_count ?? 0) > 0"
                :value="`${deck.due_count} para revisar`"
                severity="warn"
                rounded
              />
            </span>
          </span>
        </button>
      </li>
    </ul>

    <template v-if="selected">
      <div class="deck-manager__cards-head">
        <h3 class="deck-manager__cards-title">Cartões de {{ selected.name }}</h3>
        <Button
          label="Excluir baralho"
          icon="pi pi-trash"
          severity="danger"
          text
          size="small"
          :loading="deleteDeckMutation.isPending.value"
          @click="deleteDeck(selected)"
        />
      </div>

      <StackSkeleton v-if="cardsLoading" :count="2" />

      <ul v-else-if="cards?.length" class="deck-manager__cards">
        <li v-for="card in cards" :key="card.id" class="deck-manager__card">
          <div class="deck-manager__card-sides">
            <FormulaText :latex="card.front_latex" />
            <span class="deck-manager__card-arrow">→</span>
            <FormulaText :latex="card.back_latex" />
          </div>
          <Button
            icon="pi pi-times"
            severity="danger"
            text
            size="small"
            aria-label="Excluir cartão"
            @click="removeCard(card.id)"
          />
        </li>
      </ul>

      <div class="deck-manager__add-card">
        <Textarea
          v-model="front"
          placeholder="Frente (LaTeX, ex.: \\frac{d}{dx} x^n)"
          rows="2"
          auto-resize
        />
        <Textarea
          v-model="back"
          placeholder="Verso (LaTeX, ex.: n x^{n-1})"
          rows="2"
          auto-resize
        />
        <Button
          label="Adicionar cartão"
          icon="pi pi-plus"
          :loading="createCardMutation.isPending.value"
          :disabled="!front.trim() || !back.trim()"
          @click="addCard"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.deck-manager {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.deck-manager__create {
  display: flex;
  gap: var(--spacing-xs);
}
.deck-manager__deck-input {
  flex: 1;
}
.deck-manager__decks {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.deck-manager__deck {
  display: flex;
  align-items: center;
  width: 100%;
  padding: var(--spacing-md);
  text-align: left;
  background: var(--color-bg-soft);
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  color: inherit;
  cursor: pointer;
  transition:
    border-color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.deck-manager__deck:hover {
  border-color: var(--color-border);
}
.deck-manager__deck--active {
  border-color: color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
  background: var(--color-primary-soft);
}
.deck-manager__deck-main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.deck-manager__deck-name {
  font-weight: 600;
}
.deck-manager__deck-meta {
  display: flex;
  gap: var(--spacing-xs);
}
.deck-manager__cards-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-sm);
}
.deck-manager__cards-title {
  margin: 0;
  font-size: var(--text-base);
}
.deck-manager__cards {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.deck-manager__card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}
.deck-manager__card-sides {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  min-width: 0;
  overflow: hidden;
}
.deck-manager__card-arrow {
  color: var(--color-text-muted);
}
.deck-manager__add-card {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
</style>
