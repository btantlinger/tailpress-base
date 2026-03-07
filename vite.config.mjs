import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
//import basicSsl from '@vitejs/plugin-basic-ssl'

export default defineConfig(({ command }) => {
    const isBuild = command === 'build';

    return {
        base: isBuild ? '/wp-content/themes/tailpress/dist/' : '/',
        server: {
   
            host: "0.0.0.0",
            port: 3000,
            strictPort: true,
            // Fix: Handle undefined DDEV_PRIMARY_URL
            origin: process.env.DDEV_PRIMARY_URL 
                ? `${process.env.DDEV_PRIMARY_URL.replace(/:\d+$/, "")}:3000`
                : "http://localhost:3000",
            cors: {
                origin: /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)?(?::\d+)?$/,
            },
        },
        assetsInclude: ['**/*.woff2', '**/*.woff'],
        build: {
            manifest: true,
            outDir: 'dist',
            rollupOptions: {
                input: [
                    'resources/js/app.js',
                    'resources/js/modules.js',
                    'resources/css/app.css',
                    'resources/css/editor-style.css'
                ],
                output: {
                    assetFileNames: (assetInfo) => {
                        // Keep font files with their original names
                        if (assetInfo.name && (assetInfo.name.endsWith('.woff2') || assetInfo.name.endsWith('.woff'))) {
                            return 'assets/fonts/[name][extname]'
                        }
                        return 'assets/[name]-[hash][extname]'
                    }
                }
            },
        },
        plugins: [
            tailwindcss(),
        ],
    }
});
