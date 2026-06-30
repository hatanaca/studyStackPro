<script setup lang="ts">
import ChartSkeleton from '@/components/ui/ChartSkeleton.vue'
import HeatmapChart from '@/components/charts/HeatmapChart.vue'
import ChartPanel from './ChartPanel.vue'

defineProps<{
  data: { date: string; total_minutes: number }[]
  loading?: boolean
}>()
</script>

<template>
  <ChartPanel title="Calendário de atividade" :loading="loading">
    <ChartSkeleton v-if="loading" height="280px" />
    <HeatmapChart
      v-else
      :data="data"
      :show-year-selector="true"
    />
  </ChartPanel>
</template>
