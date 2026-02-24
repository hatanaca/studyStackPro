<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  type ChartOptions
} from 'chart.js'
import { Doughnut } from 'vue-chartjs'
import type { TechnologyMetric } from '@/types/domain.types'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps<{
  metrics?: TechnologyMetric[]
  loading?: boolean
}>()

const router = useRouter()

const chartData = computed(() => {
  if (!props.metrics?.length) return { labels: [], datasets: [] }
  const top6 = props.metrics.slice(0, 6)
  const others = props.metrics.slice(6)
  const othersMinutes = others.reduce((s, m) => s + m.total_minutes, 0)

  const labels = top6.map((m) => m.technology?.name ?? '')
  const data = top6.map((m) => m.total_minutes)

  if (othersMinutes > 0) {
    labels.push('Outros')
    data.push(othersMinutes)
  }

  const colors = [
    '#3b82f6',
    '#10b981',
    '#f59e0b',
    '#ef4444',
    '#8b5cf6',
    '#ec4899',
    '#64748b'
  ]

  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor: colors.slice(0, labels.length),
        borderWidth: 0
      }
    ]
  }
})

const options: ChartOptions<'doughnut'> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' }
  },
  onClick: (_, elements) => {
    if (elements[0] && props.metrics?.[elements[0].index]) {
      const tech = props.metrics[elements[0].index]
      router.push(`/sessions?technology_id=${tech.technology?.id}`)
    } else if (elements[0]?.index === 6 && props.metrics?.length > 6) {
      router.push('/sessions')
    }
  }
}
</script>

<template>
  <div class="tech-dist-widget">
    <h3 class="title">Distribuição por Tecnologia</h3>
    <div v-if="loading" class="chart-skeleton" />
    <div v-else-if="chartData.labels.length" class="chart-wrap">
      <Doughnut :data="chartData" :options="options" />
      <p class="hint">Clique em uma fatia para ver sessões</p>
    </div>
    <p v-else class="empty">Nenhum dado ainda</p>
  </div>
</template>

<style scoped>
.tech-dist-widget {
  background: #fff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.title {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.75rem;
}
.chart-wrap {
  min-height: 200px;
  position: relative;
}
.chart-skeleton {
  min-height: 200px;
  background: #f1f5f9;
  border-radius: 0.25rem;
  animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
  50% {
    opacity: 0.7;
  }
}
.hint {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.5rem;
}
.empty {
  color: #94a3b8;
  font-size: 0.875rem;
}
</style>
