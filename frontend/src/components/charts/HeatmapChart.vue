<script setup lang="ts">
import { ref, computed } from 'vue'

export interface HeatmapDay {
  date: string
  total_minutes: number
}

const props = defineProps<{
  data?: HeatmapDay[]
  title?: string
  showYearSelector?: boolean
}>()

const currentYear = new Date().getFullYear()
const selectedYear = ref(currentYear)

const weeks = computed(() => {
  const days: { date: string; minutes: number }[] = []
  const start = new Date(selectedYear.value, 0, 1)
  const end = new Date(selectedYear.value, 11, 31)
  const dataMap = new Map((props.data ?? []).map((d) => [d.date, d.total_minutes]))

  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    const dateStr = d.toISOString().slice(0, 10)
    days.push({ date: dateStr, minutes: dataMap.get(dateStr) ?? 0 })
  }

  const firstDow = start.getDay()
  const padStart = (firstDow + 6) % 7
  for (let i = 0; i < padStart; i++) {
    days.unshift({ date: '', minutes: 0 })
  }

  const result: { date: string; minutes: number }[][] = []
  for (let i = 0; i < days.length; i += 7) {
    result.push(days.slice(i, i + 7))
  }
  return result
})

const maxMinutes = computed(() => {
  return Math.max(...(props.data ?? []).map((d) => d.total_minutes), 1)
})

function getColor(minutes: number) {
  if (minutes <= 0) return 'var(--color-border)'
  const ratio = minutes / maxMinutes.value
  if (ratio < 0.25) return 'var(--color-success-soft)'
  if (ratio < 0.5) return 'var(--color-success)'
  if (ratio < 0.75) return 'var(--color-primary)'
  return 'var(--color-primary-hover)'
}

const years = computed(() => {
  return [currentYear, currentYear - 1, currentYear - 2]
})
</script>

<template>
  <div class="heatmap-chart">
    <div class="heatmap-header">
      <h3 v-if="title" class="chart-title">
        {{ title }}
      </h3>
      <select
        v-if="showYearSelector"
        v-model="selectedYear"
        class="year-select"
        aria-label="Selecionar ano"
      >
        <option v-for="y in years" :key="y" :value="y">
          {{ y }}
        </option>
      </select>
    </div>
    <div v-if="!data?.length" class="chart-placeholder">Sem dados</div>
    <div v-else class="heatmap">
      <svg
        viewBox="0 0 730 110"
        preserveAspectRatio="xMidYMid meet"
        class="heatmap-svg"
        role="img"
        :aria-label="`Calendário de atividade de estudo em ${selectedYear}. Cada célula representa um dia; intensidade da cor indica minutos estudados.`"
      >
        <g v-for="(week, wi) in weeks" :key="wi" :transform="`translate(${wi * 14}, 0)`">
          <rect
            v-for="(day, di) in week"
            :key="`${wi}-${di}`"
            :x="di * 13"
            :y="0"
            width="11"
            height="11"
            :fill="day.date ? getColor(day.minutes) : 'var(--color-bg-soft)'"
            :data-date="day.date"
            :data-minutes="day.minutes"
          >
            <title v-if="day.date">{{ day.date }}: {{ day.minutes }} min</title>
          </rect>
        </g>
      </svg>
    </div>
  </div>
</template>

<style scoped>
.heatmap-chart {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
}
.heatmap-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-sm);
}
.chart-title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  margin: 0;
  color: var(--widget-title-color);
}
.year-select {
  min-height: var(--input-height-sm);
  font-size: var(--text-xs);
  font-weight: 500;
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
}
.chart-placeholder {
  min-height: var(--widget-chart-min-height-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}
.heatmap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior-x: contain;
}
.heatmap-svg {
  width: 100%;
  height: auto;
}
</style>
