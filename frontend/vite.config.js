import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// eslint-disable-next-line no-undef
const cwd = process.cwd()

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, cwd, '')
  return {
    plugins: [react()],
    server: {
      proxy: {
        '/api': {
          target: env.VITE_BACKEND_URL,
          changeOrigin: true,
          bypass: (req) => {
            if (req.url.startsWith('/api-keys')) {
              return req.url;
            }
          },
        },
      },
    },
  }
})
