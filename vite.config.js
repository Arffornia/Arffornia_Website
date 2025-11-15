import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        // host: '0.0.0.0',
        host: 'localhost',
        port: 5173,
        strictPort: true,
        watch: {
            usePolling: true,
        },
    },
    plugins: [
        laravel({
            input: [ 'resources/css/app.css', 'public/css/components/notification.css', 'resources/js/app.js', 'resources/js/skinviewer.js', 'resources/js/shop.js', 'resources/js/vote.js', 'resources/js/notification.js' ],
            refresh: true,
        }),
    ],
});
