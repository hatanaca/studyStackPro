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
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { sentryVitePlugin } from '@sentry/vite-plugin';
var sentryPlugins = process.env.SENTRY_AUTH_TOKEN
    ? [sentryVitePlugin({
            org: "thiago-silva-hatanaka",
            project: "javascript-vue",
            authToken: process.env.SENTRY_AUTH_TOKEN,
            sourcemaps: { assets: "./dist/**" },
        })]
    : [];
var plugins = __spreadArray([vue()], sentryPlugins, true);
export default defineConfig({
    plugins: plugins,
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url))
        }
    },
    build: {
        sourcemap: true,
        rollupOptions: {
            output: {
                manualChunks: function (id) {
                    if (id.includes('node_modules/axios'))
                        return 'http-vendor';
                    if (id.includes('pusher-js') || id.includes('laravel-echo'))
                        return 'ws-vendor';
                    if (id.includes('/vue/') || id.includes('/vue-router/') || id.includes('/pinia/'))
                        return 'vue-vendor';
                    if (id.includes('/@tanstack/vue-query/') || id.includes('/@tanstack/vue-virtual/'))
                        return 'query-vendor';
                    if (id.includes('/primevue/') || id.includes('/@primeuix/') || id.includes('/primeicons/'))
                        return 'primevue-vendor';
                    if (id.includes('/apexcharts/') || id.includes('/vue3-apexcharts/'))
                        return 'charts-apex';
                    if (id.includes('/jspdf/'))
                        return 'pdf-vendor';
                    if (id.includes('/viewerjs/') || id.includes('/v-viewer/'))
                        return 'viewer-vendor';
                    if (id.includes('/@vue-flow/core/'))
                        return 'vue-flow-vendor';
                },
            },
        },
    },
    server: __assign(__assign({ host: '0.0.0.0', port: 5173, allowedHosts: true }, (process.env.VITE_DEV_POLLING === 'true'
        ? { watch: { usePolling: true, interval: 1000 } }
        : {})), { proxy: {
            '/sanctum': { target: process.env.PROXY_TARGET || 'http://127.0.0.1:8000', changeOrigin: true },
            '/api': { target: process.env.PROXY_TARGET || 'http://127.0.0.1:8000', changeOrigin: true },
            '/app': { target: process.env.PROXY_TARGET || 'http://127.0.0.1:8000', ws: true, changeOrigin: true },
        } }),
});
