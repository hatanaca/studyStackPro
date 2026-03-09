<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import Button from 'primevue/button'
import OverlayPanel from 'primevue/overlaypanel'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

const authStore = useAuthStore()
const route = useRoute()
const op = ref<InstanceType<typeof OverlayPanel> | null>(null)

function closeDropdown() {
  op.value?.hide()
}

async function handleLogout() {
  try {
    const { useWebSocket } = await import('@/composables/useWebSocket')
    useWebSocket().disconnect()
  } catch { /* ws already disconnected or never loaded */ }
  authStore.logout()
}

const navLinks = [
  { to: '/', label: 'Dashboard' },
  { to: '/sessions', label: 'Sessões' },
  { to: '/technologies', label: 'Tecnologias' },
  { to: '/goals', label: 'Metas' },
  { to: '/export', label: 'Exportar' },
  { to: '/reports', label: 'Relatórios' },
  { to: '/help', label: 'Ajuda' },
  { to: '/settings', label: 'Preferências' },
  { to: '/profile', label: 'Perfil' },
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
          :key="link.to"
          :to="link.to"
          class="app-menu-dropdown__link"
          :class="{ 'app-menu-dropdown__link--active': isActive(link.to) }"
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
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text);
  font-size: var(--text-sm);
  font-weight: 500;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: background var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.app-menu-dropdown__trigger:hover {
  background: var(--color-bg);
  border-color: var(--color-primary);
}
.app-menu-dropdown__hamburger {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
}
.app-menu-dropdown__hamburger span {
  display: block;
  width: 18px;
  height: 2px;
  background: currentColor;
  border-radius: 1px;
}
.app-menu-dropdown__panel {
  min-width: 12rem;
  padding: 0.5rem 0;
}
.app-menu-dropdown__nav {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}
.app-menu-dropdown__link {
  display: block;
  padding: 0.5rem 1rem;
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
.app-menu-dropdown__link--active {
  background: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 500;
}
.app-menu-dropdown__theme {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  margin-top: 0.5rem;
  border-top: 1px solid var(--color-border);
}
.app-menu-dropdown__theme-label {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
.app-menu-dropdown__footer {
  padding: 0.5rem 1rem 0;
  margin-top: 0.25rem;
  border-top: 1px solid var(--color-border);
}
.app-menu-dropdown__logout {
  width: 100%;
  padding: 0.5rem;
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
</style>
