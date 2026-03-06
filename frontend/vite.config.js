import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
export default defineConfig({
    plugins: [vue()],
    test: {
        globals: true,
        environment: 'happy-dom',
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html'],
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url))
        }
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: function (id) {
                    if (id.includes('chart.js') || id.includes('vue-chartjs')) {
                        return 'chart-vendor';
                    }
                    if (id.includes('pusher-js') || id.includes('laravel-echo')) {
                        return 'ws-vendor';
                    }
                },
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        watch: {
            usePolling: true,
            interval: 1000,
        },
        proxy: {
            '/api': {
                target: process.env.PROXY_TARGET || 'http://localhost',
                changeOrigin: true
            },
            '/app': {
                target: process.env.PROXY_TARGET || 'http://localhost',
                ws: true,
                changeOrigin: true
            }
        }
    }
});
