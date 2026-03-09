<script setup lang="ts">
import SessionList from '@/features/sessions/components/SessionList.vue'
import TechnologyStudyWidget from '@/features/sessions/components/TechnologyStudyWidget.vue'
import PageView from '@/components/layout/PageView.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useTechnologiesQuery } from '@/features/technologies/composables/useTechnologiesQuery'

useTechnologiesQuery()
const technologiesStore = useTechnologiesStore()
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
    <section
      v-if="technologiesStore.technologies.length"
      class="sessions-view__by-tech"
    >
      <header class="sessions-view__section-header">
        <h2 class="sessions-view__section-title">Por tecnologia</h2>
        <p class="sessions-view__section-desc">
          Acesso rápido ao tempo estudado por tecnologia e filtros na lista.
        </p>
      </header>
      <div class="sessions-view__widgets">
        <TechnologyStudyWidget
          v-for="tech in technologiesStore.technologies"
          :key="tech.id"
          :technology="tech"
        />
      </div>
    </section>
    <SessionList />
  </PageView>
</template>

<style scoped>
.sessions-view__by-tech {
  margin-bottom: var(--page-section-gap);
}
.sessions-view__section-header { margin-bottom: var(--spacing-md); }
.sessions-view__section-title {
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
}
.sessions-view__section-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.45;
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
