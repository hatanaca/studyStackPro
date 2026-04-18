<script setup lang="ts">
import Card from 'primevue/card'
import AppearanceSection from '@/views/settings/AppearanceSection.vue'
import DataSection from '@/views/settings/DataSection.vue'
</script>

<template>
  <Card class="settings-subview__card">
    <template #content>
      <AppearanceSection />
      <DataSection class="settings-appearance__data" />
    </template>
  </Card>
</template>

<style scoped>
.settings-subview__card {
  margin-top: 0;
}
.settings-subview__card :deep(.p-card-body) {
  padding: var(--spacing-lg) var(--spacing-xl);
}
.settings-appearance__data {
  margin-top: var(--spacing-xl);
  padding-top: var(--spacing-xl);
  border-top: 1px solid var(--color-border);
}
</style>
