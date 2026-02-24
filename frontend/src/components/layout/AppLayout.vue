<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import ActiveSessionBanner from '@/components/sessions/ActiveSessionBanner.vue'
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar">
      <h1 class="logo">StudyTrack Pro</h1>
      <nav>
        <RouterLink to="/" active-class="active">Dashboard</RouterLink>
        <RouterLink to="/sessions" active-class="active">Sessões</RouterLink>
        <RouterLink to="/technologies" active-class="active">Tecnologias</RouterLink>
      </nav>
      <button class="logout" @click="useAuthStore().logout()">Sair</button>
    </aside>
    <main class="main">
      <ActiveSessionBanner />
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
}
.sidebar {
  width: 220px;
  background: #1e293b;
  color: #e2e8f0;
  padding: 1rem;
  display: flex;
  flex-direction: column;
}
.logo {
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
}
.sidebar nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}
.sidebar nav a {
  color: #94a3b8;
  text-decoration: none;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
}
.sidebar nav a:hover,
.sidebar nav a.active {
  color: #fff;
  background: #334155;
}
.logout {
  margin-top: auto;
  padding: 0.5rem;
  background: transparent;
  border: 1px solid #475569;
  color: #94a3b8;
  border-radius: 0.375rem;
  cursor: pointer;
}
.logout:hover {
  background: #334155;
  color: #fff;
}
.main {
  flex: 1;
  padding: 1.5rem;
  background: #f8fafc;
}
</style>
