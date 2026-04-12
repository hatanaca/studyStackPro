var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
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
                manualChunks: function (id) {
                    if (id.includes('node_modules/axios')) {
                        return 'http-vendor';
                    }
                    if (id.includes('pusher-js') || id.includes('laravel-echo')) {
                        return 'ws-vendor';
                    }
                    if (id.includes('/vue/') || id.includes('/vue-router/') || id.includes('/pinia/')) {
                        return 'vue-vendor';
                    }
                    if (id.includes('/@tanstack/vue-query/') || id.includes('/@tanstack/vue-virtual/')) {
                        return 'query-vendor';
                    }
                    if (id.includes('/primevue/') || id.includes('/@primeuix/') || id.includes('/primeicons/')) {
                        return 'primevue-vendor';
                    }
                    if (id.includes('/apexcharts/') || id.includes('/vue3-apexcharts/')) {
                        return 'charts-apex';
                    }
                    if (id.includes('/jspdf/')) {
                        return 'pdf-vendor';
                    }
                },
            },
        },
    },
    server: __assign(__assign({ host: '0.0.0.0', port: 5173 }, (process.env.VITE_DEV_POLLING === 'true'
        ? { watch: { usePolling: true, interval: 1000 } }
        : {})), { proxy: {
            '/api': {
                target: process.env.PROXY_TARGET || 'http://127.0.0.1:8000',
                changeOrigin: true
            },
            '/app': {
                target: process.env.PROXY_TARGET || 'http://127.0.0.1:8000',
                ws: true,
                changeOrigin: true
            }
        } }),
});
