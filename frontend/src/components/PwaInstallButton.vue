<template>
  <v-btn
    v-if="isInstallVisible"
    class="gov-install-btn mr-2"
  variant="elevated"
  color="warning"
  prepend-icon="mdi-download"
  :size="mdAndUp ? 'default' : 'small'"
  :disabled="installButtonDisabled"
  :loading="installPromptInstalling"
  @click="requestInstall"
>
    {{ installButtonLabel }}
  </v-btn>
</template>

<script setup lang="ts">
import { useDisplay } from 'vuetify'
import { usePwaInstall } from '@/composables/usePwaInstall'

const { mdAndUp } = useDisplay()
const {
  installButtonLabel,
  installButtonDisabled,
  installPromptInstalling,
  isInstallVisible,
  requestInstall,
} = usePwaInstall()
</script>

<style scoped>
:deep(.gov-install-btn) {
  font-weight: 700;
  letter-spacing: 0.02em;
}
</style>
