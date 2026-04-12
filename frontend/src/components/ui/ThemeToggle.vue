<script setup lang="ts">
import { computed } from 'vue'
import { useUiStore } from '@/stores/ui.store'

withDefaults(
  defineProps<{
    /** Quando true, usa cores para fundo escuro (ex.: sidebar) */
    variant?: 'default' | 'sidebar'
  }>(),
  { variant: 'default' }
)

const uiStore = useUiStore()
const ariaLabel = computed(() =>
  uiStore.isDarkMode ? 'Usar tema claro' : 'Usar tema escuro'
)

function toggle() {
  uiStore.toggleTheme()
}
</script>

<template>
  <button
    type="button"
    class="theme-toggle"
    :class="{ 'theme-toggle--sidebar': variant === 'sidebar' }"
    :aria-label="ariaLabel"
    title="Alternar tema"
    @click="toggle"
  >
    <svg
      v-if="uiStore.isDarkMode"
      class="theme-toggle__icon"
      xmlns="http://www.w3.org/2000/svg"
      width="20"
      height="20"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <circle
        cx="12"
        cy="12"
        r="4"
      />
      <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
    </svg>
    <svg
      v-else
      class="theme-toggle__icon"
      xmlns="http://www.w3.org/2000/svg"
      width="20"
      height="20"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
    </svg>
  </button>
</template>

<style scoped>
.theme-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--input-height-md);
  height: var(--input-height-md);
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-card);
  color: var(--color-text-muted);
  cursor: pointer;
  transition: color var(--duration-fast) ease, background var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.theme-toggle:hover {
  color: var(--color-text);
  background: var(--color-bg-soft);
}
.theme-toggle:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.theme-toggle__icon {
  flex-shrink: 0;
}

/* Variante para uso dentro da sidebar (fundo escuro) */
.theme-toggle--sidebar {
  border-color: color-mix(in srgb, var(--color-border) 70%, transparent);
  background: var(--color-bg-soft);
  color: var(--color-text-muted);
}
.theme-toggle--sidebar:hover {
  background: var(--color-bg);
  color: var(--color-text);
}
</style>
