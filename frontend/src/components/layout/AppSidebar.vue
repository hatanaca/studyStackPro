<script setup lang="ts">
import { watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useUiStore } from '@/stores/ui.store'
import RealtimeBadge from '@/features/dashboard/components/RealtimeBadge.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

const authStore = useAuthStore()
const uiStore = useUiStore()
const route = useRoute()

watch(() => route.path, () => {
  uiStore.closeMobileSidebar()
})

async function handleLogout() {
  try {
    const { useWebSocket } = await import('@/composables/useWebSocket')
    useWebSocket().disconnect()
  } catch { /* ws already disconnected or never loaded */ }
  uiStore.closeMobileSidebar()
  authStore.logout()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="uiStore.mobileSidebarOpen"
        class="app-sidebar-backdrop"
        @click="uiStore.closeMobileSidebar()"
      />
    </Transition>
  </Teleport>
  <aside
    class="app-sidebar"
    :class="{ 'app-sidebar--open': uiStore.mobileSidebarOpen }"
  >
    <div class="app-sidebar__top">
      <div class="app-sidebar__brand">
        <h1 class="app-sidebar__logo">
          StudyTrack Pro
        </h1>
        <ThemeToggle
          variant="sidebar"
          class="app-sidebar__theme"
        />
      </div>
      <button
        type="button"
        class="app-sidebar__close"
        aria-label="Fechar menu"
        @click="uiStore.closeMobileSidebar()"
      >
        ✕
      </button>
    </div>
    <RealtimeBadge class="app-sidebar__realtime" />
    <nav class="app-sidebar__nav">
      <RouterLink
        to="/"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><rect
            width="7"
            height="9"
            x="3"
            y="3"
            rx="1"
          /><rect
            width="7"
            height="5"
            x="14"
            y="3"
            rx="1"
          /><rect
            width="7"
            height="9"
            x="14"
            y="12"
            rx="1"
          /><rect
            width="7"
            height="5"
            x="3"
            y="16"
            rx="1"
          /></svg>
        </span>
        Dashboard
      </RouterLink>
      <RouterLink
        to="/sessions"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><circle
            cx="12"
            cy="12"
            r="10"
          /><polyline points="12 6 12 12 16 14" /></svg>
        </span>
        Sessões
      </RouterLink>
      <RouterLink
        to="/technologies"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><path d="M12 2v4" /><path d="m4.93 4.93 2.83 2.83" /><path d="M2 12h4" /><path d="m4.93 19.07 2.83-2.83" /><path d="M12 18v4" /><path d="m17.24 17.24 2.83-2.83" /><path d="M18 12h4" /><path d="m17.24 6.76 2.83 2.83" /></svg>
        </span>
        Tecnologias
      </RouterLink>
      <RouterLink
        to="/goals"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><circle
            cx="12"
            cy="12"
            r="10"
          /><path d="M12 6v6l4 2" /></svg>
        </span>
        Metas
      </RouterLink>
      <RouterLink
        to="/export"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="7 10 12 15 17 10" /><line
            x1="12"
            y1="15"
            x2="12"
            y2="3"
          /></svg>
        </span>
        Exportar
      </RouterLink>
      <RouterLink
        to="/reports"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line
            x1="16"
            y1="13"
            x2="8"
            y2="13"
          /><line
            x1="16"
            y1="17"
            x2="8"
            y2="17"
          /><polyline points="10 9 9 9 8 9" /></svg>
        </span>
        Relatórios
      </RouterLink>
      <RouterLink
        to="/help"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><circle
            cx="12"
            cy="12"
            r="10"
          /><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" /><path d="M12 17h.01" /></svg>
        </span>
        Ajuda
      </RouterLink>
      <RouterLink
        to="/settings"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.73l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" /><circle
            cx="12"
            cy="12"
            r="3"
          /></svg>
        </span>
        Preferências
      </RouterLink>
      <RouterLink
        to="/profile"
        active-class="active"
        class="app-sidebar__link"
      >
        <span
          class="app-sidebar__icon"
          aria-hidden="true"
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
          ><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.73l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" /><circle
            cx="12"
            cy="12"
            r="3"
          /></svg>
        </span>
        Configurações
      </RouterLink>
    </nav>
    <div class="app-sidebar__footer">
      <button
        type="button"
        class="app-sidebar__logout"
        @click="handleLogout"
      >
        Sair
      </button>
    </div>
  </aside>
</template>

<style scoped>
.app-sidebar {
  width: var(--sidebar-width);
  background: var(--color-bg);
  color: var(--color-text);
  padding: var(--spacing-sm) var(--spacing-md);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  border-right: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}
.app-sidebar__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-sm);
}
.app-sidebar__brand {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}
.app-sidebar__logo {
  font-size: var(--text-base);
  font-weight: 600;
  letter-spacing: -0.02em;
  margin: 0;
  white-space: nowrap;
  color: var(--color-text);
}
.app-sidebar__close {
  display: none;
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: 1.25rem;
  cursor: pointer;
  padding: var(--spacing-xs);
  line-height: 1;
  transition: color var(--duration-fast) ease;
}
.app-sidebar__close:hover {
  color: var(--color-text);
}
.app-sidebar__realtime {
  margin-bottom: var(--spacing-sm);
}
.app-sidebar__nav {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
  flex: 1;
}
.app-sidebar__link {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  color: var(--color-text-muted);
  text-decoration: none;
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-md);
  white-space: nowrap;
  font-size: var(--text-sm);
  transition: color var(--duration-fast) ease,
    background var(--duration-fast) ease,
    transform var(--duration-fast) ease;
}
.app-sidebar__link:hover {
  color: var(--color-text);
  background: var(--color-bg-soft);
  transform: translateX(2px);
}
.app-sidebar__link.active {
  color: var(--color-primary);
  background: var(--color-primary-soft);
}
.app-sidebar__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  opacity: 0.9;
}
.app-sidebar__link.active .app-sidebar__icon {
  opacity: 1;
}
.app-sidebar__footer {
  margin-top: auto;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}
.app-sidebar__logout {
  padding: var(--spacing-xs) var(--spacing-sm);
  background: transparent;
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  border-radius: var(--radius-md);
  cursor: pointer;
  white-space: nowrap;
  font-size: var(--text-xs);
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.app-sidebar__logout:hover {
  background: var(--color-bg-soft);
  color: var(--color-text);
  border-color: var(--color-text-muted);
}

.app-sidebar-backdrop {
  display: none;
}

/* Desktop: sidebar sempre visível */
@media (min-width: 769px) {
  .app-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 40;
    transform: none;
    overflow-y: auto;
    overflow-x: hidden;
  }
}

/* Mobile: hamburger drawer */
@media (max-width: 768px) {
  .app-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    width: min(280px, 85vw);
  }
  .app-sidebar--open {
    transform: translateX(0);
  }
  .app-sidebar__close {
    display: block;
  }
  .app-sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99;
  }
  .app-sidebar {
    box-shadow: 8px 0 32px rgba(0, 0, 0, 0.3);
  }
}
</style>
