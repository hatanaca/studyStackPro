<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import TechnologyDetailMural from '@/features/technologies/components/TechnologyDetailMural.vue'

const route = useRoute()
const id = computed(() => route.params.id as string)
</script>

<template>
  <div class="tech-detail-subpage">
    <TechnologyDetailMural :technology-id="id" />
  </div>
</template>

<style scoped>
.tech-detail-subpage {
  max-width: 100%;
}
</style>
