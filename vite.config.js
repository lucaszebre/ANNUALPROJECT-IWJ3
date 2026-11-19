import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Port of the dev server. Inside Docker it comes from compose.yml (FORWARD_VITE_PORT), so the
// port the browser uses is the same as the one the server listens on.
const vitePort = Number(process.env.FORWARD_VITE_PORT ?? 5173);
const appPort = Number(process.env.APP_PORT ?? 8000);
const allowedOrigins = [
    `http://localhost:${appPort}`,
    `http://127.0.0.1:${appPort}`,
];

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: vitePort,
        strictPort: true,
        origin: `http://localhost:${vitePort}`,
        cors: {
            origin: allowedOrigins,
        },
        hmr: { host: 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**'],
            usePolling: true
        },
    },
});
