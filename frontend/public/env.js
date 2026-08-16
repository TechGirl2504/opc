(function () {
  // Default runtime config for local dev / static hosting.
  window.__ENV__ = window.__ENV__ || {}
  window.__ENV__.VITE_API_BASE_URL = window.__ENV__.VITE_API_BASE_URL || '/api/v1'
})()
