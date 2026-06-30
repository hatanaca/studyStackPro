<script setup lang="ts">
import ChartSkeleton from '@/components/ui/ChartSkeleton.vue'
import LineChart from '@/components/charts/LineChart.vue'
import ChartPanel from './ChartPanel.vue'

defineProps<{
  data: { labels: string[]; values: number[] }
  loading?: boolean
}>()
</script>

<template>
  <ChartPanel title="Tendência de estudo" :loading="loading">
    <ChartSkeleton v-if="loading" />
    <LineChart
      v-else
      :data="data"
      value-unit="minutes"
      :filled="true"
      :tall="true"
    />
  </ChartPanel>
</template>
