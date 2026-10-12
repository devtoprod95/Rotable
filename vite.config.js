import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // 외부 기기(핸드폰 등)에서 dev 서버 에셋을 받을 수 있도록 CORS 허용.
        cors: true,
        // 핸드폰 접속 시: VITE_DEV_HOST=192.168.11.67 npm run dev
        // 지정하면 @vite 가 생성하는 에셋/HMR URL이 이 호스트를 가리킨다.
        ...(process.env.VITE_DEV_HOST
            ? { origin: `http://${process.env.VITE_DEV_HOST}:5173` }
            : {}),
        hmr: {
            host: process.env.VITE_DEV_HOST || process.env.VITE_HMR_HOST || 'localhost',
        },
        watch: {
            usePolling: true,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
