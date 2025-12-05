import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css',
                 'resources/js/app.js',
                'resources/css/admin.css',
            'resources/css/dashboard.css',
            'resources/css/judging.css',
            'resources/css/results.css',
            'resources/css/submit-judge-app.css',
            'resources/css/submit.css',
            'resources/css/view-apps.css'],
            refresh: true,
        }),
    ],
});
