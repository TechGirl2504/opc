(function () {
  // Default runtime config for local dev / static hosting.
  // In Docker/Coolify, this file is generated at container startup.
  window.__ENV__ = window.__ENV__ || {}
  window.__ENV__.VITE_API_BASE_URL = window.__ENV__.VITE_API_BASE_URL || '/api/v1'
})()
