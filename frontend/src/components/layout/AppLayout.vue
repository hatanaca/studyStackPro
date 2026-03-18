<script setup lang="ts">
/**
 * Layout principal autenticado.
 * Sidebar + área de conteúdo + ActiveSessionBanner. Conecta WebSocket no mount.
 * Tema (data-theme) aplicado via uiStore. Layout estrutural independente do tema.
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useUiStore } from '@/stores/ui.store'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import ActiveSessionBanner from '@/features/sessions/components/ActiveSessionBanner.vue'

const authStore = useAuthStore()
const uiStore = useUiStore()
const route = useRoute()

const mainWrapRef = ref<HTMLElement | null>(null)

const showActiveBanner = computed(() => route.name !== 'session-focus')

// Reset scroll ao trocar de rota (scroll isolado no main-wrap, não no window)
watch(
  () => route.path,
  () => {
    mainWrapRef.value?.scrollTo({ top: 0, behavior: 'instant' })
  }
)

const wsModule = ref<{ connect: (id: string) => Promise<void>; disconnect: () => void } | null>(null)

onMounted(async () => {
  document.documentElement.setAttribute('data-theme', uiStore.theme)
  uiStore.applyCustomTheme()

  try {
    const { useWebSocket } = await import('@/composables/useWebSocket')
    wsModule.value = useWebSocket()
    if (authStore.user?.id) await wsModule.value.connect(authStore.user.id)
  } catch {
    // WebSocket connection failed silently; polling fallback handles this
  }
})

onUnmounted(() => {
  wsModule.value?.disconnect()
})

watch(
  () => uiStore.theme,
  (val) => {
    document.documentElement.setAttribute('data-theme', val)
    uiStore.applyCustomTheme()
  }
)
</script>

<template>
  <div
    class="app-layout"
    :class="{ 'app-layout--sidebar-collapsed': uiStore.sidebarCollapsed }"
  >
    <AppSidebar class="app-layout__sidebar" />
    <div ref="mainWrapRef" class="app-layout__main-wrap">
      <main class="app-layout__main">
        <header class="app-layout__mobile-header">
          <button
            type="button"
            class="app-layout__nav-trigger"
            aria-label="Abrir navegação"
            @click="uiStore.openMobileSidebar()"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <line
                x1="4"
                y1="6"
                x2="20"
                y2="6"
              /><line
                x1="4"
                y1="12"
                x2="20"
                y2="12"
              /><line
                x1="4"
                y1="18"
                x2="20"
                y2="18"
              />
            </svg>
          </button>
          <span class="app-layout__mobile-title">StudyTrack Pro</span>
        </header>
        <ActiveSessionBanner v-if="showActiveBanner" />
        <div class="app-layout__content">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
/*
  Layout de "scroll isolado":
  - .app-layout ocupa exatamente 100dvh (sem overflow)
  - A sidebar fica em flow normal (sem position: fixed)
  - .app-layout__main-wrap é o único scroll container da página
  - Não é necessário margin-left nem z-index complicado
*/
.app-layout {
  display: flex;
  height: 100dvh;
  overflow: hidden;
}
.app-layout__main-wrap {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  overflow-x: hidden;
  overscroll-behavior: contain;
}
.app-layout__main {
  flex: 1;
  min-width: 0;
  padding: var(--spacing-lg) var(--spacing-xl);
  background: var(--color-bg);
  position: relative;
}
.app-layout__main::before {
  content: '';
  position: fixed;
  inset: 0;
  background: var(--gradient-mesh);
  pointer-events: none;
  z-index: 0;
}
.app-layout__main > * {
  position: relative;
  z-index: 1;
}
.app-layout__content {
  max-width: var(--page-max-width);
  margin-left: auto;
  margin-right: auto;
  width: 100%;
  padding-block: var(--page-content-padding-block);
}
.app-layout__mobile-header {
  display: none;
}

@media (min-width: 769px) {
  .app-layout__main {
    padding: var(--spacing-lg) var(--spacing-xl);
  }
}

@media (max-width: 768px) {
  .app-layout {
    /* Mobile: layout normal (sidebar como drawer, não em flow) */
    height: auto;
    min-height: 100dvh;
    overflow: visible;
  }
  .app-layout__main-wrap {
    overflow: visible;
  }
  .app-layout__main {
    padding: var(--spacing-sm) var(--spacing-lg);
  }
  .app-layout__mobile-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
    padding-bottom: var(--spacing-sm);
    border-bottom: 1px solid var(--color-border);
  }
  .app-layout__nav-trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    padding: 0;
    background: var(--color-bg-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    color: var(--color-text-muted);
    cursor: pointer;
    transition: background var(--duration-fast) ease, color var(--duration-fast) ease, border-color var(--duration-fast) ease;
  }
  .app-layout__nav-trigger:hover {
    background: var(--color-primary-soft);
    color: var(--color-primary);
    border-color: var(--color-primary);
  }
  .app-layout__nav-trigger:active {
    transform: scale(0.98);
  }
  .app-layout__nav-trigger:focus-visible {
    outline: none;
    box-shadow: var(--shadow-focus);
  }
  .app-layout__mobile-title {
    font-weight: 600;
    font-size: var(--text-base);
    color: var(--color-text);
  }
}
</style>
