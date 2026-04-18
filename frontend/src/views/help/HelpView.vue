<script setup lang="ts">
import Card from 'primevue/card'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'

const faqItems = [
  {
    id: 'register',
    title: 'Como registrar uma sessão de estudo?',
    description:
      'No dashboard, use o card "Registrar estudo". Escolha a tecnologia, data e duração. Você também pode iniciar um cronômetro para sessões em tempo real.',
  },
  {
    id: 'goals',
    title: 'O que são as metas?',
    description:
      'Metas permitem definir objetivos como "X minutos por semana" ou "Y sessões por semana". Acompanhe o progresso no widget de metas e na página Metas.',
  },
  {
    id: 'export',
    title: 'Como exportar meus dados?',
    description:
      'Em Configurações → Exportar dados, selecione o período e o formato (CSV ou JSON). O arquivo será gerado para download.',
  },
  {
    id: 'theme',
    title: 'Como mudar o tema (claro/escuro)?',
    description:
      'Use o ícone de sol/lua no header ou na sidebar para alternar entre tema claro e escuro. A preferência é salva no navegador.',
  },
]
</script>

<template>
  <div class="help-view">
    <p class="help-view__lead">
      Expanda os itens abaixo para ver as respostas. Em caso de problema, use o bloco Contato ao
      final.
    </p>
    <Card class="help-view__card">
      <template #title>Perguntas frequentes</template>
      <template #content>
        <Accordion :multiple="true">
          <AccordionPanel v-for="item in faqItems" :key="item.id" :value="item.id">
            <AccordionHeader>{{ item.title }}</AccordionHeader>
            <AccordionContent>
              <p class="help-view__answer allow-user-select">{{ item.description }}</p>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
      </template>
    </Card>
    <Card class="help-view__card">
      <template #title>Contato</template>
      <template #content>
        <p class="help-view__text allow-user-select">
          Encontrou um bug ou tem uma sugestão? Abra uma issue no repositório do projeto ou entre em
          contato com o time de desenvolvimento.
        </p>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.help-view {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.help-view__lead {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
  max-width: 52ch;
}
.help-view__card {
  margin-top: 0;
}
.help-view__card + .help-view__card {
  margin-top: var(--spacing-xl);
}
.help-view__card :deep(.p-card-content) {
  padding-top: var(--spacing-md);
}
.help-view__answer,
.help-view__text {
  font-size: var(--text-sm);
  color: var(--color-text);
  line-height: var(--leading-normal);
  letter-spacing: var(--tracking-tight);
  margin: 0;
  max-width: 65ch;
}
.help-view__answer {
  padding: 0 var(--spacing-xs) 0 0;
}
.help-view__text {
  color: var(--color-text-muted);
}
.help-view__text--lead {
  color: var(--color-text);
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
}
</style>
