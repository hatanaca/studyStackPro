<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import PageView from '@/components/layout/PageView.vue'

const route = useRoute()

const tabs = [
  { name: 'settings-appearance' as const, label: 'Aparência', to: '/settings/appearance' },
  { name: 'settings-export' as const, label: 'Exportar dados', to: '/settings/export' },
  { name: 'settings-reports' as const, label: 'Relatórios', to: '/settings/reports' },
  { name: 'settings-help' as const, label: 'Ajuda', to: '/settings/help' },
]

const currentTabLabel = computed(() => tabs.find((t) => t.name === route.name)?.label ?? '')

const breadcrumbModel = computed(() => {
  const items: { label: string; to?: string }[] = [
    { label: 'Dashboard', to: '/' },
    { label: 'Configurações', to: '/settings/appearance' },
  ]
  if (currentTabLabel.value) {
    items.push({ label: currentTabLabel.value })
  }
  return items.map((item) => ({ label: item.label, to: item.to }))
})
</script>

<template>
  <PageView
    :breadcrumb="breadcrumbModel"
    title="Configurações"
    subtitle="Aparência, exportação, relatórios e ajuda num só sítio."
    narrow
  >
    <template #default>
      <nav class="settings-layout__tabs" aria-label="Secções de configurações">
        <RouterLink
          v-for="t in tabs"
          :key="t.name"
          :to="t.to"
          class="settings-layout__tab"
          :class="{ 'settings-layout__tab--active': route.name === t.name }"
        >
          {{ t.label }}
        </RouterLink>
      </nav>
      <div class="settings-layout__body">
        <RouterView />
      </div>
    </template>
  </PageView>
</template>

<style scoped>
.settings-layout__breadcrumb {
  margin-bottom: var(--spacing-md);
}
.settings-layout__breadcrumb :deep(.p-breadcrumb) {
  background: transparent;
  border: none;
  padding: 0;
}
.settings-layout__breadcrumb :deep(.p-breadcrumb-list) {
  gap: var(--spacing-xs);
  flex-wrap: wrap;
}
.settings-layout__breadcrumb :deep(.p-breadcrumb-item-link) {
  font-size: var(--text-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  text-decoration: none;
  border-radius: var(--radius-sm);
}
.settings-layout__breadcrumb
  :deep(.p-breadcrumb-item:not(:last-child) .p-breadcrumb-item-link:hover) {
  color: var(--color-primary);
}
.settings-layout__breadcrumb :deep(.p-breadcrumb-item:last-child .p-breadcrumb-item-link) {
  color: var(--color-text);
  font-weight: 600;
  pointer-events: none;
  cursor: default;
}
.settings-layout__intro {
  margin-bottom: var(--spacing-md);
}
.settings-layout__title {
  margin: 0 0 var(--spacing-xs);
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.settings-layout__subtitle {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
  max-width: 48ch;
}
.settings-layout__tabs {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-2xs);
  padding: var(--spacing-2xs);
  margin-bottom: var(--spacing-lg);
  background: rgba(255, 255, 255, 0.03);
  border: var(--card-chrome-border);
  border-radius: var(--radius-lg);
}
[data-theme='light'] .settings-layout__tabs {
  background: rgba(0, 0, 0, 0.02);
}
.settings-layout__tab {
  padding: var(--spacing-xs) var(--spacing-md);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text-muted);
  text-decoration: none;
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease;
}
.settings-layout__tab:hover {
  color: var(--color-text);
  background: rgba(255, 255, 255, 0.04);
}
[data-theme='light'] .settings-layout__tab:hover {
  background: rgba(0, 0, 0, 0.03);
}
.settings-layout__tab--active {
  color: var(--color-primary-contrast);
  background: var(--color-primary);
  box-shadow: var(--shadow-sm);
}
.settings-layout__tab--active:hover {
  color: var(--color-primary-contrast);
  background: var(--color-primary);
}
.settings-layout__body {
  min-height: 12rem;
}

@media (max-width: 640px) {
  .settings-layout__tabs {
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    flex-wrap: nowrap;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .settings-layout__tabs::-webkit-scrollbar {
    display: none;
  }
  .settings-layout__tab {
    scroll-snap-align: start;
    white-space: nowrap;
    font-size: var(--text-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
  }
  .settings-layout__title {
    font-size: var(--text-lg);
  }
}
</style>
