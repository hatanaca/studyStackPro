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
    /** `list`: fila compacta (página Sessões); `card`: cartão PrimeVue. */
    variant?: 'card' | 'list'
  }>(),
  { staggerMs: 0, variant: 'card' }
)

const emit = defineEmits<{
  /** Na variante lista (página Sessões): filtrar a lista de sessões por esta tecnologia. */
  viewSessionList: [technologyId: string]
}>()

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
    v-if="variant === 'card'"
    class="technology-study-widget"
    :style="{ '--tech-color': technology.color }"
  >
    <template #content>
      <div class="technology-study-widget__bar" />
      <div class="technology-study-widget__content">
        <h3 class="technology-study-widget__name">{{ technology.name }}</h3>
        <div class="technology-study-widget__metric">
          <span class="technology-study-widget__metric-label">Tempo de estudo</span>
          <p
            v-if="loading"
            class="technology-study-widget__total technology-study-widget__total--skeleton"
            aria-hidden="true"
          >
            <Skeleton width="3.25rem" height="1.125rem" class="technology-study-widget__skeleton" />
          </p>
          <p v-else class="technology-study-widget__total">
            {{ totalMinutes === 0 ? '0h' : totalHoursLabel }}
          </p>
        </div>
        <div class="technology-study-widget__metric technology-study-widget__metric--link">
          <span class="technology-study-widget__metric-label">Sessões</span>
          <RouterLink
            :to="{ name: 'technology-detail', params: { id: technology.id } }"
            class="technology-study-widget__link"
            @mouseenter="prefetchTechnologyDetailView"
          >
            Ver sessões
          </RouterLink>
        </div>
      </div>
    </template>
  </Card>

  <article
    v-else
    class="technology-study-widget technology-study-widget--list"
    :style="{ '--tech-color': technology.color }"
  >
    <div
      class="technology-study-widget__bar technology-study-widget__bar--side"
      aria-hidden="true"
    />
    <div class="technology-study-widget__list-inner">
      <div class="technology-study-widget__list-head">
        <h3 class="technology-study-widget__name technology-study-widget__name--list">
          {{ technology.name }}
        </h3>
        <RouterLink
          :to="{ name: 'technology-detail', params: { id: technology.id } }"
          class="technology-study-widget__link technology-study-widget__link--list"
          @mouseenter="prefetchTechnologyDetailView"
        >
          Abrir
        </RouterLink>
      </div>
      <div class="technology-study-widget__list-meta">
        <span class="technology-study-widget__metric-label">Tempo de estudo</span>
        <template v-if="loading">
          <Skeleton width="2.75rem" height="0.875rem" class="technology-study-widget__skeleton" />
        </template>
        <span v-else class="technology-study-widget__total technology-study-widget__total--inline">
          {{ totalMinutes === 0 ? '0h' : totalHoursLabel }}
        </span>
        <span class="technology-study-widget__meta-sep" aria-hidden="true">·</span>
        <span class="technology-study-widget__metric-label">Sessões</span>
        <button
          type="button"
          class="technology-study-widget__link technology-study-widget__link--inline technology-study-widget__link--btn"
          @click="emit('viewSessionList', technology.id)"
        >
          Ver lista
        </button>
      </div>
    </div>
  </article>
</template>

<style scoped>
.technology-study-widget {
  position: relative;
  width: 100%;
  overflow: hidden;
  transition:
    box-shadow var(--duration-normal) var(--ease-in-out),
    border-color var(--duration-fast) ease;
}
.technology-study-widget:hover {
  box-shadow: var(--shadow-md);
  border-color: color-mix(
    in srgb,
    var(--tech-color, var(--color-primary)) 40%,
    var(--color-border)
  );
}
.technology-study-widget__bar {
  height: var(--spacing-xs);
  background: var(--tech-color, var(--color-primary));
}
.technology-study-widget__content {
  padding: var(--widget-padding-sm);
}
.technology-study-widget__name {
  font-family: var(--font-display);
  font-size: var(--text-sm);
  font-weight: 700;
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-tight);
  color: var(--color-text);
  margin: 0 0 var(--spacing-2xs);
}
.technology-study-widget__metric {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--spacing-sm);
  margin-bottom: 2px;
}
.technology-study-widget__metric--link {
  align-items: center;
  margin-bottom: 0;
  margin-top: 2px;
}
.technology-study-widget__metric-label {
  font-size: var(--text-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  white-space: nowrap;
}
.technology-study-widget__total {
  font-size: var(--text-lg);
  font-weight: 700;
  line-height: var(--leading-tight);
  letter-spacing: var(--tracking-tight);
  color: var(--tech-color, var(--color-primary));
  margin: 0;
  text-align: right;
}
.technology-study-widget__total--skeleton {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  min-height: 1.125rem;
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

/* ── Variante lista (Sessões / Por tecnologia) ── */
.technology-study-widget--list {
  display: flex;
  align-items: stretch;
  min-height: 0;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: none;
  transition:
    border-color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.technology-study-widget--list:hover {
  border-color: color-mix(
    in srgb,
    var(--tech-color, var(--color-primary)) 35%,
    var(--color-border)
  );
  background: color-mix(in srgb, var(--color-bg-soft) 40%, var(--color-bg-card));
}
.technology-study-widget__bar--side {
  width: 3px;
  height: auto;
  min-height: 100%;
  flex-shrink: 0;
  border-radius: var(--radius-md) 0 0 var(--radius-md);
}
.technology-study-widget__list-inner {
  flex: 1;
  min-width: 0;
  padding: var(--spacing-sm) var(--spacing-md);
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.technology-study-widget__list-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
}
.technology-study-widget__name--list {
  margin: 0;
  font-size: var(--text-sm);
}
.technology-study-widget__link--list {
  font-size: var(--text-xs);
  font-weight: 600;
  flex-shrink: 0;
}
.technology-study-widget__list-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.25rem 0.5rem;
  font-size: var(--text-xs);
}
.technology-study-widget__total--inline {
  font-size: var(--text-xs);
  font-weight: 700;
  color: var(--tech-color, var(--color-primary));
}
.technology-study-widget__link--inline {
  font-size: var(--text-xs);
  font-weight: 500;
}
.technology-study-widget__link--btn {
  font: inherit;
  font-size: var(--text-xs);
  font-weight: 500;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  text-align: inherit;
}
.technology-study-widget__link--btn:hover {
  text-decoration: underline;
}
.technology-study-widget__meta-sep {
  color: var(--color-text-muted);
  user-select: none;
}
</style>
