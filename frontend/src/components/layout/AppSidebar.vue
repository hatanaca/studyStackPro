<script setup lang="ts">
import { computed, inject, useAttrs, watch } from 'vue'

defineOptions({ inheritAttrs: false })
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useUiStore } from '@/stores/ui.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import RealtimeBadge from '@/features/dashboard/components/RealtimeBadge.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

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
    { label: 'Total de horas', value: formatHours(m.total_hours ?? 0), color: 'var(--color-primary)' },
    { label: 'Sessões', value: String(m.total_sessions ?? 0), color: 'var(--color-success)' },
    { label: 'Streak', value: `${m.current_streak_days ?? 0} dias`, color: 'var(--color-warning)' },
  ]
})

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
    :class="[attrs.class, { 'app-sidebar--open': uiStore.mobileSidebarOpen }]"
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
    <div
      v-if="authStore.user && !stakentStyle?.value"
      class="app-sidebar__profile"
    >
      <div class="app-sidebar__avatar-wrap">
        <img
          v-if="authStore.user.avatar_url"
          :src="authStore.user.avatar_url"
          alt="Foto de perfil"
          class="app-sidebar__avatar"
        >
        <span
          v-else
          class="app-sidebar__avatar app-sidebar__avatar--fallback"
          aria-hidden="true"
        >
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
    <div
      v-if="stakentStyle?.value"
      class="app-sidebar__pills"
    >
      <RouterLink
        to="/"
        class="app-sidebar__pill"
        :class="{ active: route.path === '/' }"
      >
        Estudo
      </RouterLink>
      <RouterLink
        to="/goals"
        class="app-sidebar__pill"
        :class="{ active: route.path.startsWith('/goals') }"
      >
        Metas
      </RouterLink>
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
          ><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" /><circle
            cx="12"
            cy="7"
            r="4"
          /></svg>
        </span>
        Perfil
      </RouterLink>
    </nav>
    <template v-if="stakentStyle?.value">
      <div class="app-sidebar__summary">
        <div
          v-for="s in sidebarSummary"
          :key="s.label"
          class="app-sidebar__summary-row"
        >
          <span
            class="app-sidebar__summary-dot"
            :style="{ background: s.color }"
          />
          <span class="app-sidebar__summary-label">{{ s.label }}</span>
          <span class="app-sidebar__summary-value">{{ s.value }}</span>
        </div>
      </div>
      <div class="app-sidebar__cta">
        <span class="app-sidebar__cta-icon">⚡</span>
        <strong class="app-sidebar__cta-title">Ativar Super</strong>
        <p class="app-sidebar__cta-desc">
          Desbloqueie todos os recursos no StudyTrack Pro
        </p>
      </div>
    </template>
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
  padding: var(--spacing-md);
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
  margin-bottom: var(--spacing-md);
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
  margin-bottom: var(--spacing-md);
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
  border-radius: 9999px;
  font-size: var(--text-xs);
  font-weight: 600;
  text-decoration: none;
  color: var(--color-text-muted);
  background: var(--color-bg-soft);
  border: 1px solid transparent;
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease, border-color var(--duration-fast) ease;
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
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
  background: color-mix(in srgb, var(--color-primary) 18%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-primary) 35%, transparent);
  margin-bottom: var(--spacing-md);
}
.app-sidebar__cta-icon {
  font-size: 1.25rem;
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
  line-height: 1.4;
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
}
.app-sidebar__avatar-wrap {
  flex-shrink: 0;
}
.app-sidebar__avatar {
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 50%;
  object-fit: cover;
  display: inline-flex;
  align-items: center;
  justify-content: center;
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
.app-sidebar__nav {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  flex: 1;
  padding-inline: var(--spacing-2xs);
}
.app-sidebar__link {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  color: var(--color-text-muted);
  text-decoration: none;
  padding: var(--spacing-xs) var(--spacing-sm);
  min-height: 2.25rem;
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
  padding-top: var(--spacing-sm);
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
