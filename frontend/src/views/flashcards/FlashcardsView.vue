<script setup lang="ts">
/**
 * View de Flashcards: baralhos (criação/gestão de cartões) à esquerda,
 * fila de revisão FSRS do baralho selecionado à direita.
 */
import { ref } from 'vue'
import DeckManager from '@/features/flashcards/components/DeckManager.vue'
import StudyQueue from '@/features/flashcards/components/StudyQueue.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import type { FlashcardDeck } from '@/features/flashcards/types/flashcards.types'

const selected = ref<FlashcardDeck | null>(null)
</script>

<template>
  <section class="flashcards">
    <header class="flashcards__header">
      <h1 class="flashcards__title">Flashcards</h1>
      <p class="flashcards__subtitle">
        Repetição espaçada com o algoritmo FSRS (Anki) para fórmulas, teoremas e definições.
      </p>
    </header>

    <div class="flashcards__layout">
      <aside class="flashcards__side">
        <DeckManager :selected="selected" @select="selected = $event" />
      </aside>

      <div class="flashcards__main">
        <StudyQueue v-if="selected" :key="selected.id" :deck-id="selected.id" />
        <EmptyState v-else title="Selecione um baralho para revisar." />
      </div>
    </div>
  </section>
</template>

<style scoped>
.flashcards {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}
.flashcards__header {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.flashcards__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--text-2xl);
}
.flashcards__subtitle {
  margin: 0;
  color: var(--color-text-secondary);
}
.flashcards__layout {
  display: grid;
  grid-template-columns: minmax(16rem, 2fr) minmax(18rem, 3fr);
  gap: var(--spacing-lg);
  align-items: start;
}
@media (max-width: 860px) {
  .flashcards__layout {
    grid-template-columns: 1fr;
  }
}
.flashcards__side,
.flashcards__main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}
</style>
