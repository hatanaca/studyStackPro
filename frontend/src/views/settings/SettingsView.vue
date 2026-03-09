<script setup lang="ts">
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Card from 'primevue/card'
import PageView from '@/components/layout/PageView.vue'
import AppearanceSection from '@/views/settings/AppearanceSection.vue'
import DataSection from '@/views/settings/DataSection.vue'
import { ref } from 'vue'

const activeTab = ref('appearance')
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Configurações' }]"
    title="Configurações"
    subtitle="Aparência, dados e preferências da aplicação."
    narrow
  >
    <template #hint>
      Alterações em Aparência são salvas automaticamente. Em Dados você pode limpar cache ou exportar.
    </template>
    <Tabs v-model:value="activeTab" class="settings-view__tabs">
      <TabList>
        <Tab value="appearance">Aparência</Tab>
        <Tab value="data">Dados</Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="appearance">
          <Card class="settings-view__card">
            <template #content>
              <AppearanceSection />
            </template>
          </Card>
        </TabPanel>
        <TabPanel value="data">
          <Card class="settings-view__card">
            <template #content>
              <DataSection />
            </template>
          </Card>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </PageView>
</template>

<style scoped>
.settings-view__card {
  margin-top: 0;
  border-radius: var(--radius-md);
  overflow: hidden;
}
</style>
