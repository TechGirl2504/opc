import axios, { type AxiosInstance, type AxiosResponse } from 'axios'

function getApiBaseUrl(): string {
  // Prefer runtime-injected config (Coolify/Docker) to avoid rebuilds.
  const runtime = (window as any).__ENV__?.VITE_API_BASE_URL as string | undefined
  if (runtime && runtime.trim().length > 0) return runtime

  // Fallback to Vite build-time env (local dev)
  const buildTime = import.meta.env.VITE_API_BASE_URL as string | undefined
  if (buildTime && buildTime.trim().length > 0) return buildTime

  return 'http://localhost:8000/api/v1'
}

const api: AxiosInstance = axios.create({
  baseURL: getApiBaseUrl(),
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
    if (token && config.headers) {
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
  (response: AxiosResponse) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Unauthorized - clear token and redirect to login
      localStorage.removeItem('auth_token')
      const basePath = import.meta.env.BASE_URL || '/'
      const loginPath = `${basePath}login`.replace(/\/+/g, '/') // normalize double slashes
      if (window.location.pathname !== loginPath) {
        window.location.href = loginPath
      }
    }
    return Promise.reject(error)
  }
)

export default api

