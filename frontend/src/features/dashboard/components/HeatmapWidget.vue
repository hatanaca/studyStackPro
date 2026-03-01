<script setup lang="ts">
import { ref, computed } from 'vue'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'

export interface HeatmapDay {
  date: string
  total_minutes: number
}

const props = defineProps<{
  data?: HeatmapDay[]
  year?: number
  loading?: boolean
}>()

const selectedYear = ref(props.year ?? new Date().getFullYear())

const weeks = computed(() => {
  const days: { date: string; minutes: number }[] = []
  const start = new Date(selectedYear.value, 0, 1)
  const end = new Date(selectedYear.value, 11, 31)
  const dataMap = new Map(
    (props.data ?? []).map((d) => [d.date, d.total_minutes])
  )

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
  const max = Math.max(...(props.data ?? []).map((d) => d.total_minutes), 1)
  return max
})

function getColor(minutes: number) {
  if (minutes <= 0) return '#ebedf0'
  const ratio = minutes / maxMinutes.value
  if (ratio < 0.25) return '#9be9a8'
  if (ratio < 0.5) return '#40c463'
  if (ratio < 0.75) return '#30a14e'
  return '#216e39'
}

const years = computed(() => {
  const y = new Date().getFullYear()
  return [y, y - 1, y - 2]
})
</script>

<template>
  <div class="heatmap-widget">
    <div class="header">
      <h3 class="title">
        Atividade
      </h3>
      <select
        v-model="selectedYear"
        class="year-select"
      >
        <option
          v-for="y in years"
          :key="y"
          :value="y"
        >
          {{ y }}
        </option>
      </select>
    </div>
    <div
      v-if="loading"
      class="heatmap-skeleton"
    >
      <SkeletonLoader
        v-for="i in 10"
        :key="i"
        height="0.75rem"
        class="skeleton-row"
      />
    </div>
    <div
      v-else
      class="heatmap"
    >
      <svg
        viewBox="0 0 730 110"
        preserveAspectRatio="xMidYMid meet"
        class="heatmap-svg"
      >
        <g
          v-for="(week, wi) in weeks"
          :key="wi"
          :transform="`translate(${wi * 14}, 0)`"
        >
          <rect
            v-for="(day, di) in week"
            :key="`${wi}-${di}`"
            :x="di * 13"
            :y="0"
            width="11"
            height="11"
            :fill="day.date ? getColor(day.minutes) : '#ebedf0'"
            :data-date="day.date"
            :data-minutes="day.minutes"
          >
            <title v-if="day.date">
              {{ day.date }}: {{ day.minutes }} min
            </title>
          </rect>
        </g>
      </svg>
    </div>
  </div>
</template>

<style scoped>
.heatmap-widget {
  background: #fff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}
.title {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}
.year-select {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  border: 1px solid #e2e8f0;
}
.heatmap {
  overflow-x: auto;
}
.heatmap-svg {
  width: 100%;
  min-width: 300px;
  height: auto;
}
.heatmap-skeleton {
  min-height: 120px;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  padding: 0.5rem 0;
}
.skeleton-row {
  width: 100%;
}
</style>
