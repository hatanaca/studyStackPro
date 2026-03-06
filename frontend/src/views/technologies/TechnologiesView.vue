<script setup lang="ts">
import TechnologyList from '@/features/technologies/components/TechnologyList.vue'
import PageView from '@/components/layout/PageView.vue'
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Tecnologias' }]"
    title="Tecnologias"
    subtitle="Gerencie as tecnologias usadas para categorizar suas sessões de estudo."
  >
    <template #hint>
      Adicione uma tecnologia (ex.: JavaScript, React) para começar a registrar sessões. Cada uma pode ter cor e descrição.
    </template>
    <TechnologyList />
  </PageView>
</template>
