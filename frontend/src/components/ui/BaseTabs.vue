<script setup lang="ts">
import { ref, computed, provide, watch, nextTick } from 'vue'

export interface TabItem {
  id: string
  label: string
  disabled?: boolean
  /** Ícone opcional (slot name ou componente) */
  icon?: string
}

const props = withDefaults(
  defineProps<{
    tabs: TabItem[]
    /** ID da aba ativa (controlado) */
    modelValue?: string
    /** Alinhamento dos labels: start, center, end */
    align?: 'start' | 'center' | 'end'
    /** Variante visual: line, pill, enclosed */
    variant?: 'line' | 'pill' | 'enclosed'
  }>(),
  { modelValue: '', align: 'start', variant: 'line' }
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const activeId = ref(props.modelValue || (props.tabs[0]?.id ?? ''))
watch(() => props.modelValue, (v) => { if (v !== undefined && v !== '') activeId.value = v }, { immediate: true })
const activeTab = computed(() => props.tabs.find(t => t.id === activeId.value))
const activeIdValue = computed(() => activeId.value)

function select(id: string) {
  const tab = props.tabs.find(t => t.id === id)
  if (tab?.disabled) return
  activeId.value = id
  emit('update:modelValue', id)
}

function getEnabledTabs() {
  return props.tabs.filter(t => !t.disabled)
}

function onTabKeydown(e: KeyboardEvent, tabId: string) {
  const enabled = getEnabledTabs()
  const currentIdx = enabled.findIndex(t => t.id === tabId)
  if (currentIdx < 0) return

  let targetIdx = -1
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
    e.preventDefault()
    targetIdx = (currentIdx + 1) % enabled.length
  } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
    e.preventDefault()
    targetIdx = (currentIdx - 1 + enabled.length) % enabled.length
  } else if (e.key === 'Home') {
    e.preventDefault()
    targetIdx = 0
  } else if (e.key === 'End') {
    e.preventDefault()
    targetIdx = enabled.length - 1
  }

  if (targetIdx >= 0) {
    select(enabled[targetIdx].id)
    nextTick(() => {
      const tabEl = document.getElementById(`tab-${enabled[targetIdx].id}`)
      tabEl?.focus()
    })
  }
}

provide('tabs', {
  activeId,
  select,
  variant: computed(() => props.variant),
  align: computed(() => props.align),
})
</script>

<template>
  <div class="base-tabs">
    <div
      class="base-tabs__list"
      :class="[`base-tabs--${variant}`, `base-tabs--align-${align}`]"
      role="tablist"
    >
      <button
        v-for="tab in tabs"
        :id="`tab-${tab.id}`"
        :key="tab.id"
        type="button"
        class="base-tabs__tab"
        :class="{ 'base-tabs__tab--active': activeId === tab.id, 'base-tabs__tab--disabled': tab.disabled }"
        role="tab"
        :aria-selected="activeId === tab.id"
        :aria-controls="`tabpanel-${tab.id}`"
        :aria-disabled="tab.disabled"
        :tabindex="tab.disabled ? -1 : (activeId === tab.id ? 0 : -1)"
        @click="select(tab.id)"
        @keydown="onTabKeydown($event, tab.id)"
      >
        <slot
          v-if="$slots[`icon-${tab.id}`]"
          :name="`icon-${tab.id}`"
        />
        <span class="base-tabs__label">{{ tab.label }}</span>
      </button>
    </div>
    <div
      :id="`tabpanel-${activeIdValue}`"
      class="base-tabs__panels"
      role="tabpanel"
      :aria-labelledby="`tab-${activeIdValue}`"
    >
      <slot
        :active-id="activeIdValue"
        :active-tab="activeTab"
      />
    </div>
  </div>
</template>

<style scoped>
.base-tabs {
  width: 100%;
}
.base-tabs__list {
  display: flex;
  gap: var(--spacing-2xs);
  margin-bottom: 0;
  border-bottom: 1px solid var(--color-border);
}
.base-tabs--align-start { justify-content: flex-start; }
.base-tabs--align-center { justify-content: center; }
.base-tabs--align-end { justify-content: flex-end; }

.base-tabs__tab {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-lg);
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text-muted);
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  cursor: pointer;
  transition: color var(--duration-fast) ease, border-color var(--duration-fast) ease, background var(--duration-fast) ease;
}
.base-tabs__tab:hover:not(.base-tabs__tab--disabled) {
  color: var(--color-text);
}
.base-tabs__tab--active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}
.base-tabs__tab--disabled {
  opacity: var(--state-disabled-opacity);
  cursor: not-allowed;
}
.base-tabs--pill .base-tabs__list {
  border-bottom: none;
  gap: var(--spacing-2xs);
}
.base-tabs--pill .base-tabs__tab {
  border-radius: var(--radius-md);
  border-bottom: none;
  margin-bottom: 0;
}
.base-tabs--pill .base-tabs__tab--active {
  background: var(--color-primary-soft);
  color: var(--color-primary);
}
.base-tabs--enclosed .base-tabs__list {
  border-bottom: none;
  gap: 0;
}
.base-tabs--enclosed .base-tabs__tab {
  border: 1px solid var(--color-border);
  border-bottom: none;
  margin-bottom: 0;
  border-radius: var(--radius-md) var(--radius-md) 0 0;
}
.base-tabs--enclosed .base-tabs__tab + .base-tabs__tab {
  margin-left: -1px;
}
.base-tabs--enclosed .base-tabs__tab--active {
  background: var(--color-bg-card);
  color: var(--color-primary);
  border-bottom: 1px solid var(--color-bg-card);
  margin-bottom: -1px;
}
.base-tabs__panels {
  padding: var(--spacing-lg) 0;
}
</style>
