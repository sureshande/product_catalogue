/// <reference types="vitest" />
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// In dev, /api is proxied to the Laravel backend, so the browser only ever talks to one origin.
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': process.env.VITE_API_PROXY_TARGET ?? 'http://localhost:8000',
    },
  },
  css: {
    preprocessorOptions: {
      // Bootstrap 5.3's Sass triggers deprecation warnings in newer Sass; they're Bootstrap's, not ours.
      scss: { quietDeps: true, silenceDeprecations: ['mixed-decls', 'color-functions', 'global-builtin', 'import'] },
    },
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    css: false,
    restoreMocks: true,
  },
})
