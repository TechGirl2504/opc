<template>
  <v-snackbar
    v-model="installPromptVisible"
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
        @click="requestInstall"
        :loading="installPromptInstalling"
      >
        Install
      </v-btn>
      <v-btn
        variant="text"
        @click="dismissInstallPrompt"
      >
        Dismiss
      </v-btn>
    </template>
  </v-snackbar>

  <v-snackbar
    v-model="updatePromptVisible"
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
        :loading="updatePromptUpdating"
      >
        Update
      </v-btn>
      <v-btn
        variant="text"
        @click="dismissUpdatePrompt"
      >
        Later
      </v-btn>
    </template>
  </v-snackbar>
</template>

<script setup lang="ts">
import { usePwaInstall } from '@/composables/usePwaInstall'

const {
  dismissInstallPrompt,
  dismissUpdatePrompt,
  installPromptInstalling,
  installPromptVisible,
  requestInstall,
  updatePWA,
  updatePromptUpdating,
  updatePromptVisible,
} = usePwaInstall()
</script>
