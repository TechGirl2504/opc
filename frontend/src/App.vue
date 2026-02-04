<script setup lang="ts">
import { computed, onMounted } from 'vue'
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

onMounted(() => {
  // Fetch user if token exists
  if (authStore.isAuthenticated) {
    authStore.fetchUser()
  }
})
</script>

<template>
  <component :is="layout">
    <router-view />
  </component>
  <PwaInstallPrompt />
</template>

<style scoped></style>
