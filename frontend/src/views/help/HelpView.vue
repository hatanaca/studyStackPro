<script setup lang="ts">
import Card from 'primevue/card'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import PageView from '@/components/layout/PageView.vue'

const faqItems = [
  { id: 'register', title: 'Como registrar uma sessão de estudo?', description: 'No dashboard, use o card "Registrar estudo". Escolha a tecnologia, data e duração. Você também pode iniciar um cronômetro para sessões em tempo real.' },
  { id: 'goals', title: 'O que são as metas?', description: 'Metas permitem definir objetivos como "X minutos por semana" ou "Y sessões por semana". Acompanhe o progresso no widget de metas e na página Metas.' },
  { id: 'export', title: 'Como exportar meus dados?', description: 'Acesse Exportar no menu. Selecione o período e o formato (CSV ou JSON). O arquivo será gerado para download.' },
  { id: 'theme', title: 'Como mudar o tema (claro/escuro)?', description: 'Use o ícone de sol/lua no header ou na sidebar para alternar entre tema claro e escuro. A preferência é salva no navegador.' },
]
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Ajuda' }]"
    title="Ajuda"
    subtitle="Dúvidas frequentes e dicas para usar o StudyTrack Pro."
    narrow
  >
    <template #hint>
      Expanda os itens abaixo para ver as respostas. Em caso de problema, use o bloco Contato ao final.
    </template>
    <Card class="help-view__card">
      <template #title>Perguntas frequentes</template>
      <template #content>
        <Accordion :multiple="true">
          <AccordionPanel
            v-for="item in faqItems"
            :key="item.id"
            :value="item.id"
          >
            <AccordionHeader>{{ item.title }}</AccordionHeader>
            <AccordionContent>
              <p class="help-view__answer">{{ item.description }}</p>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
      </template>
    </Card>
    <Card class="help-view__card">
      <template #title>Contato</template>
      <template #content>
        <p class="help-view__text">
          Encontrou um bug ou tem uma sugestão? Abra uma issue no repositório do projeto ou entre em contato com o time de desenvolvimento.
        </p>
      </template>
    </Card>
  </PageView>
</template>

<style scoped>
.help-view__card {
  margin-top: 0;
  border-radius: var(--radius-md);
  overflow: hidden;
}
.help-view__card + .help-view__card {
  margin-top: var(--page-section-gap);
}
.help-view__answer,
.help-view__text {
  font-size: var(--text-sm);
  color: var(--color-text);
  line-height: 1.6;
  margin: 0;
  padding: 0 var(--spacing-xs);
}
.help-view__text {
  color: var(--color-text-muted);
  padding: 0;
}
.help-view__text--lead {
  color: var(--color-text);
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
}
.help-view__card--contact :deep(.base-card__body) {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
</style>
