<script setup lang="ts">
import { computed, ref } from 'vue'

export interface DataTableColumn<T = unknown> {
  key: string
  label: string
  sortable?: boolean
  align?: 'left' | 'center' | 'right'
  width?: string
  /** Formatação customizada ou slot name */
  formatter?: (row: T) => string
  slotName?: string
}

const props = withDefaults(
  defineProps<{
    columns: DataTableColumn[]
    data: unknown[]
    /** Chave única por linha (id) */
    rowKey?: string
    loading?: boolean
    sortBy?: string
    sortOrder?: 'asc' | 'desc'
    emptyMessage?: string
    striped?: boolean
    bordered?: boolean
    compact?: boolean
  }>(),
  {
    rowKey: 'id',
    loading: false,
    sortBy: '',
    sortOrder: 'asc',
    emptyMessage: 'Nenhum registro encontrado.',
    striped: false,
    bordered: false,
    compact: false,
  }
)

const emit = defineEmits<{
  'update:sortBy': [value: string]
  'update:sortOrder': [value: 'asc' | 'desc']
  'row-click': [row: unknown]
}>()

const internalSortBy = ref(props.sortBy)
const internalSortOrder = ref<'asc' | 'desc'>(props.sortOrder)

const sortedData = computed(() => {
  const list = [...props.data]
  if (!internalSortBy.value) return list
  const col = props.columns.find(c => c.key === internalSortBy.value)
  if (!col?.sortable) return list
  return list.sort((a, b) => {
    const aVal = (a as Record<string, unknown>)[internalSortBy.value]
    const bVal = (b as Record<string, unknown>)[internalSortBy.value]
    if (aVal == null && bVal == null) return 0
    if (aVal == null) return internalSortOrder.value === 'asc' ? 1 : -1
    if (bVal == null) return internalSortOrder.value === 'asc' ? -1 : 1
    const cmp = String(aVal).localeCompare(String(bVal), undefined, { numeric: true })
    return internalSortOrder.value === 'asc' ? cmp : -cmp
  })
})

function toggleSort(key: string) {
  const col = props.columns.find(c => c.key === key)
  if (!col?.sortable) return
  if (internalSortBy.value === key) {
    internalSortOrder.value = internalSortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    internalSortBy.value = key
    internalSortOrder.value = 'asc'
  }
  emit('update:sortBy', internalSortBy.value)
  emit('update:sortOrder', internalSortOrder.value)
}

function getCellValue(row: unknown, col: DataTableColumn): string {
  const val = (row as Record<string, unknown>)[col.key]
  if (col.formatter) return col.formatter(row as never)
  if (val == null) return '—'
  return String(val)
}

function onRowClick(row: unknown) {
  emit('row-click', row)
}
</script>

<template>
  <div class="base-data-table">
    <div
      v-if="loading"
      class="base-data-table__loading"
    >
      <span class="base-data-table__spinner" />
      Carregando...
    </div>
    <div
      v-else
      class="base-data-table__scroll"
    >
      <table
        class="base-data-table__table"
        :class="{
          'base-data-table__table--striped': striped,
          'base-data-table__table--bordered': bordered,
          'base-data-table__table--compact': compact,
        }"
      >
        <thead class="base-data-table__thead">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="base-data-table__th"
              :class="[
                `base-data-table__th--${col.align || 'left'}`,
                { 'base-data-table__th--sortable': col.sortable },
                { 'base-data-table__th--sorted': sortBy === col.key },
              ]"
              :style="col.width ? { width: col.width } : undefined"
              @click="col.sortable ? toggleSort(col.key) : undefined"
            >
              <span class="base-data-table__th-content">
                {{ col.label }}
                <span
                  v-if="col.sortable && sortBy === col.key"
                  class="base-data-table__sort-icon"
                  aria-hidden="true"
                >
                  {{ sortOrder === 'asc' ? '↑' : '↓' }}
                </span>
              </span>
            </th>
          </tr>
        </thead>
        <tbody class="base-data-table__tbody">
          <tr
            v-for="(row, index) in sortedData"
            :key="String((row as Record<string, unknown>)[rowKey] ?? index)"
            class="base-data-table__tr"
            @click="onRowClick(row)"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="base-data-table__td"
              :class="`base-data-table__td--${col.align || 'left'}`"
            >
              <slot
                v-if="col.slotName && $slots[col.slotName]"
                :name="col.slotName"
                :row="row"
                :value="(row as Record<string, unknown>)[col.key]"
              />
              <span v-else>{{ getCellValue(row, col) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
      <div
        v-if="!loading && sortedData.length === 0"
        class="base-data-table__empty"
      >
        {{ emptyMessage }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.base-data-table {
  position: relative;
  width: 100%;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: visible;
}
.base-data-table__loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-xl);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}
.base-data-table__spinner {
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.base-data-table__scroll {
  border-radius: inherit;
  overflow-x: auto;
  overflow-y: visible;
  -webkit-overflow-scrolling: touch;
}
.base-data-table__table {
  width: 100%;
  min-width: 100%;
  border-collapse: collapse;
  font-size: var(--text-sm);
}
.base-data-table__table--compact .base-data-table__th,
.base-data-table__table--compact .base-data-table__td {
  padding: var(--spacing-sm) var(--spacing-md);
}
.base-data-table__thead {
  background: var(--color-bg-soft);
  border-bottom: 1px solid var(--color-border);
}
.base-data-table__th {
  padding: var(--spacing-md) var(--widget-padding);
  text-align: left;
  font-weight: 600;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  white-space: nowrap;
}
.base-data-table__th--center { text-align: center; }
.base-data-table__th--right { text-align: right; }
.base-data-table__th--sortable {
  cursor: pointer;
  user-select: none;
  transition: color var(--duration-fast) ease;
}
.base-data-table__th--sortable:hover {
  color: var(--color-primary);
}
.base-data-table__th-content {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs);
}
.base-data-table__sort-icon {
  font-size: var(--text-xs);
  color: var(--color-primary);
}
.base-data-table__tbody .base-data-table__tr {
  border-bottom: 1px solid var(--color-border);
  transition: background var(--duration-fast) ease;
}
.base-data-table__tbody .base-data-table__tr:hover {
  background: var(--color-bg-soft);
}
.base-data-table__tbody .base-data-table__tr:last-child {
  border-bottom: none;
}
.base-data-table__table--striped .base-data-table__tbody .base-data-table__tr:nth-child(even) {
  background: var(--color-bg-soft);
}
.base-data-table__table--striped .base-data-table__tbody .base-data-table__tr:nth-child(even):hover {
  background: color-mix(in srgb, var(--color-border) 50%, var(--color-bg-soft));
}
.base-data-table__td {
  padding: var(--spacing-md) var(--widget-padding);
  color: var(--color-text);
  vertical-align: middle;
}
.base-data-table__td--center { text-align: center; }
.base-data-table__td--right { text-align: right; }
.base-data-table__empty {
  padding: var(--spacing-xl);
  text-align: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  line-height: 1.5;
}
</style>
