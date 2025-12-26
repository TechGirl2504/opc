<template>
  <v-container>
    <v-row justify="center" align="center" style="min-height: 100vh">
      <v-col cols="12" sm="8" md="6">
        <v-card>
          <v-card-title class="text-h5 text-center">404 - Page Not Found</v-card-title>
          <v-card-text class="text-center">
            <p>The page you're looking for doesn't exist.</p>
            <v-btn color="primary" @click="$router.push('/dashboard')" class="mt-4">
              Go to Dashboard
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
// 404 Not Found page
</script>

