<script setup lang="ts">
import GoalList from '@/features/goals/components/GoalList.vue'
import PageView from '@/components/layout/PageView.vue'
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
