<script setup lang="ts">
import { defineAsyncComponent, h } from 'vue'
import Skeleton from 'primevue/skeleton'
import PageView from '@/components/layout/PageView.vue'

const GoalList = defineAsyncComponent({
  loader: () => import('@/features/goals/components/GoalList.vue'),
  loadingComponent: {
    name: 'GoalListLoading',
    setup() {
      return () =>
        h(
          'div',
          {
            class: 'goals-view__async-placeholder',
            role: 'status',
            'aria-live': 'polite',
            'aria-label': 'Carregando metas',
          },
          [
            h(Skeleton, { width: '38%', height: '1rem', class: 'goals-view__async-placeholder__line' }),
            h(Skeleton, { width: '100%', height: '10rem' }),
            h(Skeleton, { width: '100%', height: '6rem', class: 'goals-view__async-placeholder__line' }),
          ],
        )
    },
  },
  delay: 100,
})
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Metas' }]"
    title="Metas"
    subtitle="Defina e acompanhe suas metas de estudo por semana ou sequência de dias."
    narrow
  >
    <template #hint>
      Metas de minutos por semana aparecem no dashboard. Crie ou edite uma meta ativa para personalizar seu objetivo.
    </template>
    <GoalList />
  </PageView>
</template>

<style>
.goals-view__async-placeholder {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  padding: var(--spacing-sm) 0;
}
.goals-view__async-placeholder__line {
  display: block;
}
</style>
