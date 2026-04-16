<script setup lang="ts">
/**
 * Barra superior (estilo stakent). Logo, título da página, NotificationCenter, ThemeToggle.
 * Visível apenas quando stakentStyle=true.
 */
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import NotificationCenter from '@/features/notifications/components/NotificationCenter.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

const authStore = useAuthStore()
const route = useRoute()
const searchQuery = ref('')

const pageTitle = computed(() => {
  const name = route.name?.toString() ?? ''
  const map: Record<string, string> = {
    dashboard: 'Dashboard',
    sessions: 'Sessões',
    'session-focus': 'Sessão ativa',
    technologies: 'Tecnologias',
    goals: 'Metas',
    export: 'Exportar',
    reports: 'Relatórios',
    help: 'Ajuda',
    settings: 'Configurações',
    profile: 'Perfil',
  }
  return map[name] ?? 'Dashboard'
})

const userInitials = computed(() => {
  const name = authStore.user?.name?.trim()
  if (!name) return 'ST'
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase()
})
</script>

<template>
  <header class="app-topbar">
    <div class="app-topbar__left">
      <router-link to="/" class="app-topbar__brand">
        <span class="app-topbar__logo">StudyTrack Pro</span>
      </router-link>
      <span class="app-topbar__pagetitle">{{ pageTitle }}</span>
    </div>

    <div class="app-topbar__center">
      <div v-if="authStore.user" class="app-topbar__profile">
        <div class="app-topbar__avatar-wrap">
          <img
            v-if="authStore.user.avatar_url"
            :src="authStore.user.avatar_url"
            alt=""
            class="app-topbar__avatar"
          />
          <span v-else class="app-topbar__avatar app-topbar__avatar--fallback">
            {{ userInitials }}
          </span>
        </div>
        <div class="app-topbar__profile-info">
          <span class="app-topbar__name">{{ authStore.user.name }}</span>
          <span class="app-topbar__badge">PRO</span>
        </div>
        <span class="app-topbar__chevron" aria-hidden="true">▼</span>
      </div>
    </div>

    <div class="app-topbar__right">
      <div class="app-topbar__search">
        <span class="app-topbar__search-icon" aria-hidden="true">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="app-topbar__search-svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
        </span>
        <input
          v-model="searchQuery"
          type="search"
          class="app-topbar__search-input"
          placeholder="Buscar..."
          aria-label="Buscar"
        />
      </div>
      <NotificationCenter />
      <router-link to="/settings" class="app-topbar__icon-btn" aria-label="Configurações">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="app-topbar__action-svg"
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
      </router-link>
      <ThemeToggle />
    </div>
  </header>
</template>

<style scoped>
.app-topbar {
  height: var(--header-height);
  min-height: var(--header-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--spacing-xl);
  background: var(--shell-topbar-bg);
  border-bottom: 1px solid color-mix(in srgb, var(--color-border) 85%, transparent);
  gap: var(--spacing-lg);
  flex-shrink: 0;
}
.app-topbar__left {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  min-width: 0;
}
.app-topbar__brand {
  text-decoration: none;
  color: var(--color-text);
  font-family: var(--font-display);
  font-weight: 700;
  font-size: var(--text-base);
  letter-spacing: var(--tracking-tight);
  white-space: nowrap;
}
.app-topbar__brand:hover {
  color: var(--color-primary);
}
.app-topbar__brand:focus-visible,
.app-topbar__icon-btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.app-topbar__pagetitle {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: var(--tracking-wide);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.app-topbar__center {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 1 auto;
}
.app-topbar__profile {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-full);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  cursor: default;
}
.app-topbar__avatar-wrap {
  flex-shrink: 0;
}
.app-topbar__avatar {
  width: var(--avatar-size-sm);
  height: var(--avatar-size-sm);
  border-radius: 50%;
  object-fit: cover;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-xs);
  font-weight: 700;
  color: var(--color-primary);
  background: var(--color-primary-soft);
  border: 1px solid var(--color-border);
}
.app-topbar__profile-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}
.app-topbar__name {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 10rem;
}
.app-topbar__badge {
  font-size: var(--text-xs);
  font-weight: 700;
  padding: var(--spacing-2xs) var(--spacing-xs);
  border-radius: var(--radius-sm);
  background: var(--color-primary);
  color: var(--color-primary-contrast);
  text-transform: uppercase;
  letter-spacing: var(--tracking-wide);
}
.app-topbar__chevron {
  font-size: var(--text-2xs);
  color: var(--color-text-muted);
  opacity: 0.8;
}
.app-topbar__right {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  min-width: 0;
}
.app-topbar__search {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  width: clamp(10rem, 24vw, 14rem);
  min-height: var(--header-control-size);
  padding: 0 var(--spacing-sm);
  border-radius: var(--radius-full);
  border: 1px solid color-mix(in srgb, var(--color-border) 88%, transparent);
  background: color-mix(in srgb, var(--color-bg-card) 94%, var(--color-bg-soft));
  box-shadow: inset 0 1px 2px color-mix(in srgb, var(--color-text) 5%, transparent);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.app-topbar__search-icon {
  flex-shrink: 0;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
}
.app-topbar__search-svg {
  width: var(--icon-size-md);
  height: var(--icon-size-md);
  flex-shrink: 0;
}
.app-topbar__action-svg {
  width: var(--icon-size-md);
  height: var(--icon-size-md);
}
.app-topbar__search-input {
  flex: 1;
  min-width: 0;
  min-height: var(--input-height-sm);
  border: none;
  background: transparent;
  color: var(--form-input-text);
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  outline: none;
}
.app-topbar__search-input::placeholder {
  color: var(--form-input-placeholder);
}
.app-topbar__search-input:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-radius: var(--radius-sm);
}
.app-topbar__search:focus-within {
  border-color: var(--color-primary);
  box-shadow:
    inset 0 1px 2px color-mix(in srgb, var(--color-text) 5%, transparent),
    0 0 0 1px color-mix(in srgb, var(--color-primary) 25%, transparent);
}
.app-topbar__icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--header-control-size);
  height: var(--header-control-size);
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  transition:
    color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.app-topbar__icon-btn:hover {
  color: var(--color-primary);
  background: var(--color-primary-soft);
}
@media (max-width: 1024px) {
  .app-topbar__pagetitle {
    display: none;
  }
  .app-topbar__search {
    display: none;
  }
  .app-topbar__name {
    max-width: 6rem;
  }
}
@media (max-width: 640px) {
  .app-topbar__center {
    display: none;
  }
  .app-topbar {
    padding: 0 var(--spacing-lg);
  }
}
</style>
