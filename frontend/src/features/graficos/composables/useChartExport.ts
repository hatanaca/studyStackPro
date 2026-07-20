import { ref } from 'vue'
import { useToast } from '@/composables/useToast'

export function useChartExport() {
  const isExporting = ref(false)
  const toast = useToast()

  async function exportChartPNG(
    chartRef: { exportTo?: (opts: { format: string; filename?: string }) => void } | null,
    filename = 'chart'
  ) {
    if (!chartRef?.exportTo) {
      toast.error('Não foi possível exportar este gráfico')
      return
    }
    isExporting.value = true
    try {
      chartRef.exportTo({ format: 'png', filename })
      toast.success('Gráfico exportado como PNG')
    } catch {
      toast.error('Erro ao exportar gráfico')
    } finally {
      isExporting.value = false
    }
  }

  function exportDataCSV(
    data: { headers: string[]; rows: (string | number)[][] },
    filename: string
  ) {
    isExporting.value = true
    try {
      const csvContent = [
        data.headers.join(','),
        ...data.rows.map((row) =>
          row
            .map((cell) => {
              const str = String(cell)
              return str.includes(',') || str.includes('"') || str.includes('\n')
                ? `"${str.replace(/"/g, '""')}"`
                : str
            })
            .join(',')
        ),
      ].join('\n')

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${filename}.csv`
      a.click()
      URL.revokeObjectURL(url)
      toast.success('Dados exportados como CSV')
    } catch {
      toast.error('Erro ao exportar dados')
    } finally {
      isExporting.value = false
    }
  }

  return { isExporting, exportChartPNG, exportDataCSV }
}
