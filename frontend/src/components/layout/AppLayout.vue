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
import MiniPlayer from '@/components/player/MiniPlayer.vue'
import { clearMeasureCache } from '@/composables/useTextMeasure'
import { invalidateChartThemeCache } from '@/composables/useApexChartTheme'
import { connectWebSocket, disconnectWebSocket } from '@/composables/useWebSocket'
import { useBreakpoints } from '@/composables/useMediaQuery'

const authStore = useAuthStore()
const uiStore = useUiStore()
const route = useRoute()
const { isMobile } = useBreakpoints()

const mainWrapRef = ref<HTMLElement | null>(null)

const showActiveBanner = computed(() => route.name !== 'session-focus')

// Reset scroll ao trocar de rota — desktop usa main-wrap, mobile usa window
watch(
  () => route.path,
  () => {
    if (mainWrapRef.value) {
      if (isMobile.value) {
        window.scrollTo({ top: 0, behavior: 'auto' })
      }
      mainWrapRef.value.scrollTo({ top: 0, behavior: 'auto' })
    }
  },
  { flush: 'post' }
)

async function tryConnectWebSocket() {
  if (!authStore.sessionValidated || !authStore.user?.id) return
  try {
    await connectWebSocket(authStore.user.id)
  } catch {
    // WebSocket connection failed silently; polling fallback handles this
  }
}

onMounted(async () => {
  document.documentElement.setAttribute('data-theme', uiStore.theme)
  uiStore.applyCustomTheme()

  await tryConnectWebSocket()
})

watch(
  () => [authStore.sessionValidated, authStore.user?.id] as const,
  () => {
    void tryConnectWebSocket()
  }
)

watch(
  () => authStore.sessionValidated,
  (ok) => {
    if (!ok) disconnectWebSocket()
  }
)

onUnmounted(() => {
  disconnectWebSocket()
})

watch(
  () => uiStore.theme,
  (val) => {
    document.documentElement.setAttribute('data-theme', val)
    uiStore.applyCustomTheme()
    invalidateChartThemeCache()
    clearMeasureCache()
  }
)
</script>

<template>
  <div class="app-layout" :class="{ 'app-layout--sidebar-collapsed': uiStore.sidebarCollapsed }">
    <AppSidebar class="app-layout__sidebar" />
    <div ref="mainWrapRef" class="app-layout__main-wrap">
      <main class="app-layout__main">
        <ActiveSessionBanner v-if="showActiveBanner" />
        <div class="app-layout__content">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
  <MiniPlayer />
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
  position: absolute;
  inset: 0;
  background: var(--gradient-mesh);
  pointer-events: none;
  z-index: 0;
  opacity: 0.6;
}
.app-layout__main > * {
  position: relative;
  z-index: 1;
}
.app-layout__content {
  width: 100%;
  padding-block: var(--page-content-padding-block);
}

@media (min-width: 768px) {
  .app-layout__main {
    padding: var(--spacing-lg) var(--spacing-xl);
  }
}

@media (max-width: 768px) {
  .app-layout {
    height: auto;
    min-height: 100dvh;
    overflow: visible;
  }
  .app-layout__main-wrap {
    overflow: visible;
  }
  .app-layout__main {
    padding: var(--spacing-md);
  }
}
</style>
