import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Port of the dev server. Inside Docker it comes from compose.yml (FORWARD_VITE_PORT), so the
// port the browser uses is the same as the one the server listens on.
const vitePort = Number(process.env.FORWARD_VITE_PORT ?? 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Fonts of the CodeCampus design system: Manrope for titles,
            // Inter for text, JetBrains Mono for code.
            fonts: [
                bunny('Inter', { weights: [400, 600] }),
                bunny('Manrope', { weights: [700] }),
                bunny('JetBrains Mono', { weights: [400] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: vitePort,
        strictPort: true,
        origin: `http://localhost:${vitePort}`,
        hmr: { host: 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**'],
            usePolling: true
        },
    },
});
