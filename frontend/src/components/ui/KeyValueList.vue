<script setup lang="ts">
import { computed } from 'vue'

export interface KeyValueItem {
  label: string
  value: string | number | null | undefined
  /** Ocultar linha quando value estiver vazio */
  hideWhenEmpty?: boolean
}

const props = withDefaults(
  defineProps<{
    /** Lista de itens label/valor (renderiza como dl/dt/dd) */
    items?: KeyValueItem[]
    /** Layout: row (label e valor na mesma linha) ou stack (valor abaixo do label em mobile) */
    layout?: 'row' | 'stack'
  }>(),
  { items: () => [], layout: 'row' }
)

const visibleItems = computed(() =>
  (props.items ?? []).filter(
    (item) => item.hideWhenEmpty !== true || item.value != null
  )
)

function getDisplayValue(value: KeyValueItem['value']): string {
  if (value == null || value === '') return '—'
  return String(value)
}
</script>

<template>
  <dl
    class="key-value-list"
    :class="`key-value-list--${layout}`"
  >
    <template v-if="items?.length">
      <div
        v-for="(item, index) in visibleItems"
        :key="index"
        class="key-value-list__row"
      >
        <dt class="key-value-list__term">
          {{ item.label }}
        </dt>
        <dd class="key-value-list__value">
          <slot
            name="value"
            :item="item"
            :value="item.value"
          >
            {{ getDisplayValue(item.value) }}
          </slot>
        </dd>
      </div>
    </template>
    <template v-else>
      <slot name="default" />
    </template>
  </dl>
</template>

<style scoped>
.key-value-list {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.key-value-list__row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
  align-items: baseline;
  gap: var(--spacing-md);
  padding: var(--spacing-sm) 0;
  border-bottom: 1px solid var(--color-border);
}
.key-value-list__row:last-child {
  border-bottom: none;
}
.key-value-list__term {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  margin: 0;
}
.key-value-list__value {
  font-size: var(--text-sm);
  color: var(--color-text);
  margin: 0;
  word-break: break-word;
}
.key-value-list--stack {
  max-width: 24rem;
}
.key-value-list--stack .key-value-list__row {
  grid-template-columns: 1fr;
  gap: var(--spacing-2xs);
}
@media (min-width: 480px) {
  .key-value-list--stack .key-value-list__row {
    grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
  }
}
</style>
