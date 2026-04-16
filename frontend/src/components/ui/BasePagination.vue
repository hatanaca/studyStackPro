<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    page: number
    totalPages: number
    totalItems?: number
    pageSize?: number
    showFirstLast?: boolean
    maxVisible?: number
  }>(),
  { totalItems: undefined, pageSize: undefined, showFirstLast: true, maxVisible: 5 }
)

const emit = defineEmits<{
  'update:page': [page: number]
}>()

const hasPrevious = computed(() => props.page > 1)
const hasNext = computed(() => props.page < props.totalPages)

const visiblePages = computed(() => {
  const total = props.totalPages
  const current = props.page
  const max = props.maxVisible
  if (total <= max) return Array.from({ length: total }, (_, i) => i + 1)
  const half = Math.floor(max / 2)
  let start = Math.max(1, current - half)
  let end = Math.min(total, start + max - 1)
  if (end - start + 1 < max) start = Math.max(1, end - max + 1)
  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

function goTo(p: number) {
  const next = Math.max(1, Math.min(p, props.totalPages))
  if (next !== props.page) emit('update:page', next)
}
</script>

<template>
  <nav class="base-pagination" role="navigation" aria-label="Paginação">
    <span v-if="totalItems != null && pageSize != null" class="base-pagination__info">
      {{ (page - 1) * pageSize + 1 }}-{{ Math.min(page * pageSize, totalItems) }} de
      {{ totalItems }}
    </span>
    <ul class="base-pagination__list">
      <li v-if="showFirstLast">
        <button
          type="button"
          class="base-pagination__btn"
          :disabled="!hasPrevious"
          aria-label="Primeira página"
          @click="goTo(1)"
        >
          «
        </button>
      </li>
      <li>
        <button
          type="button"
          class="base-pagination__btn"
          :disabled="!hasPrevious"
          aria-label="Página anterior"
          @click="goTo(page - 1)"
        >
          ‹
        </button>
      </li>
      <li v-for="p in visiblePages" :key="p">
        <button
          type="button"
          class="base-pagination__btn base-pagination__btn--page"
          :class="{ 'base-pagination__btn--current': p === page }"
          :aria-current="p === page ? 'page' : undefined"
          :aria-label="`Página ${p}`"
          @click="goTo(p)"
        >
          {{ p }}
        </button>
      </li>
      <li>
        <button
          type="button"
          class="base-pagination__btn"
          :disabled="!hasNext"
          aria-label="Próxima página"
          @click="goTo(page + 1)"
        >
          ›
        </button>
      </li>
      <li v-if="showFirstLast">
        <button
          type="button"
          class="base-pagination__btn"
          :disabled="!hasNext"
          aria-label="Última página"
          @click="goTo(totalPages)"
        >
          »
        </button>
      </li>
    </ul>
  </nav>
</template>

<style scoped>
.base-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--spacing-lg);
}
.base-pagination__info {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
.base-pagination__list {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  list-style: none;
  margin: 0;
  padding: 0;
}
.base-pagination__btn {
  min-width: var(--avatar-size-md);
  min-height: var(--input-height-sm);
  height: var(--avatar-size-md);
  padding: 0 var(--spacing-sm);
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-text);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition:
    background var(--duration-fast) ease,
    border-color var(--duration-fast) ease,
    color var(--duration-fast) ease;
}
.base-pagination__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.base-pagination__btn:hover:not(:disabled) {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.base-pagination__btn:disabled {
  opacity: var(--state-disabled-opacity);
  cursor: not-allowed;
}
.base-pagination__btn--current {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-primary-contrast);
}
.base-pagination__btn--current:hover:not(:disabled) {
  background: var(--color-primary-hover);
  border-color: var(--color-primary-hover);
  color: var(--color-primary-contrast);
}
</style>
