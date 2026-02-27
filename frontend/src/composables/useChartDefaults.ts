/**
 * Config padrão Chart.js (tema, fontes)
 */
export function useChartDefaults() {
  return {
    fontFamily: "'Inter', system-ui, sans-serif",
    fontSize: 12,
    color: '#64748b',
    backgroundColor: 'rgba(255, 255, 255, 0.9)',
    borderColor: '#e2e8f0',
    gridColor: 'rgba(226, 232, 240, 0.5)',
    defaultColors: [
      '#3b82f6',
      '#10b981',
      '#f59e0b',
      '#ef4444',
      '#8b5cf6',
      '#06b6d4',
    ],
  }
}
