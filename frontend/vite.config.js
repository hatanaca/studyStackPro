var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { visualizer } from 'rollup-plugin-visualizer';
export default defineConfig(function (_a) {
    var mode = _a.mode;
    return ({
        plugins: __spreadArray([
            vue()
        ], (mode === 'analyze' ? [visualizer({ open: false, gzipSize: true, filename: 'dist/stats.html' })] : []), true),
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
});
