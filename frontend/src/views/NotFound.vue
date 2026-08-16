<template>
  <v-container class="gov-auth-shell">
    <v-row justify="center" align="center" style="min-height: 100vh">
      <v-col cols="12" sm="8" md="6">
        <v-card class="gov-auth-card pa-4">
          <div class="gov-auth-card__content text-center">
            <div class="gov-auth-brand mb-4">CNMIS</div>
            <v-card-title class="text-h5 text-center">404 - Page Not Found</v-card-title>
            <v-card-text class="text-center">
              <p>The page you're looking for doesn't exist.</p>
              <v-btn color="primary" @click="$router.push({ name: 'Dashboard' })" class="mt-4">
                Go to Application Dashboard
              </v-btn>
            </v-card-text>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
// 404 Not Found page
</script>
