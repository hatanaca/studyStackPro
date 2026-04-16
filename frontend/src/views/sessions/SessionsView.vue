<script setup lang="ts">
import { defineAsyncComponent, h } from 'vue'
import Skeleton from 'primevue/skeleton'
import SessionList from '@/features/sessions/components/SessionList.vue'
import PageView from '@/components/layout/PageView.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useTechnologiesQuery } from '@/features/technologies/composables/useTechnologiesQuery'

const TechnologyStudyWidget = defineAsyncComponent({
  loader: () => import('@/features/sessions/components/TechnologyStudyWidget.vue'),
  loadingComponent: {
    name: 'TechnologyStudyWidgetSkeleton',
    setup() {
      return () =>
        h(Skeleton, {
          width: '100%',
          height: '9rem',
          borderRadius: 'var(--radius-lg)',
        })
    },
  },
  delay: 80,
})

useTechnologiesQuery()
const technologiesStore = useTechnologiesStore()

function staggerMs(index: number): number {
  return Math.min(index * 45, 900)
}
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Sessões' }]"
    title="Sessões"
    subtitle="Histórico de sessões de estudo e atalhos por tecnologia."
  >
    <template #hint>
      Clique em uma tecnologia abaixo para filtrar o histórico ou acessar o detalhe.
    </template>
    <section v-if="technologiesStore.technologies.length" class="sessions-view__by-tech">
      <header class="sessions-view__section-header">
        <h2 class="sessions-view__section-title">Por tecnologia</h2>
        <p class="sessions-view__section-desc">
          Acesso rápido ao tempo estudado por tecnologia e filtros na lista.
        </p>
      </header>
      <div class="sessions-view__widgets">
        <TechnologyStudyWidget
          v-for="(tech, idx) in technologiesStore.technologies"
          :key="tech.id"
          :technology="tech"
          :stagger-ms="staggerMs(idx)"
        />
      </div>
    </section>
    <SessionList />
  </PageView>
</template>

<style scoped>
.sessions-view__by-tech {
  margin-bottom: var(--page-section-gap);
  padding: var(--spacing-xl);
  background: var(--surface-page-header-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--surface-page-header-shadow);
}
.sessions-view__section-header {
  margin-bottom: var(--spacing-lg);
}
.sessions-view__section-title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.sessions-view__section-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: var(--leading-snug);
}
.sessions-view__widgets {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: var(--widget-gap);
}
@media (min-width: 640px) {
  .sessions-view__widgets {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  }
}
</style>
