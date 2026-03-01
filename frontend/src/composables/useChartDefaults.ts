import type { ChartOptions } from 'chart.js'

const FONT_FAMILY = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
const FONT_SIZE = 12
const GRID_COLOR = '#e2e8f0'
const TEXT_COLOR = '#64748b'

/**
 * Config padrão Chart.js para consistência visual
 */
export function useChartDefaults() {
  const defaultOptions: Partial<ChartOptions<'line' | 'bar' | 'doughnut'>> = {
    responsive: true,
    maintainAspectRatio: false,
    font: {
      family: FONT_FAMILY,
      size: FONT_SIZE,
    },
    color: TEXT_COLOR,
    scales: {
      x: {
        grid: { color: GRID_COLOR },
        ticks: { color: TEXT_COLOR },
      },
      y: {
        grid: { color: GRID_COLOR },
        ticks: { color: TEXT_COLOR },
      },
    },
    plugins: {
      legend: {
        labels: {
          font: { family: FONT_FAMILY, size: FONT_SIZE },
          color: TEXT_COLOR,
          padding: 16,
        },
      },
      tooltip: {
        backgroundColor: 'rgba(30, 41, 59, 0.95)',
        titleFont: { family: FONT_FAMILY },
        bodyFont: { family: FONT_FAMILY },
      },
    },
  }

  return {
    defaultOptions,
    fontFamily: FONT_FAMILY,
    fontSize: FONT_SIZE,
    gridColor: GRID_COLOR,
    textColor: TEXT_COLOR,
  }
}
