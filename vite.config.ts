import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    test: {
        environment: 'jsdom',
        setupFiles: ['resources/js/tests/setup.ts'],
        globals: true,
        include: ['resources/js/**/*.{test,spec}.{ts,js}'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html'],
            include: ['resources/js/**/*.{ts,vue}'],
            exclude: ['resources/js/types/**', 'resources/js/lib/**'],
        },
    },
});
