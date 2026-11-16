import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

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
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
