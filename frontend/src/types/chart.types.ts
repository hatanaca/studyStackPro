/**
 * Tipos para componentes de gráficos (ApexCharts e dados de API).
 */

export interface ChartDataset {
  label: string
  data: number[]
  backgroundColor?: string | string[]
  borderColor?: string | string[]
}

export interface TimeSeriesPoint {
  date: string
  total_minutes: number
  label?: string
}

export interface PieChartData {
  labels: string[]
  values: number[]
  colors?: string[]
}

export interface LineChartData {
  labels: string[]
  datasets: ChartDataset[]
}

export interface DateRange {
  start: string
  end: string
}

export interface KpiCard {
  label: string
  value: string | number
  color: string
  sparkline?: number[]
}

export interface TreemapDataPoint {
  x: string
  y: number
  color?: string
}

export interface RadarChartData {
  labels: string[]
  series: Array<{
    name: string
    data: number[]
  }>
}

export interface FunnelDataPoint {
  label: string
  value: number
}
