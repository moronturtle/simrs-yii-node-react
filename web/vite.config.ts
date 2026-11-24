import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

const yiiTarget = process.env.YII_PROXY_TARGET ?? 'http://localhost:8081'
const nodeTarget = process.env.NODE_PROXY_TARGET ?? 'http://localhost:3001'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    proxy: {
      '/api-yii': {
        target: yiiTarget,
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api-yii/, '/api'),
      },
      '/api-node': {
        target: nodeTarget,
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api-node/, '/api'),
      },
    },
  },
})
