<script setup lang="ts">
import BaseTabs from '@/components/ui/BaseTabs.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import PageView from '@/components/layout/PageView.vue'
import AppearanceSection from '@/views/settings/AppearanceSection.vue'
import DataSection from '@/views/settings/DataSection.vue'
import { ref } from 'vue'

const tabs = [
  { id: 'appearance', label: 'Aparência', disabled: false },
  { id: 'data', label: 'Dados', disabled: false },
]
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
    <BaseTabs
      v-model="activeTab"
      :tabs="tabs"
      variant="pill"
      align="start"
    >
      <template #default="{ activeId }">
        <BaseCard class="settings-view__card">
          <AppearanceSection v-if="activeId === 'appearance'" />
          <DataSection v-else-if="activeId === 'data'" />
        </BaseCard>
      </template>
    </BaseTabs>
  </PageView>
</template>

<style scoped>
.settings-view__card {
  margin-top: 0;
  border-radius: var(--radius-md);
  overflow: hidden;
}
</style>
