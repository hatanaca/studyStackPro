<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import SessionList from '@/features/sessions/components/SessionList.vue'

const route = useRoute()
const id = computed(() => route.params.id as string)
const sessionListRef = ref<{ openAddModal: () => void } | null>(null)
</script>

<template>
  <div class="tech-detail-subpage">
    <div class="tech-detail-subpage__toolbar">
      <Button label="Nova sessão" size="small" @click="sessionListRef?.openAddModal()" />
    </div>
    <SessionList ref="sessionListRef" :technology-id="id" />
  </div>
</template>

<style scoped>
.tech-detail-subpage {
  max-width: 100%;
}
.tech-detail-subpage__toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: var(--spacing-sm);
}
</style>
