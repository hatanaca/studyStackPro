<script setup lang="ts">
import { ref } from 'vue'

export interface AccordionItem {
  id: string
  title: string
  disabled?: boolean
}

const props = withDefaults(
  defineProps<{
    items: AccordionItem[]
    /** Permitir múltiplos abertos */
    multiple?: boolean
    /** IDs abertos por padrão */
    defaultOpen?: string[]
  }>(),
  { multiple: false, defaultOpen: () => [] }
)

const openIds = ref<Set<string>>(new Set(props.defaultOpen))

function toggle(id: string) {
  const item = props.items.find(i => i.id === id)
  if (item?.disabled) return
  if (props.multiple) {
    const next = new Set(openIds.value)
    if (next.has(id)) next.delete(id)
    else next.add(id)
    openIds.value = next
  } else {
    openIds.value = openIds.value.has(id) ? new Set() : new Set([id])
  }
}

function isOpen(id: string) {
  return openIds.value.has(id)
}
</script>

<template>
  <div class="base-accordion">
    <div
      v-for="item in items"
      :key="item.id"
      class="base-accordion__item"
      :class="{ 'base-accordion__item--open': isOpen(item.id), 'base-accordion__item--disabled': item.disabled }"
    >
      <button
        :id="`accordion-trigger-${item.id}`"
        type="button"
        class="base-accordion__trigger"
        :aria-expanded="isOpen(item.id)"
        :aria-controls="`accordion-panel-${item.id}`"
        :aria-disabled="item.disabled"
        @click="toggle(item.id)"
        @keydown.enter.space.prevent="toggle(item.id)"
      >
        <span class="base-accordion__title">{{ item.title }}</span>
        <span
          class="base-accordion__chevron"
          aria-hidden="true"
        >▼</span>
      </button>
      <Transition name="accordion">
        <div
          v-show="isOpen(item.id)"
          :id="`accordion-panel-${item.id}`"
          class="base-accordion__panel"
          role="region"
          :aria-labelledby="`accordion-trigger-${item.id}`"
        >
          <slot
            :name="item.id"
            :item="item"
          />
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.base-accordion {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.base-accordion__item {
  border-bottom: 1px solid var(--color-border);
}
.base-accordion__item:last-child {
  border-bottom: none;
}
.base-accordion__trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: var(--spacing-lg) var(--widget-padding);
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text);
  background: var(--color-bg-card);
  border: none;
  cursor: pointer;
  text-align: left;
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.base-accordion__trigger:hover:not([aria-disabled="true"]) {
  background: var(--color-bg-soft);
  color: var(--color-primary);
}
.base-accordion__item--disabled .base-accordion__trigger {
  opacity: var(--state-disabled-opacity);
  cursor: not-allowed;
}
.base-accordion__chevron {
  font-size: 0.625rem;
  color: var(--color-text-muted);
  transition: transform var(--duration-normal) var(--ease-out-expo);
  flex-shrink: 0;
}
.base-accordion__item--open .base-accordion__chevron {
  transform: rotate(180deg);
  color: var(--color-primary);
}
.base-accordion__panel {
  overflow: hidden;
  background: color-mix(in srgb, var(--color-bg-soft) 40%, var(--color-bg-card));
}
.base-accordion__panel > * {
  padding: 0 var(--widget-padding) var(--widget-padding);
}

.accordion-enter-active,
.accordion-leave-active {
  transition: opacity var(--duration-normal) var(--ease-in-out), transform var(--duration-normal) var(--ease-out-expo);
}
.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
}
.accordion-enter-to,
.accordion-leave-from {
  opacity: 1;
}
</style>
