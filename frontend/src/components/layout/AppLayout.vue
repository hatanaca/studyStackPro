<script setup lang="ts">
import { computed, onMounted, onUnmounted, provide, ref, watch } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useUiStore } from '@/stores/ui.store'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppTopBar from '@/components/layout/AppTopBar.vue'
import ActiveSessionBanner from '@/features/sessions/components/ActiveSessionBanner.vue'

const authStore = useAuthStore()
const uiStore = useUiStore()

const useStakentStyle = computed(() => uiStore.theme === 'dark')

provide('stakentStyle', useStakentStyle)

const wsModule = ref<{ connect: (id: string) => Promise<void>; disconnect: () => void } | null>(null)

onMounted(async () => {
  const { useWebSocket } = await import('@/composables/useWebSocket')
  wsModule.value = useWebSocket()
  if (authStore.user?.id) wsModule.value.connect(authStore.user.id)

  document.documentElement.setAttribute('data-theme', uiStore.theme)
  uiStore.applyCustomTheme()
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
    :class="{ 'stakent-style': useStakentStyle }"
  >
    <AppSidebar class="app-layout__sidebar" />
    <div class="app-layout__main-wrap">
      <AppTopBar
        v-if="useStakentStyle"
        class="app-layout__topbar"
      />
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
        <ActiveSessionBanner />
        <div class="app-layout__content">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
}
.app-layout__main-wrap {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}
.app-layout__topbar {
  flex-shrink: 0;
}
.app-layout__main {
  flex: 1;
  min-width: 0;
  padding: var(--spacing-md) var(--spacing-lg);
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
.app-layout.stakent-style .app-layout__main::before {
  background: var(--gradient-mesh, radial-gradient(at 40% 20%, #1a1f2e 0%, transparent 50%));
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
.app-layout.stakent-style .app-layout__content {
  max-width: none;
  padding: var(--spacing-lg);
}
.app-layout__mobile-header {
  display: none;
}

@media (min-width: 769px) {
  .app-layout__main-wrap {
    margin-left: var(--sidebar-width);
  }
  .app-layout__main {
    padding: var(--spacing-md) var(--spacing-lg);
  }
}

@media (max-width: 768px) {
  .app-layout__main {
    padding: var(--spacing-sm) var(--spacing-md);
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
    width: 2.25rem;
    height: 2.25rem;
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
  .app-layout__mobile-title {
    font-weight: 600;
    font-size: 1rem;
    color: var(--color-text);
  }
}
</style>
