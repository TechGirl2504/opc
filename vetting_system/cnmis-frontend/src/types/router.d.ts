import 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    layout?: 'auth' | 'admin' | 'officer'
    requiresAuth?: boolean
    requiresGuest?: boolean
    roles?: string[]
    permissions?: string[]
  }
}

