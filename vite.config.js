import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/stats.css',
                'resources/js/app.js',
                'resources/js/stats.js',
            ],
            refresh: true,
            fonts: [
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        chunkSizeWarningLimit: 700,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
