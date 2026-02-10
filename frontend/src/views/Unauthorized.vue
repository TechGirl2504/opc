<template>
  <v-container>
    <v-row justify="center" align="center" style="min-height: 100vh">
      <v-col cols="12" sm="8" md="6">
        <v-card>
          <v-card-title class="text-h5 text-center text-error">Unauthorized</v-card-title>
          <v-card-text class="text-center">
            <p>You don't have permission to access this page.</p>
            <v-btn color="primary" @click="$router.push({ name: 'Dashboard' })" class="mt-4">
              Go to Dashboard
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
// Unauthorized page
</script>

