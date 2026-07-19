<script setup lang="ts">
import ChartSkeleton from '@/components/ui/ChartSkeleton.vue'
import RadarChart from '@/components/charts/RadarChart.vue'
import ChartPanel from './ChartPanel.vue'
import type { RadarChartData } from '@/types/chart.types'

defineProps<{
  data: RadarChartData
  loading?: boolean
}>()
</script>

<template>
  <ChartPanel title="Perfil de estudo" :loading="loading">
    <ChartSkeleton v-if="loading" height="280px" />
    <RadarChart v-else :series="data.series" :labels="data.labels" />
  </ChartPanel>
</template>
