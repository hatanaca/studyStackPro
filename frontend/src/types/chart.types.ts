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
