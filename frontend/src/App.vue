<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import OfficerLayout from '@/layouts/OfficerLayout.vue'
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue'

const route = useRoute()
const authStore = useAuthStore()

const layout = computed(() => {
  if (route.meta.layout === 'auth') {
    return AuthLayout
  }
  
  if (authStore.isAdmin) {
    return AdminLayout
  }
  
  return OfficerLayout
})
</script>

<template>
  <div>
    <component :is="layout">
      <router-view />
    </component>
    <PwaInstallPrompt />
  </div>
</template>

<style scoped></style>
