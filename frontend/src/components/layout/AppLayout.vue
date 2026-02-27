<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useWebSocket } from '@/composables/useWebSocket'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import ActiveSessionBanner from '@/features/sessions/components/ActiveSessionBanner.vue'

const authStore = useAuthStore()
const { connect, disconnect } = useWebSocket()

onMounted(() => {
  if (authStore.user?.id) connect(authStore.user.id)
})

onUnmounted(() => {
  disconnect()
})
</script>

<template>
  <div class="app-layout">
    <AppSidebar />
    <main class="app-layout__main">
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
.app-layout__main {
  flex: 1;
  padding: 1.5rem;
  background: #f8fafc;
}
</style>
