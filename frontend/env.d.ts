/// <reference types="vite/client" />
/// <reference types="vite-plugin-pwa/client" />

declare module 'virtual:pwa-register' {
  export interface RegisterSWOptions {
    immediate?: boolean
    onNeedRefresh?: () => void
    onOfflineReady?: () => void
    onRegistered?: (registration: ServiceWorkerRegistration | undefined) => void
    onRegisterError?: (error: any) => void
  }

  export type RegisterSW = (options?: RegisterSWOptions) => (reload?: boolean) => Promise<void>
  
  export const registerSW: RegisterSW
}

declare global {
  interface Window {
    __ENV__?: {
      VITE_API_BASE_URL?: string
    }
  }
}

export {}
