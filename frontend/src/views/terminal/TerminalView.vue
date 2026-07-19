<script setup lang="ts">
import PageView from '@/components/layout/PageView.vue'
import CodeTerminal from '@/features/code-terminal/components/CodeTerminal.vue'
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Terminal' }]"
    title="Code Terminal"
    subtitle="Pratique código em um ambiente isolado e seguro."
    narrow
  >
    <CodeTerminal />
  </PageView>
</template>
