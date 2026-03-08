<script setup lang="ts">
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
    dashboard: 'Top Métricas de Estudo',
    sessions: 'Sessões',
    technologies: 'Tecnologias',
    goals: 'Metas',
    export: 'Exportar',
    reports: 'Relatórios',
    help: 'Ajuda',
    settings: 'Preferências',
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
      <router-link
        to="/"
        class="app-topbar__brand"
      >
        <span class="app-topbar__logo">StudyTrack Pro</span>
      </router-link>
      <span class="app-topbar__pagetitle">{{ pageTitle }}</span>
    </div>

    <div class="app-topbar__center">
      <div
        v-if="authStore.user"
        class="app-topbar__profile"
      >
        <div class="app-topbar__avatar-wrap">
          <img
            v-if="authStore.user.avatar_url"
            :src="authStore.user.avatar_url"
            alt=""
            class="app-topbar__avatar"
          >
          <span
            v-else
            class="app-topbar__avatar app-topbar__avatar--fallback"
          >
            {{ userInitials }}
          </span>
        </div>
        <div class="app-topbar__profile-info">
          <span class="app-topbar__name">{{ authStore.user.name }}</span>
          <span class="app-topbar__badge">PRO</span>
        </div>
        <span
          class="app-topbar__chevron"
          aria-hidden="true"
        >▼</span>
      </div>
    </div>

    <div class="app-topbar__right">
      <div class="app-topbar__search">
        <span
          class="app-topbar__search-icon"
          aria-hidden="true"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          ><circle
            cx="11"
            cy="11"
            r="8"
          /><path d="m21 21-4.35-4.35" /></svg>
        </span>
        <input
          v-model="searchQuery"
          type="search"
          class="app-topbar__search-input"
          placeholder="Buscar..."
          aria-label="Buscar"
        >
      </div>
      <NotificationCenter />
      <router-link
        to="/settings"
        class="app-topbar__icon-btn"
        aria-label="Configurações"
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
      </router-link>
      <ThemeToggle />
    </div>
  </header>
</template>

<style scoped>
.app-topbar {
  height: var(--header-height, 4rem);
  min-height: 4rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--spacing-lg);
  background: var(--color-bg-soft);
  border-bottom: 1px solid var(--color-border);
  gap: var(--spacing-md);
  flex-shrink: 0;
}
.app-topbar__left {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  min-width: 0;
}
.app-topbar__brand {
  text-decoration: none;
  color: var(--color-text);
  font-weight: 700;
  font-size: var(--text-base);
  letter-spacing: -0.02em;
  white-space: nowrap;
}
.app-topbar__brand:hover {
  color: var(--color-primary);
}
.app-topbar__pagetitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  font-weight: 500;
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
  border-radius: 9999px;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  cursor: default;
}
.app-topbar__avatar-wrap {
  flex-shrink: 0;
}
.app-topbar__avatar {
  width: 2rem;
  height: 2rem;
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
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  background: var(--color-primary);
  color: #fff;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.app-topbar__chevron {
  font-size: 0.5rem;
  color: var(--color-text-muted);
  opacity: 0.8;
}
.app-topbar__right {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}
.app-topbar__search {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  min-width: 10rem;
  max-width: 14rem;
  height: 2.25rem;
  padding: 0 var(--spacing-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
}
.app-topbar__search-icon {
  flex-shrink: 0;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
}
.app-topbar__search-input {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  color: var(--color-text);
  font-size: var(--text-sm);
  outline: none;
}
.app-topbar__search-input::placeholder {
  color: var(--color-text-muted);
}
.app-topbar__icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  transition: color var(--duration-fast) ease, background var(--duration-fast) ease;
}
.app-topbar__icon-btn:hover {
  color: var(--color-primary);
  background: var(--color-primary-soft);
}
@media (max-width: 900px) {
  .app-topbar__pagetitle { display: none; }
  .app-topbar__search { display: none; }
  .app-topbar__name { max-width: 6rem; }
}
@media (max-width: 640px) {
  .app-topbar__center { display: none; }
  .app-topbar { padding: 0 var(--spacing-md); }
}
</style>
