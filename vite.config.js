import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
                'resources/css/admin.css',
                'resources/css/dashboard.css',
                'resources/css/judging.css',
                'resources/css/results.css',
                'resources/css/submit-judge-app.css',
                'resources/css/submit.css',
                'resources/css/theme.css',
                'resources/css/stats.css',
                'resources/css/profile.css',
                'resources/css/view-apps.css',
                'resources/css/search.css',
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});