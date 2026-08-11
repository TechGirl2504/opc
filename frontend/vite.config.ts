import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  base: '/cnmis/',
  /**
   * PWA + Workbox in `vite dev` is noisy because dev serves assets from memory,
   * and `dev-dist/` often has no matching build artifacts for `globPatterns`.
   *
   * If you need to test the real PWA behavior locally, prefer:
   * `npm run build && npm run preview`
   */
  plugins: [
    vue(),
    vueDevTools(),
    VitePWA({
      strategies: 'injectManifest',
      srcDir: 'src',
      filename: 'sw.ts',
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'mask-icon.svg'],
      manifest: {
        name: 'CNMIS - Change of Name Management',
        short_name: 'CNMIS',
        description: 'Change of Name Management Information System for OPC',
        theme_color: '#12385f',
        background_color: '#eef3f8',
        display: 'standalone',
        orientation: 'portrait',
        scope: '/cnmis/',
        start_url: '/cnmis/',
        icons: [
          {
            src: '/cnmis/pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any maskable'
          },
          {
            src: '/cnmis/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
      injectManifest: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
      },
      // Disable SW generation in dev to avoid Workbox glob warnings.
      devOptions: {
        enabled: false,
        type: 'module'
      }
    })
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  server: {
    port: 5173,
    host: true,
    strictPort: false,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
        ws: true
      },
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
        ws: true
      }
    }
  },
  preview: {
    // Allow testing on LAN (e.g. http://192.168.x.x:4174)
    host: true,
    port: 4174,
    strictPort: true
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          'ui-vendor': ['vuetify'],
          'utils-vendor': ['axios', '@vueuse/core', 'date-fns']
        }
      }
    }
  }
})
