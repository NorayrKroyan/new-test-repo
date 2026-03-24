import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    origin: 'https://noro.voldhaul.com',
    hmr: {
      protocol: 'wss',
      host: 'noro.voldhaul.com',
      clientPort: 443,
    },
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/koolreport_assets': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
