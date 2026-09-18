import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from 'vite-plugin-symfony';
import path from 'path';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        symfonyPlugin({
            viteDevServerHostname: 'localhost',
        }),
    ],
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: true,
        },
    },
    resolve: {
        alias: {
            '@/components': path.resolve(__dirname, './assets/js/Components'),
            '@/layouts': path.resolve(__dirname, './assets/js/Layouts'),
            '@/stores': path.resolve(__dirname, './assets/js/Stores'),
            '@/composables': path.resolve(__dirname, './assets/js/Composables'),
            '@/lib': path.resolve(__dirname, './assets/js/Lib'),
            '@/types': path.resolve(__dirname, './assets/js/Types'),
            '@': path.resolve(__dirname, './assets/js'),
        },
    },
    build: {
        rollupOptions: {
            input: {
                app: './assets/js/app.ts',
                ssr: './assets/js/ssr.ts',
            },
        },
    },
});
