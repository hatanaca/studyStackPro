<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()

const id = computed(() => route.params.id as string)

const links = computed(() => {
  const tid = id.value
  if (!tid) return []
  return [
    { name: 'technology-detail' as const, label: 'Visão geral', params: { id: tid } },
    { name: 'technology-detail-lembretes' as const, label: 'Lembretes', params: { id: tid } },
    { name: 'technology-detail-mural' as const, label: 'Mural', params: { id: tid } },
    { name: 'technology-detail-mapa' as const, label: 'Mapa de estudos', params: { id: tid } },
    { name: 'technology-detail-sessoes' as const, label: 'Sessões', params: { id: tid } },
  ]
})
</script>

<template>
  <nav class="tech-detail-nav" aria-label="Secções da tecnologia">
    <ul class="tech-detail-nav__list">
      <li v-for="item in links" :key="item.name" class="tech-detail-nav__item">
        <RouterLink
          :to="{ name: item.name, params: item.params }"
          class="tech-detail-nav__link"
          active-class="tech-detail-nav__link--active"
        >
          {{ item.label }}
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>

<style scoped>
.tech-detail-nav {
  margin-bottom: var(--page-section-gap);
}
.tech-detail-nav__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
}
.tech-detail-nav__link {
  display: inline-block;
  padding: var(--spacing-sm) var(--spacing-md);
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text-muted);
  text-decoration: none;
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  transition:
    color var(--duration-fast) ease,
    background var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.tech-detail-nav__link:hover {
  color: var(--color-text);
  background: var(--color-bg-soft);
}
.tech-detail-nav__link--active {
  color: var(--color-primary);
  background: color-mix(in srgb, var(--color-primary) 12%, transparent);
  border-color: color-mix(in srgb, var(--color-primary) 35%, transparent);
}
</style>
