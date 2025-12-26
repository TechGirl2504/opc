<template>
  <v-snackbar
    v-model="showInstallPrompt"
    :timeout="-1"
    location="bottom"
    color="primary"
  >
    <div class="d-flex align-center">
      <v-icon class="mr-3">mdi-download</v-icon>
      <span>Install CNMIS app for a better experience</span>
    </div>
    <template #actions>
      <v-btn
        variant="text"
        @click="installPWA"
        :loading="installing"
      >
        Install
      </v-btn>
      <v-btn
        variant="text"
        @click="dismissPrompt"
      >
        Dismiss
      </v-btn>
    </template>
  </v-snackbar>

  <v-snackbar
    v-model="showUpdatePrompt"
    :timeout="-1"
    location="bottom"
    color="info"
  >
    <div class="d-flex align-center">
      <v-icon class="mr-3">mdi-update</v-icon>
      <span>A new version is available. Update now?</span>
    </div>
    <template #actions>
      <v-btn
        variant="text"
        @click="updatePWA"
        :loading="updating"
      >
        Update
      </v-btn>
      <v-btn
        variant="text"
        @click="dismissUpdate"
      >
        Later
      </v-btn>
    </template>
  </v-snackbar>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const showInstallPrompt = ref(false)
const showUpdatePrompt = ref(false)
const installing = ref(false)
const updating = ref(false)
let deferredPrompt: any = null
let updateSW: ((reload?: boolean) => Promise<void>) | null = null

onMounted(() => {
  // Check if app is already installed
  if (window.matchMedia('(display-mode: standalone)').matches) {
    // App is already installed
    return
  }

  // Listen for beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt = e
    // Show install prompt after a delay
    setTimeout(() => {
      const dismissed = localStorage.getItem('pwa-install-dismissed')
      if (!dismissed) {
        showInstallPrompt.value = true
      }
    }, 3000)
  })

  // Listen for app installed event
  window.addEventListener('appinstalled', () => {
    showInstallPrompt.value = false
    toast.success('App installed successfully!')
    deferredPrompt = null
  })

  // Register service worker update handler (only if vite-plugin-pwa is installed)
  if ('serviceWorker' in navigator) {
    import('virtual:pwa-register').then(({ registerSW }) => {
      updateSW = registerSW({
        immediate: true,
        onNeedRefresh() {
          showUpdatePrompt.value = true
        },
        onOfflineReady() {
          console.log('App ready to work offline')
        }
      })
    }).catch((error) => {
      // Plugin not installed or not available - silently fail
      console.log('PWA plugin not available:', error.message)
    })
  }
})

function installPWA() {
  if (!deferredPrompt) {
    toast.error('Install prompt not available')
    return
  }

  installing.value = true
  deferredPrompt.prompt()
  
  deferredPrompt.userChoice.then((choiceResult: any) => {
    if (choiceResult.outcome === 'accepted') {
      toast.success('App installation started')
    } else {
      toast.info('App installation cancelled')
    }
    deferredPrompt = null
    showInstallPrompt.value = false
    installing.value = false
  })
}

function dismissPrompt() {
  showInstallPrompt.value = false
  localStorage.setItem('pwa-install-dismissed', 'true')
}

function updatePWA() {
  if (!updateSW) {
    toast.error('Update not available')
    return
  }

  updating.value = true
  updateSW(true).then(() => {
    updating.value = false
    showUpdatePrompt.value = false
  }).catch((error) => {
    console.error('Update error:', error)
    toast.error('Failed to update app')
    updating.value = false
  })
}

function dismissUpdate() {
  showUpdatePrompt.value = false
}
</script>

