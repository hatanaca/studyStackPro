<script setup lang="ts">
export interface BreadcrumbItem {
  label: string
  to?: string
  href?: string
}

defineProps<{
  items: BreadcrumbItem[]
}>()
</script>

<template>
  <nav class="base-breadcrumb" aria-label="Navegação">
    <ol class="base-breadcrumb__list">
      <li v-for="(item, index) in items" :key="index" class="base-breadcrumb__item">
        <template v-if="index > 0">
          <span class="base-breadcrumb__sep" aria-hidden="true">/</span>
        </template>
        <RouterLink
          v-if="item.to && index < items.length - 1"
          :to="item.to"
          class="base-breadcrumb__link"
        >
          {{ item.label }}
        </RouterLink>
        <a
          v-else-if="item.href && index < items.length - 1"
          :href="item.href"
          class="base-breadcrumb__link"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ item.label }}
        </a>
        <span v-else class="base-breadcrumb__current" aria-current="page">
          {{ item.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<style scoped>
.base-breadcrumb__list {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: var(--text-sm);
}
.base-breadcrumb__item {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}
.base-breadcrumb__sep {
  color: var(--color-text-muted);
  margin: 0 var(--spacing-xs);
  opacity: 0.8;
}
.base-breadcrumb__link {
  color: var(--color-primary);
  text-decoration: none;
  transition: color var(--duration-fast) ease;
}
.base-breadcrumb__link:hover {
  color: var(--color-primary-hover);
  text-decoration: underline;
}
.base-breadcrumb__current {
  color: var(--color-text);
  font-weight: 600;
}
</style>
