<template>
  <v-app>
    <v-main>
      <slot />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
// Auth layout - minimal layout for authentication pages
</script>

<style scoped>
:deep(.v-main) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

