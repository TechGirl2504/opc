import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

// Vuetify
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'

// Toast notifications
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

// PWA Service Worker - will be registered if vite-plugin-pwa is installed

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#1976d2',
          secondary: '#424242',
          accent: '#82B1FF',
          error: '#FF5252',
          info: '#2196F3',
          success: '#4CAF50',
          warning: '#FFC107'
        }
      }
    }
  }
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)
app.use(Toast, {
  transition: 'Vue-Toastification__bounce',
  maxToasts: 20,
  newestOnTop: true,
  position: 'top-right',
  timeout: 5000
})

// Register PWA Service Worker (only if vite-plugin-pwa is installed)
if ('serviceWorker' in navigator) {
  // Dynamically import to avoid errors if plugin is not installed
  import('virtual:pwa-register').then(({ registerSW }) => {
    const updateSW = registerSW({
      immediate: true,
      onNeedRefresh() {
        // Show notification when new version is available
        if (confirm('A new version of the app is available. Would you like to update now?')) {
          updateSW(true)
        }
      },
      onOfflineReady() {
        console.log('App ready to work offline')
      },
      onRegistered(registration: ServiceWorkerRegistration | undefined) {
        console.log('Service Worker registered:', registration)
      },
      onRegisterError(error: any) {
        console.error('Service Worker registration error:', error)
      }
    })
  }).catch((error) => {
    // Plugin not installed or not available in dev mode
    console.log('PWA plugin not available:', error.message)
  })
}

app.mount('#app')
