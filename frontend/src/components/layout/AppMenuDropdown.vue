<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import Button from 'primevue/button'
import OverlayPanel from 'primevue/overlaypanel'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import { disconnectWebSocket } from '@/composables/useWebSocket'
import {
  prefetchDashboardView,
  prefetchExportView,
  prefetchGoalsView,
  prefetchHelpView,
  prefetchProfileView,
  prefetchReportsView,
  prefetchSessionsView,
  prefetchSettingsView,
  prefetchTechnologiesView,
} from '@/router/prefetch'

const authStore = useAuthStore()
const route = useRoute()
const op = ref<InstanceType<typeof OverlayPanel> | null>(null)

function closeDropdown() {
  op.value?.hide()
}

function handleLogout() {
  try {
    disconnectWebSocket()
  } catch { /* ws already disconnected */ }
  authStore.logout()
}

type NavLink = {
  to: string | { name: string }
  label: string
  activePath: string
  prefetch?: () => void
}

const navLinks: NavLink[] = [
  { to: '/', label: 'Dashboard', activePath: '/', prefetch: prefetchDashboardView },
  { to: { name: 'sessions' }, label: 'Sessões', activePath: '/sessions', prefetch: prefetchSessionsView },
  { to: '/technologies', label: 'Tecnologias', activePath: '/technologies', prefetch: prefetchTechnologiesView },
  { to: '/goals', label: 'Metas', activePath: '/goals', prefetch: prefetchGoalsView },
  { to: '/export', label: 'Exportar', activePath: '/export', prefetch: prefetchExportView },
  { to: '/reports', label: 'Relatórios', activePath: '/reports', prefetch: prefetchReportsView },
  { to: '/help', label: 'Ajuda', activePath: '/help', prefetch: prefetchHelpView },
  { to: '/settings', label: 'Configurações', activePath: '/settings', prefetch: prefetchSettingsView },
  { to: '/profile', label: 'Perfil', activePath: '/profile', prefetch: prefetchProfileView },
]

function isActive(path: string) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>

<template>
  <Button
    type="button"
    text
    severity="secondary"
    class="app-menu-dropdown__trigger"
    aria-label="Abrir menu"
    aria-haspopup="true"
    @click="op?.toggle($event)"
  >
    <span class="app-menu-dropdown__hamburger" aria-hidden="true">
      <span />
      <span />
      <span />
    </span>
    <span class="app-menu-dropdown__label">Menu</span>
  </Button>
  <OverlayPanel ref="op" class="app-menu-dropdown__overlay">
    <div class="app-menu-dropdown__panel">
      <nav
        class="app-menu-dropdown__nav"
        role="navigation"
      >
        <RouterLink
          v-for="link in navLinks"
          :key="link.label"
          :to="link.to"
          class="app-menu-dropdown__link"
          :class="{ 'app-menu-dropdown__link--active': isActive(link.activePath) }"
          @mouseenter="link.prefetch?.()"
          @click="closeDropdown"
        >
          {{ link.label }}
        </RouterLink>
      </nav>
      <div class="app-menu-dropdown__theme">
        <span class="app-menu-dropdown__theme-label">Tema</span>
        <ThemeToggle variant="default" />
      </div>
      <div class="app-menu-dropdown__footer">
        <button
          type="button"
          class="app-menu-dropdown__logout"
          @click="handleLogout"
        >
          Sair
        </button>
      </div>
    </div>
  </OverlayPanel>
</template>

<style scoped>
.app-menu-dropdown__trigger {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-sm);
  min-height: var(--header-control-size);
  padding: var(--spacing-xs) var(--spacing-md);
  background: var(--color-bg-card);
  border: var(--card-chrome-border);
  border-radius: var(--radius-md);
  color: var(--color-text);
  font-size: var(--text-sm);
  font-weight: 500;
  cursor: pointer;
  box-shadow: var(--card-chrome-shadow);
  transition: background var(--duration-fast) ease, border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.app-menu-dropdown__trigger:hover {
  background: var(--color-primary-soft);
  border-color: color-mix(in srgb, var(--color-primary) 35%, var(--color-border));
  box-shadow: var(--shadow-sm);
}
.app-menu-dropdown__hamburger {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: var(--spacing-xs);
}
.app-menu-dropdown__hamburger span {
  display: block;
  width: var(--icon-size-md);
  height: var(--spacing-2xs);
  background: currentColor;
  border-radius: var(--radius-full);
}
.app-menu-dropdown__panel {
  min-width: clamp(11rem, 42vw, 14rem);
  padding: var(--spacing-sm) 0;
}
.app-menu-dropdown__nav {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
}
.app-menu-dropdown__link {
  display: block;
  padding: var(--spacing-sm) var(--spacing-lg);
  color: var(--color-text);
  text-decoration: none;
  font-size: var(--text-sm);
  border-radius: var(--radius-md);
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.app-menu-dropdown__link:hover {
  background: var(--color-bg);
  color: var(--color-primary);
}
.app-menu-dropdown__link:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.app-menu-dropdown__link--active {
  background: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 500;
}
.app-menu-dropdown__theme {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md) var(--spacing-lg);
  margin-top: var(--spacing-sm);
  border-top: 1px solid var(--color-border);
}
.app-menu-dropdown__theme-label {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text-muted);
}
.app-menu-dropdown__footer {
  padding: var(--spacing-sm) var(--spacing-lg) 0;
  margin-top: var(--spacing-xs);
  border-top: 1px solid var(--color-border);
}
.app-menu-dropdown__logout {
  width: 100%;
  padding: var(--spacing-sm);
  background: transparent;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  cursor: pointer;
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.app-menu-dropdown__logout:hover {
  background: var(--color-error-soft);
  border-color: var(--color-error);
  color: var(--color-error);
}
.app-menu-dropdown__logout:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
</style>
