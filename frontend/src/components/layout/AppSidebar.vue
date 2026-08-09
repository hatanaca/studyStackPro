<script setup lang="ts">
/**
 * Sidebar de navegação. Links principais, resumo (horas, sessões, streak),
 * ThemeToggle, RealtimeBadge. Fecha ao mudar rota (mobile). Teleport para overlay.
 * Suporta estado colapsado (ícones apenas) via uiStore.sidebarCollapsed.
 */
import { computed, inject, useAttrs, watch } from 'vue'
import { useMediaQuery } from '@vueuse/core'

defineOptions({ inheritAttrs: false })
import { RouterLink, useRoute } from 'vue-router'
import { sidebarNavItems, sidebarStakentPills } from '@/constants/sidebar-nav'
import SidebarIcon from './SidebarIcon.vue'

import { useAuthStore } from '@/stores/auth.store'
import { formatHours } from '@/utils/formatters'
import { useUiStore } from '@/stores/ui.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import RealtimeBadge from '@/features/dashboard/components/RealtimeBadge.vue'
import { disconnectWebSocket } from '@/composables/useWebSocket'

const attrs = useAttrs()
const authStore = useAuthStore()
const uiStore = useUiStore()
const route = useRoute()
const isDesktopLayout = useMediaQuery('(min-width: 768px)')
const analyticsStore = useAnalyticsStore()
const stakentStyle = inject<{ value: boolean }>('stakentStyle', { value: false })

const userInitials = computed(() => {
  const name = authStore.user?.name?.trim()
  if (!name) return 'ST'
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase()
})

/** Perfil completo: drawer mobile aberto ou sidebar expandida no desktop. */
const showFullProfileBlock = computed(
  () =>
    !!authStore.user &&
    !stakentStyle?.value &&
    (!uiStore.sidebarCollapsed || uiStore.mobileSidebarOpen)
)

/** Atalho só com avatar quando a barra está recolhida no desktop (evita duplicar no drawer mobile). */
const showCollapsedProfileShortcut = computed(
  () =>
    !!authStore.user &&
    !stakentStyle?.value &&
    uiStore.sidebarCollapsed &&
    !uiStore.mobileSidebarOpen &&
    isDesktopLayout.value
)

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
    <RouterLink
      v-if="showFullProfileBlock && authStore.user"
      :to="{ name: 'profile' }"
      class="app-sidebar__profile"
      :class="{
        'app-sidebar__profile--active': route.name === 'profile' && route.query.tab !== 'goals',
      }"
      @click="uiStore.closeMobileSidebar()"
    >
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
    </RouterLink>
    <RouterLink
      v-if="showCollapsedProfileShortcut && authStore.user"
      :to="{ name: 'profile' }"
      class="app-sidebar__profile-rail"
      :class="{
        'app-sidebar__profile-rail--active':
          route.name === 'profile' && route.query.tab !== 'goals',
      }"
      title="Perfil"
      aria-label="Abrir perfil"
      @click="uiStore.closeMobileSidebar()"
    >
      <img
        v-if="authStore.user.avatar_url"
        :src="authStore.user.avatar_url"
        alt=""
        class="app-sidebar__profile-rail-avatar"
      />
      <span
        v-else
        class="app-sidebar__profile-rail-avatar app-sidebar__profile-rail-avatar--fallback"
        aria-hidden="true"
      >
        {{ userInitials }}
      </span>
    </RouterLink>
    <div v-if="stakentStyle?.value" class="app-sidebar__pills">
      <RouterLink
        v-for="pill in sidebarStakentPills"
        :key="pill.label"
        :to="pill.to"
        class="app-sidebar__pill"
        :class="{ active: pill.isActive?.(route.path, route.name as string, route.query as Record<string, unknown>) }"
        @mouseenter="pill.prefetch?.()"
      >
        {{ pill.label }}
      </RouterLink>
    </div>
    <nav class="app-sidebar__nav">
      <RouterLink
        v-for="item in sidebarNavItems"
        :key="item.label"
        :to="item.to"
        :class="{ active: item.isActive?.(route.path, route.name as string, route.query as Record<string, unknown>) }"
        class="app-sidebar__link"
        :title="item.label"
        :aria-label="`Ir para ${item.label}`"
        @mouseenter="item.prefetch?.()"
      >
        <span class="app-sidebar__icon" aria-hidden="true">
          <SidebarIcon :name="item.icon" />
        </span>
        <span class="app-sidebar__link-content">
          <span class="app-sidebar__link-text">{{ item.label }}</span>
          <span class="app-sidebar__link-route">{{ item.routeHint }}</span>
        </span>
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
        <span class="app-sidebar__link-content">
          <span class="app-sidebar__link-text">
            {{ uiStore.isDarkMode ? 'Tema claro' : 'Tema escuro' }}
          </span>
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
        <span class="app-sidebar__link-content">
          <span class="app-sidebar__link-text">Sair</span>
        </span>
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
  overflow: hidden;
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
  font-family: var(--font-display);
  font-size: var(--text-sm);
  font-weight: 600;
  letter-spacing: var(--tracking-wide);
  text-transform: uppercase;
  margin: 0;
  white-space: nowrap;
  color: var(--color-accent);
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
  background: var(--color-accent);
  color: var(--color-bg);
  border-color: var(--color-accent);
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
  background: color-mix(in srgb, var(--color-text) 3%, transparent);
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
  background: var(--color-accent-soft);
  border: 1px solid color-mix(in srgb, var(--color-accent) 20%, transparent);
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
  color: var(--color-accent);
  margin-bottom: var(--spacing-2xs);
  font-weight: 700;
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
  border: 1px solid var(--color-border);
  background: color-mix(in srgb, var(--color-text) 3%, transparent);
  overflow: hidden;
  min-width: 0;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  max-height: 5rem;
  opacity: 1;
  pointer-events: auto;
  transition:
    max-height var(--duration-slow) var(--ease-out-expo),
    opacity var(--duration-normal) ease,
    margin-bottom var(--duration-slow) ease,
    padding var(--duration-slow) ease,
    border-color var(--duration-normal) ease,
    background var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.app-sidebar__profile:hover {
  border-color: color-mix(in srgb, var(--color-text) 12%, transparent);
  background: color-mix(in srgb, var(--color-text) 6%, transparent);
}
.app-sidebar__profile:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.app-sidebar__profile--active {
  border-color: color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
  background: var(--color-primary-soft);
}
.app-sidebar__profile-rail {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--spacing-sm);
  padding: var(--spacing-xs);
  border-radius: var(--radius-md);
  border: 1px solid color-mix(in srgb, var(--color-border) 85%, transparent);
  background: color-mix(in srgb, var(--color-bg-soft) 70%, transparent);
  text-decoration: none;
  color: inherit;
  transition:
    border-color var(--duration-fast) ease,
    background var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.app-sidebar__profile-rail:hover {
  border-color: color-mix(in srgb, var(--color-primary) 28%, var(--color-border));
  background: color-mix(in srgb, var(--color-primary-soft) 35%, var(--color-bg-soft));
}
.app-sidebar__profile-rail:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.app-sidebar__profile-rail--active {
  border-color: color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
  background: var(--color-primary-soft);
}
.app-sidebar__profile-rail-avatar {
  width: var(--avatar-size-sm);
  height: var(--avatar-size-sm);
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid color-mix(in srgb, var(--color-primary) 35%, var(--color-border));
  background: color-mix(in srgb, var(--color-primary-soft) 55%, var(--color-bg-card));
}
.app-sidebar__profile-rail-avatar--fallback {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary);
  font-size: var(--text-xs);
  font-weight: 700;
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
  padding: var(--spacing-sm) var(--spacing-md);
  min-height: 2.75rem;
  border-radius: var(--radius-md);
  white-space: nowrap;
  font-size: var(--text-sm);
  font-weight: 500;
  position: relative;
  transition:
    color var(--duration-fast) ease,
    background var(--duration-fast) ease;
  overflow: hidden;
}
.app-sidebar__link:hover {
  color: var(--sidebar-link-color-hover);
  background: color-mix(in srgb, var(--color-text) 4%, transparent);
}
.app-sidebar__link.active {
  color: var(--color-text);
  background: color-mix(in srgb, var(--color-text) 5%, transparent);
  font-weight: 600;
}
.app-sidebar__link.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  background: var(--sidebar-active-indicator);
  border-radius: 0 var(--radius-full) var(--radius-full) 0;
}
[data-theme='light'] .app-sidebar__link:hover {
  background: color-mix(in srgb, var(--color-text) 3%, transparent);
}
[data-theme='light'] .app-sidebar__link.active {
  background: var(--color-primary-soft);
  color: var(--color-primary);
}
[data-theme='light'] .app-sidebar__link.active::before {
  background: var(--color-primary);
}
.app-sidebar__link-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.app-sidebar__link-route {
  font-size: 10px;
  color: var(--color-text-muted);
  opacity: 0.6;
  font-weight: 400;
  line-height: 1.2;
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
.app-sidebar--collapsed .app-sidebar__link-text,
.app-sidebar--collapsed .app-sidebar__link-route {
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

/* Mobile: hamburger drawer — full page */
@media (max-width: 768px) {
  .app-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    height: 100dvh;
    width: 100vw;
    z-index: var(--z-overlay, 500);
    transform: translateX(-100%);
    transition: transform var(--duration-slow) var(--ease-out-expo);
    overflow-y: auto;
    overscroll-behavior: contain;
    box-shadow: var(--overlay-shadow);
    padding: var(--spacing-xl);
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
  .app-sidebar__link {
    padding: var(--spacing-md) var(--spacing-lg);
    min-height: 3.25rem;
    font-size: var(--text-base);
    border-radius: var(--radius-lg);
  }
  .app-sidebar__link-content {
    align-items: center;
  }
  .app-sidebar__link-route {
    font-size: 11px;
  }
  .app-sidebar__nav {
    gap: var(--spacing-sm);
  }
  .app-sidebar__theme-btn {
    padding: var(--spacing-md) var(--spacing-lg);
    font-size: var(--text-base);
    min-height: 3.25rem;
    border-radius: var(--radius-lg);
  }
  .app-sidebar__logout {
    padding: var(--spacing-md) var(--spacing-lg);
    font-size: var(--text-base);
    min-height: 3.25rem;
    border-radius: var(--radius-lg);
  }
  .app-sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    height: 100dvh;
    width: 100vw;
    background: var(--overlay-backdrop);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: calc(var(--z-overlay, 500) - 1);
  }
}
</style>
