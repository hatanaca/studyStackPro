import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules/axios')) {
            return 'http-vendor'
          }
          if (id.includes('pusher-js') || id.includes('laravel-echo')) {
            return 'ws-vendor'
          }
          if (id.includes('/vue/') || id.includes('/vue-router/') || id.includes('/pinia/')) {
            return 'vue-vendor'
          }
          if (id.includes('/@tanstack/vue-query/') || id.includes('/@tanstack/vue-virtual/')) {
            return 'query-vendor'
          }
          if (id.includes('/primevue/') || id.includes('/@primeuix/') || id.includes('/primeicons/')) {
            return 'primevue-vendor'
          }
          if (id.includes('/apexcharts/') || id.includes('/vue3-apexcharts/')) {
            return 'charts-apex'
          }
          if (id.includes('/jspdf/')) {
            return 'pdf-vendor'
          }
          if (id.includes('/viewerjs/') || id.includes('/v-viewer/')) {
            return 'viewer-vendor'
          }
          if (id.includes('/@vue-flow/core/')) {
            return 'vue-flow-vendor'
          }
        },
      },
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    // Polling constante deixa o dev muito lento (Windows, OneDrive, SSD). Só ative em Docker/WSL se precisar:
    // VITE_DEV_POLLING=true npm run dev
    ...(process.env.VITE_DEV_POLLING === 'true'
      ? { watch: { usePolling: true, interval: 1000 } }
      : {}),
    proxy: {
      '/sanctum': {
        target: process.env.PROXY_TARGET || 'http://127.0.0.1:8000',
        changeOrigin: true
      },
      '/api': {
        target: process.env.PROXY_TARGET || 'http://127.0.0.1:8000',
        changeOrigin: true
      },
      '/app': {
        target: process.env.PROXY_TARGET || 'http://127.0.0.1:8000',
        ws: true,
        changeOrigin: true
      }
    }
  },
})
