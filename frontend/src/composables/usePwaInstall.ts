import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'

type BeforeInstallPromptEventLike = Event & {
  prompt: () => Promise<void> | void
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

const installPromptReady = ref(false)
const installPromptVisible = ref(false)
const installPromptAvailable = ref(false)
const installPromptInstalling = ref(false)
const updatePromptVisible = ref(false)
const updatePromptUpdating = ref(false)
const installPromptInstalled = ref(false)

let deferredPrompt: BeforeInstallPromptEventLike | null = null
let updateSW: ((reload?: boolean) => Promise<void>) | null = null
let listenersRegistered = false
let updateRegistered = false

function isStandaloneApp() {
  if (typeof window === 'undefined') {
    return false
  }

  return window.matchMedia('(display-mode: standalone)').matches
}

function refreshInstallAvailability() {
  if (typeof window === 'undefined') {
    installPromptAvailable.value = false
    return
  }

  installPromptAvailable.value =
    !installPromptInstalled.value &&
    !isStandaloneApp() &&
    'serviceWorker' in navigator
}

function registerPwaListeners() {
  if (typeof window === 'undefined' || listenersRegistered) {
    refreshInstallAvailability()
    return
  }

  listenersRegistered = true
  installPromptInstalled.value =
    isStandaloneApp() ||
    localStorage.getItem('pwa-installed') === 'true'
  refreshInstallAvailability()

  if (isStandaloneApp()) {
    return
  }

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferredPrompt = event as BeforeInstallPromptEventLike
    installPromptReady.value = true

    window.setTimeout(() => {
      if (deferredPrompt) {
        installPromptVisible.value = true
      }
    }, 3000)
  })

  window.addEventListener('appinstalled', () => {
    installPromptVisible.value = false
    installPromptReady.value = false
    installPromptInstalled.value = true
    localStorage.setItem('pwa-installed', 'true')
    deferredPrompt = null
    refreshInstallAvailability()
  })

  window.matchMedia('(display-mode: standalone)').addEventListener('change', refreshInstallAvailability)
}

async function registerServiceWorkerUpdates() {
  if (typeof window === 'undefined' || updateRegistered) {
    return
  }

  updateRegistered = true

  if (!('serviceWorker' in navigator)) {
    return
  }

  try {
    const { registerSW } = await import('virtual:pwa-register')
    updateSW = registerSW({
      immediate: true,
      onNeedRefresh() {
        updatePromptVisible.value = true
      },
      onOfflineReady() {
        console.log('App ready to work offline')
      },
    })
  } catch (error: any) {
    console.log('PWA plugin not available:', error?.message || error)
  }
}

export function usePwaInstall() {
  const toast = useToast()

  registerPwaListeners()
  registerServiceWorkerUpdates()

  const isInstallVisible = computed(() => installPromptAvailable.value)

  const installButtonLabel = computed(() => {
    if (isStandaloneApp()) {
      return 'Installed'
    }

    if (!installPromptReady.value) {
      return 'Preparing install'
    }

    if (installPromptReady.value) {
      return 'Install app'
    }

    return 'Install'
  })

  const installButtonDisabled = computed(() => {
    return installPromptInstalling.value || !installPromptReady.value
  })

  async function requestInstall() {
  if (isStandaloneApp()) {
    toast.info('CNMIS is already installed on this device')
    return
  }

    if (installPromptInstalled.value) {
      toast.info('CNMIS is already installed on this device')
      return
    }

    if (!deferredPrompt) {
      toast.info('Install prompt is still preparing. Stay on CNMIS for a moment.')
      return
    }

    installPromptInstalling.value = true

    try {
      await deferredPrompt.prompt()
      const choiceResult = await deferredPrompt.userChoice

      if (choiceResult.outcome === 'accepted') {
        toast.success('App installation started')
      } else {
        toast.info('App installation cancelled')
      }

      installPromptVisible.value = false
      installPromptReady.value = false
      deferredPrompt = null
    } catch (error) {
      console.error('Failed to start install prompt:', error)
      toast.error('Install prompt could not be opened')
    } finally {
      installPromptInstalling.value = false
    }
  }

  function dismissInstallPrompt() {
    installPromptVisible.value = false
  }

  async function updatePWA() {
    if (!updateSW) {
      toast.error('Update not available')
      return
    }

    updatePromptUpdating.value = true

    try {
      await updateSW(true)
      updatePromptVisible.value = false
    } catch (error) {
      console.error('Update error:', error)
      toast.error('Failed to update app')
    } finally {
      updatePromptUpdating.value = false
    }
  }

  function dismissUpdatePrompt() {
    updatePromptVisible.value = false
  }

  return {
    installButtonLabel,
    installButtonDisabled,
    installPromptAvailable,
    installPromptInstalling,
    installPromptReady,
    installPromptVisible,
    installPromptInstalled,
    isInstallVisible,
    requestInstall,
    dismissInstallPrompt,
    updatePWA,
    dismissUpdatePrompt,
    updatePromptUpdating,
    updatePromptVisible,
  }
}

export function initPwaInstall() {
  registerPwaListeners()
  void registerServiceWorkerUpdates()
}
