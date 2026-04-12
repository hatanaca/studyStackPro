<script setup lang="ts">
import TechnologyList from '@/features/technologies/components/TechnologyList.vue'
import PageView from '@/components/layout/PageView.vue'
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Tecnologias' }]"
    title="Tecnologias"
    subtitle="Suas sessões de estudo ficam organizadas por tecnologia. Clique em uma para ver e registrar sessões."
  >
    <template #hint>
      Adicione uma tecnologia (ex.: JavaScript, React), acesse seus detalhes e registre sessões diretamente de lá.
    </template>
    <TechnologyList />
  </PageView>
</template>
