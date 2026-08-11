import axios, { type AxiosInstance, type AxiosResponse } from 'axios'

function getApiBaseUrl(): string {
  // Prefer runtime-injected config (Coolify/Docker) to avoid rebuilds.
  const runtime = (window as any).__ENV__?.VITE_API_BASE_URL as string | undefined
  if (runtime && runtime.trim().length > 0) return runtime

  const hostname = window.location.hostname
  const isLocalDevHost = hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '::1'

  // Fallback to Vite build-time env (local dev)
  const buildTime = import.meta.env.VITE_API_BASE_URL as string | undefined
  if (buildTime && buildTime.trim().length > 0) {
    if (isLocalDevHost && /^https?:\/\/localhost:8000\/api\/v1\/?$/i.test(buildTime.trim())) {
      return '/api/v1'
    }

    return buildTime
  }

  // Default to same-origin API so Vite's dev proxy handles local backend routing.
  return '/api/v1'
}

const api: AxiosInstance = axios.create({
  baseURL: getApiBaseUrl(),
  withCredentials: true, // Required for Sanctum SPA
  withXSRFToken: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

// Request interceptor
api.interceptors.request.use(
  (config) => config,
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error) => {
    if (error.response?.status === 401 || error.response?.status === 419) {
      // Unauthorized or CSRF/session mismatch - redirect to login
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
