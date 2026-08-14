import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

// Laravel 8 predates laravel-vite-plugin, so we emit fixed filenames into
// public/build and reference them straight from the blade view.
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js/admin', import.meta.url)),
        },
    },
    // Laravel already serves public/ — vite must not treat it as its own static dir.
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: false,
        rollupOptions: {
            input: fileURLToPath(new URL('./resources/js/admin/main.js', import.meta.url)),
            output: {
                entryFileNames: 'admin.js',
                chunkFileNames: 'admin-[name].js',
                assetFileNames: 'admin.[ext]',
            },
        },
    },
})
