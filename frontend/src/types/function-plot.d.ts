declare module 'function-plot' {
  interface FunctionPlotDatum {
    fn?: string
    color?: string
    range?: [number, number]
  }

  interface FunctionPlotOptions {
    target: HTMLElement | string
    width?: number
    height?: number
    xAxis?: { domain?: [number, number]; label?: string }
    yAxis?: { domain?: [number, number]; label?: string }
    grid?: boolean
    tip?: { xLine?: boolean; yLine?: boolean }
    data: FunctionPlotDatum[]
  }

  function functionPlot(options: FunctionPlotOptions): void

  export default functionPlot
}
