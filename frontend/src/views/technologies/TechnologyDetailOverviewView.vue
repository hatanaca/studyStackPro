<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
const id = computed(() => route.params.id as string)

const cards = computed(() => {
  const tid = id.value
  if (!tid) return []
  return [
    {
      name: 'technology-detail-lembretes' as const,
      params: { id: tid },
      title: 'Lembretes',
      desc: 'Notas rápidas que não quer esquecer para esta tecnologia.',
    },
    {
      name: 'technology-detail-mural' as const,
      params: { id: tid },
      title: 'Mural',
      desc: 'Imagens e citações num quadro visual.',
    },
    {
      name: 'technology-detail-mapa' as const,
      params: { id: tid },
      title: 'Mapa de estudos',
      desc: 'Fluxograma de tópicos e dependências (local neste dispositivo).',
    },
    {
      name: 'technology-detail-sessoes' as const,
      params: { id: tid },
      title: 'Sessões',
      desc: 'Histórico e registo de sessões de estudo desta tecnologia.',
    },
  ]
})
</script>

<template>
  <div class="tech-detail-overview">
    <p class="tech-detail-overview__intro">
      Escolha uma secção para abrir a página correspondente.
    </p>
    <ul class="tech-detail-overview__grid" role="list">
      <li v-for="card in cards" :key="card.name" class="tech-detail-overview__cell">
        <RouterLink
          :to="{ name: card.name, params: card.params }"
          class="tech-detail-overview__card"
        >
          <span class="tech-detail-overview__card-title">{{ card.title }}</span>
          <span class="tech-detail-overview__card-desc">{{ card.desc }}</span>
        </RouterLink>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.tech-detail-overview__intro {
  margin: 0 0 var(--spacing-lg);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-normal);
}
.tech-detail-overview__grid {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
}
@media (min-width: 560px) {
  .tech-detail-overview__grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
.tech-detail-overview__card {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  height: 100%;
  padding: var(--spacing-lg);
  text-decoration: none;
  color: inherit;
  background: var(--surface-page-header-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--surface-page-header-shadow);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.tech-detail-overview__card:hover {
  border-color: color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
  box-shadow: var(--shadow-md);
}
.tech-detail-overview__card-title {
  font-family: var(--font-display);
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-text);
}
.tech-detail-overview__card-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
</style>
