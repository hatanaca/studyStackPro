import { visualizer } from 'rollup-plugin-visualizer'
import baseConfig from './vite.config'

export default {
  ...baseConfig,
  plugins: [...(baseConfig.plugins ?? []), visualizer({ open: false, gzipSize: true, filename: 'dist/stats.html' })],
}
