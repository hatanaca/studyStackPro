<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import StudyPathFlow from '@/features/study-path/components/StudyPathFlow.vue'

const route = useRoute()
const id = computed(() => route.params.id as string)
</script>

<template>
  <div class="tech-detail-subpage tech-detail-map">
    <header class="tech-detail-map__header">
      <p class="tech-detail-map__desc">
        Mapa local: barra fixa no topo do editor com todas as ações. Clique no fundo do grafo ou
        botão direito para o menu de edição (sem menu do navegador).
      </p>
    </header>
    <StudyPathFlow :technology-id="id" />
  </div>
</template>

<style scoped>
.tech-detail-subpage {
  max-width: 100%;
}
.tech-detail-map__header {
  margin-bottom: var(--page-section-gap);
  padding: var(--spacing-lg) var(--spacing-xl);
  background: var(--surface-page-header-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--surface-page-header-shadow);
}
.tech-detail-map__desc {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-normal);
}
</style>
