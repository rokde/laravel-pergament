import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        lib: {
            entry: 'resources/js/pergament.js',
            name: 'Pergament',
            formats: ['iife'],
            fileName: () => 'pergament.js',
        },
        outDir: 'dist',
        emptyOutDir: false,
        minify: true,
        rollupOptions: {
            output: {
                entryFileNames: 'pergament.js',
            },
        },
    },
});
