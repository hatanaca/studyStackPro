<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import Card from 'primevue/card'
import Skeleton from 'primevue/skeleton'
import { prefetchTechnologyDetailView } from '@/router/prefetch'
import { sessionsApi } from '@/api/modules/sessions.api'
import { formatHours } from '@/utils/formatters'
import type { Technology } from '@/types/domain.types'

const props = withDefaults(
  defineProps<{
    technology: Pick<Technology, 'id' | 'name' | 'color'>
    /** Atrasa o primeiro pedido (ms) para espalhar carga quando há muitos cartões na grelha. */
    staggerMs?: number
  }>(),
  { staggerMs: 0 },
)

const totalMinutes = ref(0)
const loading = ref(true)

const totalHoursLabel = computed(() => formatHours(totalMinutes.value))

let loadTimer: ReturnType<typeof setTimeout> | null = null

async function loadTotal() {
  if (!props.technology.id) return
  loading.value = true
  try {
    const { data } = await sessionsApi.list({
      technology_id: props.technology.id,
      per_page: 50,
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

function scheduleLoadTotal() {
  if (loadTimer) {
    clearTimeout(loadTimer)
    loadTimer = null
  }
  const delay = props.staggerMs
  if (delay <= 0) {
    void loadTotal()
    return
  }
  loadTimer = setTimeout(() => {
    loadTimer = null
    void loadTotal()
  }, delay)
}

onMounted(scheduleLoadTotal)
watch(() => props.technology.id, scheduleLoadTotal)

onBeforeUnmount(() => {
  if (loadTimer) {
    clearTimeout(loadTimer)
    loadTimer = null
  }
})
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
        <p v-if="loading" class="technology-study-widget__total technology-study-widget__total--skeleton" aria-hidden="true">
          <Skeleton width="4rem" height="1.5rem" class="technology-study-widget__skeleton" />
        </p>
        <p v-else class="technology-study-widget__total">{{ totalMinutes === 0 ? '0h' : totalHoursLabel }}</p>
        <RouterLink
          :to="{ name: 'technology-detail', params: { id: technology.id } }"
          class="technology-study-widget__link"
          @mouseenter="prefetchTechnologyDetailView"
        >
          Ver sessões
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
  height: var(--spacing-xs);
  background: var(--tech-color, var(--color-primary));
}
.technology-study-widget__content {
  padding: var(--widget-padding);
}
.technology-study-widget__name {
  font-family: var(--font-display);
  font-size: var(--text-base);
  font-weight: 700;
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-tight);
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
}
.technology-study-widget__total {
  font-size: var(--text-xl);
  font-weight: 700;
  line-height: var(--leading-tight);
  letter-spacing: var(--tracking-tight);
  color: var(--tech-color, var(--color-primary));
  margin: 0 0 var(--spacing-sm);
}
.technology-study-widget__total--skeleton {
  display: flex;
  align-items: center;
  min-height: 1.5rem;
  color: transparent;
}
.technology-study-widget__skeleton {
  border-radius: var(--radius-sm);
}
.technology-study-widget__link {
  font-size: var(--text-sm);
  font-weight: 500;
  line-height: var(--leading-snug);
  color: var(--color-primary);
  text-decoration: none;
  transition: color var(--duration-fast) ease;
}
.technology-study-widget__link:hover {
  color: var(--color-primary-hover);
  text-decoration: underline;
}
</style>
