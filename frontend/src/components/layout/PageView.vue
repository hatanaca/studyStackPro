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
  <div
    class="page-view"
    :class="{ 'page-view--narrow': narrow }"
  >
    <Breadcrumb
      v-if="breadcrumb?.length"
      :model="breadcrumb.map((item) => ({ label: item.label, to: item.to }))"
      class="page-view__breadcrumb"
    />
    <header
      v-if="title"
      class="page-header page-view__header"
    >
      <div class="page-view__header-inner">
        <h1
          v-if="title"
          class="page-title"
        >
          {{ title }}
        </h1>
        <p
          v-if="subtitle"
          class="page-subtitle"
        >
          {{ subtitle }}
        </p>
        <div
          v-if="$slots.hint"
          class="page-hint"
          role="status"
          aria-live="polite"
        >
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
.page-view__header {
  margin-bottom: var(--page-header-margin-bottom);
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: color-mix(in srgb, var(--color-bg-card) 85%, var(--color-bg-soft));
}
.page-view__header-inner {
  display: flex;
  flex-direction: column;
  gap: var(--page-header-gap);
}
.page-hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: 1.45;
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
@media (max-width: var(--screen-sm)) {
  .page-view__header {
    padding: var(--spacing-sm);
  }
}
</style>
