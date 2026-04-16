<script setup lang="ts">
import Breadcrumb from 'primevue/breadcrumb'

export interface BreadcrumbItem {
  label: string
  to?: string
  href?: string
}

withDefaults(
  defineProps<{
    breadcrumb?: BreadcrumbItem[]
    title?: string
    subtitle?: string
    narrow?: boolean
  }>(),
  { breadcrumb: () => [], title: '', subtitle: '', narrow: false }
)
</script>

<template>
  <div class="page-view" :class="{ 'page-view--narrow': narrow }">
    <Breadcrumb
      v-if="breadcrumb?.length"
      :model="breadcrumb.map((item) => ({ label: item.label, to: item.to }))"
      class="page-view__breadcrumb"
    />
    <header v-if="title" class="page-header page-view__header">
      <div class="page-view__header-inner">
        <h1 v-if="title" class="page-title">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="page-subtitle">
          {{ subtitle }}
        </p>
        <div v-if="$slots.hint" class="page-hint" role="status" aria-live="polite">
          <slot name="hint" />
        </div>
      </div>
    </header>
    <div class="page-view__body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.page-view__breadcrumb {
  margin-bottom: var(--page-breadcrumb-margin-bottom);
}
.page-view__breadcrumb :deep(.p-breadcrumb) {
  background: transparent;
  border: none;
  padding: 0;
}
.page-view__breadcrumb :deep(.p-breadcrumb-list) {
  gap: var(--spacing-xs);
  flex-wrap: wrap;
}
.page-view__breadcrumb :deep(.p-breadcrumb-item-link) {
  font-size: var(--text-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  text-decoration: none;
  transition: color var(--duration-fast) ease;
  border-radius: var(--radius-sm);
}
.page-view__breadcrumb :deep(.p-breadcrumb-item:not(:last-child) .p-breadcrumb-item-link:hover) {
  color: var(--color-primary);
}
.page-view__breadcrumb :deep(.p-breadcrumb-item:last-child .p-breadcrumb-item-link) {
  color: var(--color-text);
  font-weight: 600;
  pointer-events: none;
  cursor: default;
}
.page-view__breadcrumb :deep(.p-breadcrumb-item:last-child .p-breadcrumb-item-link:hover) {
  color: var(--color-text);
}
.page-view__breadcrumb :deep(.p-breadcrumb-separator-icon) {
  color: var(--color-text-muted);
  opacity: 0.55;
}
.page-view__header {
  margin-bottom: var(--page-header-margin-bottom);
  padding: var(--spacing-md) var(--spacing-xl);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--surface-page-header-bg);
  box-shadow: var(--surface-page-header-shadow);
}
.page-view__header .page-title {
  font-family: var(--font-display);
}
.page-view__header-inner {
  display: flex;
  flex-direction: column;
  gap: var(--page-header-gap);
}
.page-hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-normal);
  margin: 0;
  padding: var(--spacing-xs) 0 0;
  border-top: 1px solid var(--color-border);
  margin-top: var(--spacing-xs);
  padding-top: var(--spacing-sm);
}
.page-view__body {
  display: flex;
  flex-direction: column;
  gap: var(--page-section-gap);
  padding-block: var(--page-content-padding-block);
}
@media (max-width: 640px) {
  .page-view__header {
    padding: var(--spacing-sm);
  }
}
</style>
