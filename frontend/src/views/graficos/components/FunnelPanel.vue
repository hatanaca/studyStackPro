<script setup lang="ts">
import ChartSkeleton from '@/components/ui/ChartSkeleton.vue'
import FunnelChart from '@/components/charts/FunnelChart.vue'
import ChartPanel from './ChartPanel.vue'
import type { FunnelDataPoint } from '@/types/chart.types'

defineProps<{
  data: FunnelDataPoint[]
  loading?: boolean
}>()
</script>

<template>
  <ChartPanel title="Funil de engajamento" :loading="loading">
    <ChartSkeleton v-if="loading" height="280px" />
    <FunnelChart
      v-else
      :data="data"
    />
  </ChartPanel>
</template>
