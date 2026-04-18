<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import TechnologyList from '@/features/technologies/components/TechnologyList.vue'
import PageView from '@/components/layout/PageView.vue'

const technologyListRef = ref<{ openCreate: () => void } | null>(null)
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Tecnologias' }]"
    title="Tecnologias"
    subtitle="Suas sessões de estudo ficam organizadas por tecnologia. Clique em uma para ver e registrar sessões."
  >
    <template #hint>
      Adicione uma tecnologia (ex.: JavaScript, React), acesse seus detalhes e registre sessões
      diretamente de lá. Em cada tecnologia encontra o mapa de estudos (fluxograma local).
    </template>
    <template #actions>
      <Button label="Nova tecnologia" size="small" @click="technologyListRef?.openCreate()" />
    </template>
    <TechnologyList ref="technologyListRef" />
  </PageView>
</template>
