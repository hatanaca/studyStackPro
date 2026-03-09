<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import Card from 'primevue/card'
import { sessionsApi } from '@/api/modules/sessions.api'
import { formatHours } from '@/utils/formatters'
import type { Technology } from '@/types/domain.types'

const props = defineProps<{
  technology: Pick<Technology, 'id' | 'name' | 'color'>
}>()

const totalMinutes = ref(0)
const loading = ref(true)

const totalHoursLabel = computed(() => formatHours(totalMinutes.value))

async function loadTotal() {
  if (!props.technology.id) return
  loading.value = true
  try {
    const { data } = await sessionsApi.list({
      technology_id: props.technology.id,
      per_page: 500,
    })
    if (data.success && Array.isArray(data.data)) {
      totalMinutes.value = data.data.reduce((sum, s) => sum + (s.duration_min ?? 0), 0)
    } else {
      totalMinutes.value = 0
    }
  } catch {
    totalMinutes.value = 0
  } finally {
    loading.value = false
  }
}

onMounted(loadTotal)
watch(() => props.technology.id, loadTotal)
</script>

<template>
  <Card
    class="technology-study-widget"
    :style="{ '--tech-color': technology.color }"
  >
    <template #content>
      <div class="technology-study-widget__bar" />
      <div class="technology-study-widget__content">
        <h3 class="technology-study-widget__name">{{ technology.name }}</h3>
        <p v-if="loading" class="technology-study-widget__total">...</p>
        <p v-else class="technology-study-widget__total">{{ totalHoursLabel }}</p>
        <RouterLink
          :to="{ name: 'sessions-by-technology', params: { id: technology.id } }"
          class="technology-study-widget__link"
        >
          Ver por dia
        </RouterLink>
      </div>
    </template>
  </Card>
</template>

<style scoped>
.technology-study-widget {
  position: relative;
  overflow: hidden;
  transition: box-shadow var(--duration-normal) var(--ease-in-out), border-color var(--duration-fast) ease;
}
.technology-study-widget:hover {
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--tech-color, var(--color-primary)) 40%, var(--color-border));
}
.technology-study-widget__bar {
  height: 3px;
  background: var(--tech-color, var(--color-primary));
}
.technology-study-widget__content {
  padding: var(--widget-padding);
}
.technology-study-widget__name {
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
  letter-spacing: -0.01em;
}
.technology-study-widget__total {
  font-size: var(--text-xl);
  font-weight: 700;
  color: var(--tech-color, var(--color-primary));
  margin: 0 0 var(--spacing-sm);
  letter-spacing: -0.02em;
}
.technology-study-widget__link {
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-primary);
  text-decoration: none;
  transition: color var(--duration-fast) ease;
}
.technology-study-widget__link:hover {
  color: var(--color-primary-hover);
  text-decoration: underline;
}
</style>
