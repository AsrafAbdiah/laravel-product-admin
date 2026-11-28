import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // hero.js only loads on the public home page (GSAP isn't needed anywhere else).
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/hero.js'],
            refresh: true,
            fonts: [
                // Body and UI text.
                bunny('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700],
                }),
                // Editorial display headlines.
                bunny('Instrument Serif', {
                    weights: [400],
                    styles: ['normal', 'italic'],
                }),
                // Index labels and small metadata.
                bunny('JetBrains Mono', {
                    weights: [400, 500],
                }),
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
