# Vue 3 SPA + PWA Setup Guide for CNMIS

## Quick Start Commands

### 1. Create Vue Project
```bash
cd /Users/m1/Sites/opc/vetting_system
npm create vue@latest cnmis-frontend
```

**When prompted, select:**
- ✅ Add TypeScript? → **No** (or Yes if you prefer)
- ✅ Add JSX Support? → **No**
- ✅ Add Vue Router? → **Yes**
- ✅ Add Pinia? → **Yes**
- ✅ Add Vitest? → **Yes** (for testing)
- ✅ Add Playwright? → **No** (optional)
- ✅ Add ESLint? → **Yes**

### 2. Install Dependencies
```bash
cd cnmis-frontend
npm install
```

### 3. Install Additional Packages
```bash
# HTTP Client
npm install axios

# UI Framework (Vuetify 3 - Recommended)
npm install vuetify@next @mdi/font

# PWA Support
npm install -D vite-plugin-pwa

# Utilities
npm install @vueuse/core date-fns vue-toastification

# Form Validation (optional but recommended)
npm install vee-validate yup @vee-validate/yup
```

### 4. Install Dev Dependencies
```bash
npm install -D @types/node
```

---

## Project Structure

After setup, your structure should be:

```
cnmis-frontend/
├── public/
│   └── icons/              # PWA icons
├── src/
│   ├── api/                # API service layer
│   │   ├── index.js        # Axios instance
│   │   ├── auth.js         # Auth endpoints
│   │   ├── applications.js
│   │   ├── vetting.js
│   │   ├── documents.js
│   │   ├── decisions.js
│   │   ├── users.js
│   │   ├── reports.js
│   │   └── notifications.js
│   ├── assets/
│   ├── components/
│   │   ├── common/         # Common components
│   │   ├── forms/          # Form components
│   │   └── tables/         # Table components
│   ├── composables/        # Vue composables
│   │   ├── useAuth.js
│   │   ├── useApi.js
│   │   └── useNotifications.js
│   ├── layouts/
│   │   ├── AdminLayout.vue
│   │   ├── OfficerLayout.vue
│   │   └── AuthLayout.vue
│   ├── router/
│   │   ├── index.js
│   │   └── guards.js       # Route guards
│   ├── stores/
│   │   ├── auth.js         # Auth store
│   │   ├── applications.js
│   │   └── notifications.js
│   ├── utils/
│   │   ├── constants.js
│   │   └── helpers.js
│   ├── views/
│   │   ├── auth/
│   │   │   ├── Login.vue
│   │   │   └── ForgotPassword.vue
│   │   ├── applications/
│   │   │   ├── List.vue
│   │   │   ├── Create.vue
│   │   │   └── Show.vue
│   │   ├── vetting/
│   │   ├── documents/
│   │   ├── reports/
│   │   └── admin/
│   ├── App.vue
│   └── main.js
├── .env
├── .env.example
├── vite.config.js
├── package.json
└── pwa.config.js
```

---

## Configuration Files

### 1. vite.config.js
```javascript
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'mask-icon.svg'],
      manifest: {
        name: 'CNMIS - Change of Name Management',
        short_name: 'CNMIS',
        description: 'Change of Name Management Information System',
        theme_color: '#1976d2',
        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}']
      }
    })
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  server: {
    port: 5173,
    host: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false
      }
    }
  }
})
```

### 2. .env
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=CNMIS
VITE_APP_ENV=local
```

### 3. .env.example
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=CNMIS
VITE_APP_ENV=local
```

---

## Core Files to Create

### 1. src/api/index.js (Axios Configuration)
```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  withCredentials: true, // Required for Sanctum SPA
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Unauthorized - clear token and redirect to login
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
```

### 2. src/api/auth.js
```javascript
import api from './index'

export const authApi = {
  login: (credentials) => api.post('/auth/login', credentials),
  logout: () => api.post('/auth/logout'),
  user: () => api.get('/auth/user'),
  refresh: () => api.post('/auth/refresh'),
  forgotPassword: (email) => api.post('/auth/forgot-password', { email }),
  resetPassword: (data) => api.post('/auth/reset-password', data),
  updatePassword: (data) => api.post('/auth/update-password', data)
}
```

### 3. src/stores/auth.js
```javascript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.roles?.includes('admin'))
  const userRole = computed(() => user.value?.roles?.[0])

  async function login(credentials) {
    loading.value = true
    try {
      const response = await authApi.login(credentials)
      if (response.data.success) {
        token.value = response.data.data.token
        user.value = response.data.data.user
        localStorage.setItem('auth_token', token.value)
        return response.data
      }
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      router.push('/login')
    }
  }

  async function fetchUser() {
    try {
      const response = await authApi.user()
      if (response.data.success) {
        user.value = response.data.data.user
      }
    } catch (error) {
      console.error('Fetch user error:', error)
      logout()
    }
  }

  function hasRole(role) {
    return user.value?.roles?.includes(role)
  }

  function hasAnyRole(roles) {
    return roles.some(role => hasRole(role))
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isAdmin,
    userRole,
    login,
    logout,
    fetchUser,
    hasRole,
    hasAnyRole
  }
})
```

### 4. src/router/guards.js
```javascript
import { useAuthStore } from '@/stores/auth'

export function setupRouterGuards(router) {
  router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()
    
    // Public routes
    const publicRoutes = ['/login', '/forgot-password', '/reset-password']
    const isPublicRoute = publicRoutes.includes(to.path)

    if (!authStore.isAuthenticated && !isPublicRoute) {
      // Redirect to login if not authenticated
      next('/login')
    } else if (authStore.isAuthenticated && isPublicRoute) {
      // Redirect to dashboard if already logged in
      next('/dashboard')
    } else if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      next('/login')
    } else if (to.meta.roles && !authStore.hasAnyRole(to.meta.roles)) {
      // Check role-based access
      next('/unauthorized')
    } else {
      next()
    }
  })
}
```

### 5. src/router/index.js
```javascript
import { createRouter, createWebHistory } from 'vue-router'
import { setupRouterGuards } from './guards'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { layout: 'auth' }
  },
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/applications',
    name: 'Applications',
    component: () => import('@/views/applications/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/applications/create',
    name: 'CreateApplication',
    component: () => import('@/views/applications/Create.vue'),
    meta: { 
      requiresAuth: true,
      roles: ['admin', 'opc_data_entry']
    }
  },
  {
    path: '/applications/:id',
    name: 'ApplicationDetail',
    component: () => import('@/views/applications/Show.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: () => import('@/views/Unauthorized.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

setupRouterGuards(router)

export default router
```

### 6. src/main.js
```javascript
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

// PWA
import { registerSW } from 'virtual:pwa-register'

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
  newestOnTop: true
})

// Register PWA
if ('serviceWorker' in navigator) {
  registerSW({
    immediate: true,
    onNeedRefresh() {
      // Show update notification
      console.log('New version available')
    },
    onOfflineReady() {
      console.log('App ready to work offline')
    }
  })
}

app.mount('#app')
```

---

## Laravel Sanctum SPA Configuration

### Update Laravel .env
```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
```

### Update config/sanctum.php
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

### Update config/cors.php
```php
'allowed_origins' => [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
],
'supports_credentials' => true,
```

---

## Next Steps

1. ✅ Run the setup commands above
2. ✅ Create the configuration files
3. ✅ Set up the API service layer
4. ✅ Create authentication views
5. ✅ Build role-based dashboards
6. ✅ Implement core features

The backend API is ready - you can start building the frontend! 🚀

