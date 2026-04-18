<script setup lang="ts">
import { defineAsyncComponent, h, ref } from 'vue'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import SessionList from '@/features/sessions/components/SessionList.vue'
import PageView from '@/components/layout/PageView.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useTechnologiesQuery } from '@/features/technologies/composables/useTechnologiesQuery'

/** Placeholder compacto (altura ~inline) enquanto o chunk carrega; mantém hierarquia nome / tempo / sessões. */
const TechnologyStudyWidgetSkeleton = {
  name: 'TechnologyStudyWidgetSkeleton',
  setup() {
    return () =>
      h('div', { class: 'sessions-view__tw-skel' }, [
        h(Skeleton, {
          width: '72%',
          height: '0.8125rem',
          borderRadius: 'var(--radius-sm)',
        }),
        h('div', { class: 'sessions-view__tw-skel-metrics' }, [
          h('div', { class: 'sessions-view__tw-skel-line' }, [
            h('span', { class: 'sessions-view__tw-skel-label' }, 'Tempo de estudo'),
            h(Skeleton, {
              width: '2.5rem',
              height: '0.75rem',
              borderRadius: 'var(--radius-sm)',
            }),
          ]),
          h('div', { class: 'sessions-view__tw-skel-line' }, [
            h('span', { class: 'sessions-view__tw-skel-label' }, 'Sessões'),
            h(Skeleton, {
              width: '3.25rem',
              height: '0.75rem',
              borderRadius: 'var(--radius-sm)',
            }),
          ]),
        ]),
      ])
  },
}

const TechnologyStudyWidget = defineAsyncComponent({
  loader: () => import('@/features/sessions/components/TechnologyStudyWidget.vue'),
  loadingComponent: TechnologyStudyWidgetSkeleton,
  delay: 80,
})

useTechnologiesQuery()
const technologiesStore = useTechnologiesStore()

function staggerMs(index: number): number {
  return Math.min(index * 45, 900)
}

const sessionListRef = ref<{ openAddModal: () => void; applyTechnologyFilter: (id?: string) => void } | null>(
  null,
)
const techListExpanded = ref(false)

function onViewSessionList(technologyId: string) {
  sessionListRef.value?.applyTechnologyFilter(technologyId)
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
    <template #actions>
      <Button label="Nova sessão" size="small" @click="sessionListRef?.openAddModal()" />
    </template>
    <section v-if="technologiesStore.technologies.length" class="sessions-view__by-tech">
      <header class="sessions-view__section-header">
        <div class="sessions-view__section-text">
          <h2 class="sessions-view__section-title">Por tecnologia</h2>
          <p class="sessions-view__section-desc">
            Acesso rápido ao tempo estudado por tecnologia e filtros na lista.
          </p>
        </div>
        <Button
          :title="techListExpanded ? 'Recolher lista' : 'Expandir lista'"
          :icon="techListExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
          text
          rounded
          severity="secondary"
          :aria-expanded="techListExpanded"
          aria-controls="sessions-by-tech-widgets"
          :aria-label="techListExpanded ? 'Recolher lista por tecnologia' : 'Expandir lista por tecnologia'"
          @click="techListExpanded = !techListExpanded"
        />
      </header>
      <div v-if="techListExpanded" id="sessions-by-tech-widgets" class="sessions-view__widgets">
        <TechnologyStudyWidget
          v-for="(tech, idx) in technologiesStore.technologies"
          :key="tech.id"
          variant="list"
          :technology="tech"
          :stagger-ms="staggerMs(idx)"
          @view-session-list="onViewSessionList"
        />
      </div>
    </section>
    <SessionList ref="sessionListRef" />
  </PageView>
</template>

<style scoped>
.sessions-view__by-tech {
  margin-bottom: var(--spacing-md);
  padding: var(--spacing-md);
  background: var(--surface-page-header-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--surface-page-header-shadow);
}
.sessions-view__section-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-sm);
}
.sessions-view__section-text {
  min-width: 0;
  flex: 1;
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
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.sessions-view__widgets > * {
  width: 100%;
}
</style>

<!-- Estilos do loadingComponent (h()): fora do scoped para aplicar ao placeholder async. -->
<style>
.sessions-view__tw-skel {
  box-sizing: border-box;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-bg-card);
  padding: var(--spacing-sm) var(--spacing-md);
  min-height: 0;
}
.sessions-view__tw-skel-metrics {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: var(--spacing-xs);
}
.sessions-view__tw-skel-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  min-height: 1.125rem;
}
.sessions-view__tw-skel-label {
  font-size: var(--text-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  white-space: nowrap;
}
</style>
