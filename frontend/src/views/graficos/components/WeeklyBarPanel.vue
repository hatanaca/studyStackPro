<script setup lang="ts">
import ChartSkeleton from '@/components/ui/ChartSkeleton.vue'
import BarChart from '@/components/charts/BarChart.vue'
import ChartPanel from './ChartPanel.vue'

defineProps<{
  data: { labels: string[]; values: number[]; scores: (number | null)[] }
  loading?: boolean
}>()
</script>

<template>
  <ChartPanel title="Resumo semanal" :loading="loading">
    <ChartSkeleton v-if="loading" height="280px" />
    <BarChart
      v-else
      :data="{ labels: data.labels, values: data.values, scores: data.scores.map(s => s ?? 0) }"
      orientation="vertical"
      value-unit="min"
      :show-data-labels="true"
      :gradient-fill="true"
    />
  </ChartPanel>
</template>
