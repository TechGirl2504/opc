<template>
  <v-app>
    <div class="gov-auth-backdrop">
      <div class="gov-auth-backdrop__glow gov-auth-backdrop__glow--one" />
      <div class="gov-auth-backdrop__glow gov-auth-backdrop__glow--two" />
    </div>
    <v-main>
      <slot />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
// Auth layout - minimal layout for authentication pages
</script>

<style scoped>
:deep(.v-app) {
  background: transparent;
}

:deep(.v-main) {
  background:
    radial-gradient(circle at top left, rgba(18, 56, 95, 0.14), transparent 34%),
    radial-gradient(circle at bottom right, rgba(15, 118, 110, 0.12), transparent 30%),
    linear-gradient(180deg, #eaf0f6 0%, #f7fafc 100%);
}
</style>
