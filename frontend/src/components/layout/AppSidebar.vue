<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useWebSocket } from '@/composables/useWebSocket'
import RealtimeBadge from '@/features/dashboard/components/RealtimeBadge.vue'

const authStore = useAuthStore()
const { disconnect } = useWebSocket()

function handleLogout() {
  disconnect()
  authStore.logout()
}
</script>

<template>
  <aside class="app-sidebar">
    <h1 class="app-sidebar__logo">
      StudyTrack Pro
    </h1>
    <RealtimeBadge class="app-sidebar__realtime" />
    <nav class="app-sidebar__nav">
      <RouterLink
        to="/"
        active-class="active"
      >
        Dashboard
      </RouterLink>
      <RouterLink
        to="/sessions"
        active-class="active"
      >
        Sessões
      </RouterLink>
      <RouterLink
        to="/technologies"
        active-class="active"
      >
        Tecnologias
      </RouterLink>
    </nav>
    <button
      class="app-sidebar__logout"
      @click="handleLogout"
    >
      Sair
    </button>
  </aside>
</template>

<style scoped>
.app-sidebar {
  width: 220px;
  background: #1e293b;
  color: #e2e8f0;
  padding: 1rem;
  display: flex;
  flex-direction: column;
}
.app-sidebar__logo {
  font-size: 1.1rem;
  margin-bottom: 0.75rem;
}
.app-sidebar__realtime {
  margin-bottom: 1rem;
}
.app-sidebar__nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}
.app-sidebar__nav a {
  color: #94a3b8;
  text-decoration: none;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
}
.app-sidebar__nav a:hover,
.app-sidebar__nav a.active {
  color: #fff;
  background: #334155;
}
.app-sidebar__logout {
  margin-top: auto;
  padding: 0.5rem;
  background: transparent;
  border: 1px solid #475569;
  color: #94a3b8;
  border-radius: 0.375rem;
  cursor: pointer;
}
.app-sidebar__logout:hover {
  background: #334155;
  color: #fff;
}
</style>
