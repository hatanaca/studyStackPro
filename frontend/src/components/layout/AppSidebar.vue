<script setup lang="ts">
/**
 * Sidebar de navegação. Links principais, resumo (horas, sessões, streak),
 * ThemeToggle, RealtimeBadge. Fecha ao mudar rota (mobile). Teleport para overlay.
 * Suporta estado colapsado (ícones apenas) via uiStore.sidebarCollapsed.
 */
import { computed, inject, useAttrs, watch } from 'vue'

defineOptions({ inheritAttrs: false })
import { RouterLink, useRoute } from 'vue-router'
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

/** Referências explícitas para o vue-tsc reconhecer uso (handlers no template). */
const prefetch = {
  help: prefetchHelpView,
  profile: prefetchProfileView,
  settings: prefetchSettingsView,
}
import { useAuthStore } from '@/stores/auth.store'
import { useUiStore } from '@/stores/ui.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import RealtimeBadge from '@/features/dashboard/components/RealtimeBadge.vue'
import { disconnectWebSocket } from '@/composables/useWebSocket'

const attrs = useAttrs()
const authStore = useAuthStore()
const uiStore = useUiStore()
const route = useRoute()
const analyticsStore = useAnalyticsStore()
const stakentStyle = inject<{ value: boolean }>('stakentStyle', { value: false })

const userInitials = computed(() => {
  const name = authStore.user?.name?.trim()
  if (!name) return 'ST'
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase()
})

function formatHours(h: number): string {
  if (h <= 0) return '0h'
  const int = Math.floor(h)
  const m = Math.round((h - int) * 60)
  return m === 0 ? `${int}h` : `${int}h ${m}min`
}

const sidebarSummary = computed(() => {
  const m = analyticsStore.userMetrics
  if (!m) return []
  return [
    {
      label: 'Total de horas',
      value: formatHours(m.total_hours ?? 0),
      color: 'var(--color-primary)',
    },
    { label: 'Sessões', value: String(m.total_sessions ?? 0), color: 'var(--color-success)' },
    { label: 'Streak', value: `${m.current_streak_days ?? 0} dias`, color: 'var(--color-warning)' },
  ]
})

watch(
  () => route.path,
  () => {
    uiStore.closeMobileSidebar()
  }
)

function handleLogout() {
  try {
    disconnectWebSocket()
  } catch {
    /* ws already disconnected */
  }
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
    :class="[
      attrs.class,
      {
        'app-sidebar--open': uiStore.mobileSidebarOpen,
        'app-sidebar--collapsed': uiStore.sidebarCollapsed,
      },
    ]"
  >
    <div class="app-sidebar__top">
      <div class="app-sidebar__brand">
        <h1 class="app-sidebar__logo">StudyTrack Pro</h1>
      </div>
      <button
        type="button"
        class="app-sidebar__toggle"
        :aria-label="uiStore.sidebarCollapsed ? 'Expandir menu' : 'Recolher menu'"
        :title="uiStore.sidebarCollapsed ? 'Expandir menu' : 'Recolher menu'"
        @click="uiStore.toggleSidebar()"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
          class="app-sidebar__toggle-icon"
        >
          <path d="M15 18l-6-6 6-6" />
        </svg>
      </button>
      <button
        type="button"
        class="app-sidebar__close"
        aria-label="Fechar menu"
        @click="uiStore.closeMobileSidebar()"
      >
        ✕
      </button>
    </div>
    <div v-if="authStore.user && !stakentStyle?.value" class="app-sidebar__profile">
      <div class="app-sidebar__avatar-wrap">
        <img
          v-if="authStore.user.avatar_url"
          :src="authStore.user.avatar_url"
          alt="Foto de perfil"
          class="app-sidebar__avatar"
        />
        <span v-else class="app-sidebar__avatar app-sidebar__avatar--fallback" aria-hidden="true">
          {{ userInitials }}
        </span>
      </div>
      <div class="app-sidebar__profile-meta">
        <p class="app-sidebar__profile-name">
          {{ authStore.user.name }}
        </p>
        <p class="app-sidebar__profile-email">
          {{ authStore.user.email }}
        </p>
      </div>
    </div>
    <div v-if="stakentStyle?.value" class="app-sidebar__pills">
      <RouterLink
        to="/"
        class="app-sidebar__pill"
        :class="{ active: route.path === '/' }"
        @mouseenter="prefetchDashboardView"
      >
        Estudo
      </RouterLink>
      <RouterLink
        to="/goals"
        class="app-sidebar__pill"
        :class="{ active: route.path.startsWith('/goals') }"
        @mouseenter="prefetchGoalsView"
      >
        Metas
      </RouterLink>
    </div>
    <nav class="app-sidebar__nav">
      <RouterLink
        to="/"
        exact-active-class="active"
        class="app-sidebar__link"
        title="Dashboard"
        aria-label="Ir para Dashboard"
        @mouseenter="prefetchDashboardView"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <rect width="7" height="9" x="3" y="3" rx="1" />
            <rect width="7" height="5" x="14" y="3" rx="1" />
            <rect width="7" height="9" x="14" y="12" rx="1" />
            <rect width="7" height="5" x="3" y="16" rx="1" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Dashboard</span>
      </RouterLink>
      <RouterLink
        :to="{ name: 'sessions' }"
        active-class="active"
        class="app-sidebar__link"
        title="Sessões"
        aria-label="Ir para Sessões"
        @mouseenter="prefetchSessionsView"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
            <path d="M8 7h8" />
            <path d="M8 11h8" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Sessões</span>
      </RouterLink>
      <RouterLink
        to="/technologies"
        active-class="active"
        class="app-sidebar__link"
        title="Tecnologias"
        aria-label="Ir para Tecnologias"
        @mouseenter="prefetchTechnologiesView"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <path d="M12 2v4" />
            <path d="m4.93 4.93 2.83 2.83" />
            <path d="M2 12h4" />
            <path d="m4.93 19.07 2.83-2.83" />
            <path d="M12 18v4" />
            <path d="m17.24 17.24 2.83-2.83" />
            <path d="M18 12h4" />
            <path d="m17.24 6.76 2.83 2.83" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Tecnologias</span>
      </RouterLink>
      <RouterLink
        to="/goals"
        active-class="active"
        class="app-sidebar__link"
        title="Metas"
        aria-label="Ir para Metas"
        @mouseenter="prefetchGoalsView"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <circle cx="12" cy="12" r="10" />
            <path d="M12 6v6l4 2" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Metas</span>
      </RouterLink>
      <RouterLink
        to="/export"
        active-class="active"
        class="app-sidebar__link"
        title="Exportar"
        aria-label="Ir para Exportar"
        @mouseenter="prefetchExportView"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Exportar</span>
      </RouterLink>
      <RouterLink
        to="/reports"
        active-class="active"
        class="app-sidebar__link"
        title="Relatórios"
        aria-label="Ir para Relatórios"
        @mouseenter="prefetchReportsView"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="16" y1="13" x2="8" y2="13" />
            <line x1="16" y1="17" x2="8" y2="17" />
            <polyline points="10 9 9 9 8 9" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Relatórios</span>
      </RouterLink>
      <RouterLink
        to="/help"
        active-class="active"
        class="app-sidebar__link"
        title="Ajuda"
        aria-label="Ir para Ajuda"
        @mouseenter="prefetch.help"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <circle cx="12" cy="12" r="10" />
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
            <path d="M12 17h.01" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Ajuda</span>
      </RouterLink>
      <RouterLink
        to="/settings"
        active-class="active"
        class="app-sidebar__link"
        title="Configurações"
        aria-label="Ir para Configurações"
        @mouseenter="prefetch.settings"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <path
              d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.73l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"
            />
            <circle cx="12" cy="12" r="3" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Configurações</span>
      </RouterLink>
      <RouterLink
        to="/profile"
        active-class="active"
        class="app-sidebar__link"
        title="Perfil"
        aria-label="Ir para Perfil"
        @mouseenter="prefetch.profile"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
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
          >
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
          </svg>
        </span>
        <span class="app-sidebar__link-text">Perfil</span>
      </RouterLink>
    </nav>
    <template v-if="stakentStyle?.value">
      <div class="app-sidebar__summary">
        <div v-for="s in sidebarSummary" :key="s.label" class="app-sidebar__summary-row">
          <span class="app-sidebar__summary-dot" :style="{ background: s.color }" />
          <span class="app-sidebar__summary-label">{{ s.label }}</span>
          <span class="app-sidebar__summary-value">{{ s.value }}</span>
        </div>
      </div>
      <div class="app-sidebar__cta">
        <span class="app-sidebar__cta-icon">⚡</span>
        <strong class="app-sidebar__cta-title">Ativar Super</strong>
        <p class="app-sidebar__cta-desc">Desbloqueie todos os recursos no StudyTrack Pro</p>
      </div>
    </template>
    <div class="app-sidebar__footer">
      <RealtimeBadge class="app-sidebar__realtime" />
      <button
        type="button"
        class="app-sidebar__link app-sidebar__theme-btn"
        :aria-label="uiStore.isDarkMode ? 'Usar tema claro' : 'Usar tema escuro'"
        :title="uiStore.isDarkMode ? 'Usar tema claro' : 'Usar tema escuro'"
        @click="uiStore.toggleTheme()"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
          <svg
            v-if="uiStore.isDarkMode"
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
            <circle cx="12" cy="12" r="4" />
            <path
              d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"
            />
          </svg>
          <svg
            v-else
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
        </span>
        <span class="app-sidebar__link-text">
          {{ uiStore.isDarkMode ? 'Tema claro' : 'Tema escuro' }}
        </span>
      </button>
      <button type="button" class="app-sidebar__logout" @click="handleLogout">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
          class="app-sidebar__logout-icon"
        >
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" y1="12" x2="9" y2="12" />
        </svg>
        <span class="app-sidebar__link-text">Sair</span>
      </button>
    </div>
  </aside>
</template>

<style scoped>
.app-sidebar {
  width: var(--sidebar-width);
  background: var(--color-bg-card);
  color: var(--color-text);
  padding: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  border-right: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  /* anima width e padding simultaneamente para colapso suave */
  transition:
    width var(--duration-slow) var(--ease-out-expo),
    padding var(--duration-slow) var(--ease-out-expo);
}
.app-sidebar--collapsed {
  width: var(--sidebar-width-collapsed);
  padding: var(--spacing-lg) var(--spacing-xs);
}
.app-sidebar--collapsed .app-sidebar__top {
  justify-content: center;
  gap: 0;
}
.app-sidebar--collapsed .app-sidebar__link {
  justify-content: center;
  padding: var(--spacing-xs);
  gap: 0;
}
.app-sidebar--collapsed .app-sidebar__logout {
  justify-content: center;
  padding: var(--spacing-xs);
  gap: 0;
  border-color: transparent;
  background: transparent;
}
.app-sidebar__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-lg);
  gap: var(--spacing-xs);
  min-width: 0;
}
.app-sidebar__brand {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  flex: 1;
  min-width: 0;
  overflow: hidden;
  transition:
    width var(--duration-slow) var(--ease-out-expo),
    opacity var(--duration-normal) ease;
}
.app-sidebar--collapsed .app-sidebar__brand {
  flex: 0 0 0px;
  width: 0;
  min-width: 0;
  opacity: 0;
  pointer-events: none;
  overflow: hidden;
}
.app-sidebar__logo {
  font-size: var(--text-base);
  font-weight: 600;
  letter-spacing: var(--tracking-tight);
  margin: 0;
  white-space: nowrap;
  color: var(--color-text);
  overflow: hidden;
}
.app-sidebar__toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 1.75rem;
  height: 1.75rem;
  padding: 0;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text-secondary);
  cursor: pointer;
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.app-sidebar__toggle:hover {
  background: var(--color-primary-soft);
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.app-sidebar__toggle:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.app-sidebar__toggle-icon {
  transition: transform var(--duration-slow) var(--ease-out-expo);
  will-change: transform;
}
.app-sidebar--collapsed .app-sidebar__toggle-icon {
  transform: rotate(180deg);
}
.app-sidebar__close {
  display: none;
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: var(--text-xl);
  cursor: pointer;
  padding: var(--spacing-xs);
  line-height: var(--leading-tight);
  transition: color var(--duration-fast) ease;
}
.app-sidebar__close:hover {
  color: var(--color-text);
}
.app-sidebar__pills {
  display: flex;
  gap: var(--spacing-2xs);
  padding: var(--spacing-xs) 0;
  margin-bottom: var(--spacing-sm);
}
.app-sidebar__pill {
  flex: 1;
  text-align: center;
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
  font-weight: 600;
  text-decoration: none;
  color: var(--color-text-muted);
  background: var(--color-bg-soft);
  border: 1px solid transparent;
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.app-sidebar__pill:hover {
  color: var(--color-text);
  background: var(--color-bg-card);
}
.app-sidebar__pill.active {
  background: var(--color-primary);
  color: var(--color-primary-contrast);
  border-color: var(--color-primary);
}
.app-sidebar__link:focus-visible,
.app-sidebar__pill:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.app-sidebar__close:focus-visible,
.app-sidebar__logout:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.app-sidebar__summary {
  margin-top: auto;
  padding: var(--spacing-sm);
  border-radius: var(--radius-md);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  margin-bottom: var(--spacing-sm);
}
.app-sidebar__summary-row {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-xs) 0;
  font-size: var(--text-xs);
}
.app-sidebar__summary-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  flex-shrink: 0;
}
.app-sidebar__summary-label {
  flex: 1;
  color: var(--color-text-muted);
}
.app-sidebar__summary-value {
  font-weight: 600;
  color: var(--color-text);
  font-variant-numeric: tabular-nums;
}
.app-sidebar__cta {
  padding: var(--spacing-lg);
  border-radius: var(--radius-md);
  background: color-mix(in srgb, var(--color-primary) 18%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-primary) 35%, transparent);
  margin-bottom: var(--spacing-lg);
}
.app-sidebar__cta-icon {
  font-size: var(--text-xl);
  display: block;
  margin-bottom: var(--spacing-xs);
}
.app-sidebar__cta-title {
  display: block;
  font-size: var(--text-sm);
  color: var(--color-text);
  margin-bottom: var(--spacing-2xs);
}
.app-sidebar__cta-desc {
  margin: 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.app-sidebar__profile {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
  border-radius: var(--radius-md);
  border: 1px solid color-mix(in srgb, var(--color-border) 85%, transparent);
  background: color-mix(in srgb, var(--color-bg-soft) 70%, transparent);
  overflow: hidden;
  min-width: 0;
  /* anima colapso via max-height em vez de display:none */
  max-height: 5rem;
  opacity: 1;
  pointer-events: auto;
  transition:
    max-height var(--duration-slow) var(--ease-out-expo),
    opacity var(--duration-normal) ease,
    margin-bottom var(--duration-slow) ease,
    padding var(--duration-slow) ease,
    border-color var(--duration-normal) ease;
}
.app-sidebar--collapsed .app-sidebar__profile {
  max-height: 0;
  opacity: 0;
  margin-bottom: 0;
  padding-top: 0;
  padding-bottom: 0;
  border-color: transparent;
  pointer-events: none;
}
.app-sidebar__avatar-wrap {
  flex-shrink: 0;
}
.app-sidebar__avatar {
  width: var(--avatar-size-sm);
  height: var(--avatar-size-sm);
  border-radius: 50%;
  object-fit: cover;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid color-mix(in srgb, var(--color-primary) 35%, var(--color-border));
  background: color-mix(in srgb, var(--color-primary-soft) 55%, var(--color-bg-card));
}
.app-sidebar__avatar--fallback {
  color: var(--color-primary);
  font-size: var(--text-xs);
  font-weight: 700;
}
.app-sidebar__profile-meta {
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  white-space: nowrap;
  transition:
    width var(--duration-slow) var(--ease-out-expo),
    opacity var(--duration-normal) ease;
}
.app-sidebar--collapsed .app-sidebar__profile-meta {
  width: 0;
  opacity: 0;
  pointer-events: none;
}
.app-sidebar__profile-name {
  margin: 0;
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.app-sidebar__profile-email {
  margin: 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ── Nav scrollável isolada ────────────────────────────────────────
   min-height: 0 é crítico para flex scroll funcionar corretamente.
   O cabeçalho (top + perfil) e o rodapé (footer) ficam fixos.
   ─────────────────────────────────────────────────────────────── */
.app-sidebar__nav {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  padding-inline: var(--spacing-2xs);
  padding-bottom: var(--spacing-xs);
  scrollbar-width: thin;
  scrollbar-color: var(--color-border) transparent;
}
.app-sidebar__nav::-webkit-scrollbar {
  width: var(--spacing-xs);
}
.app-sidebar__nav::-webkit-scrollbar-track {
  background: transparent;
}
.app-sidebar__nav::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: var(--radius-full);
}
.app-sidebar__link {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  color: var(--sidebar-link-color);
  text-decoration: none;
  padding: var(--spacing-xs) var(--spacing-sm);
  min-height: 2.25rem;
  border-radius: var(--radius-md);
  white-space: nowrap;
  font-size: var(--text-sm);
  font-weight: 500;
  transition:
    color var(--duration-fast) ease,
    background var(--duration-fast) ease,
    transform var(--duration-fast) ease;
  overflow: hidden;
}
.app-sidebar__link:hover {
  color: var(--sidebar-link-color-hover);
  background: var(--color-bg-soft);
  transform: translateX(var(--spacing-2xs));
}
.app-sidebar__link.active {
  color: var(--color-primary);
  background: var(--color-primary-soft);
  font-weight: 600;
}
.app-sidebar__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  opacity: 1;
  color: inherit;
}
.app-sidebar__icon svg {
  stroke-width: 2.5;
  stroke: currentColor;
}
.app-sidebar__link.active .app-sidebar__icon {
  opacity: 1;
}
.app-sidebar__link.active .app-sidebar__icon svg {
  stroke-width: 2;
}
.app-sidebar__link-text {
  overflow: hidden;
  white-space: nowrap;
  transition:
    width var(--duration-slow) var(--ease-out-expo),
    opacity var(--duration-normal) ease;
}
.app-sidebar--collapsed .app-sidebar__link-text {
  width: 0;
  opacity: 0;
  pointer-events: none;
}
.app-sidebar__footer {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding-top: var(--spacing-sm);
  border-top: 1px solid var(--color-border);
  margin-top: var(--spacing-xs);
  overflow: hidden;
}
.app-sidebar__realtime {
  overflow: hidden;
  transition:
    width var(--duration-slow) var(--ease-out-expo),
    opacity var(--duration-normal) ease;
}
.app-sidebar--collapsed .app-sidebar__realtime {
  width: 0;
  opacity: 0;
  pointer-events: none;
}
.app-sidebar__logout {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-xs) var(--spacing-sm);
  background: transparent;
  border: 1px solid var(--color-border);
  color: var(--sidebar-link-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  white-space: nowrap;
  font-size: var(--text-xs);
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
  overflow: hidden;
  min-width: 0;
}
.app-sidebar__logout:hover {
  background: var(--color-bg-soft);
  color: var(--sidebar-link-color-hover);
  border-color: var(--sidebar-link-color);
}
.app-sidebar__logout-icon {
  flex-shrink: 0;
}
.app-sidebar__theme-btn {
  background: transparent;
  border: none;
  width: 100%;
  text-align: left;
  font-size: var(--text-sm);
  cursor: pointer;
}

.app-sidebar-backdrop {
  display: none;
}

/* Desktop: sidebar em flow normal — o layout de scroll isolado (height: 100dvh + overflow-y: auto no main-wrap)
   mantém a sidebar "fixa" sem precisar de position: fixed.
   z-index: 2 garante que a sidebar fique acima do gradient ::before (z-index: 0) do main. */
@media (min-width: 768px) {
  .app-sidebar {
    position: relative;
    z-index: 2;
    height: 100dvh;
    flex-shrink: 0;
    overflow: hidden;
  }
  .app-sidebar__toggle {
    display: flex;
  }
}

/* Mobile: hamburger drawer */
@media (max-width: 768px) {
  .app-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: var(--z-overlay, 500);
    transform: translateX(-100%);
    transition: transform var(--duration-slow) var(--ease-out-expo);
    width: var(--sidebar-drawer-width);
    overflow-y: auto;
    overscroll-behavior: contain;
  }
  .app-sidebar--open {
    transform: translateX(0);
  }
  .app-sidebar__close {
    display: block;
  }
  .app-sidebar__toggle {
    display: none;
  }
  .app-sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: var(--overlay-backdrop);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: calc(var(--z-overlay, 500) - 1);
  }
  .app-sidebar {
    box-shadow: var(--overlay-shadow);
  }
}
</style>
