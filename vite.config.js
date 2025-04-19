import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        watch: {
            usePolling: true,
        },
    },
    plugins: [
        laravel({
            input: [ 'resources/css/app.css', 'resources/js/app.js', 'resources/js/skinviewer.js' ],
            refresh: true,
        }),
    ],
});
