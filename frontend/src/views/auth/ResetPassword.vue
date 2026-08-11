<template>
  <v-container fluid class="gov-auth-shell">
    <v-row align="center" justify="center" class="fill-height">
      <v-col cols="12" sm="8" md="6" lg="4">
        <v-card elevation="6" class="gov-auth-card pa-4">
          <div class="gov-auth-card__content">
            <div class="text-center mb-6">
              <div class="gov-auth-brand mb-4">CNMIS</div>
              <v-card-title class="text-h5 text-center mb-2">
                Reset Password
              </v-card-title>
              <v-card-subtitle class="text-center text-medium-emphasis">
                Enter your new password
              </v-card-subtitle>
            </div>

            <v-form ref="formRef" v-model="valid" @submit.prevent="handleResetPassword">
              <v-text-field
                v-model="form.email"
                label="Email Address"
                type="email"
                prepend-inner-icon="mdi-email"
                :rules="emailRules"
                required
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="form.token"
                label="Reset Token"
                prepend-inner-icon="mdi-key"
                :rules="tokenRules"
                required
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="form.password"
                label="New Password"
                type="password"
                prepend-inner-icon="mdi-lock"
                :rules="passwordRules"
                required
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="form.password_confirmation"
                label="Confirm Password"
                type="password"
                prepend-inner-icon="mdi-lock-check"
                :rules="confirmPasswordRules"
                required
                variant="outlined"
                class="mb-4"
              />

              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                :loading="loading"
                :disabled="!valid || loading"
                class="mb-3"
              >
                Reset Password
              </v-btn>

              <v-btn
                variant="tonal"
                block
                @click="$router.push({ name: 'Login' })"
              >
                Back to Login
              </v-btn>
            </v-form>

            <v-alert
              v-if="error"
              type="error"
              variant="tonal"
              class="mt-4"
              closable
              @click:close="error = ''"
            >
              {{ error }}
            </v-alert>

            <v-alert
              v-if="success"
              type="success"
              variant="tonal"
              class="mt-4"
            >
              {{ success }}
            </v-alert>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authApi } from '@/api/auth'
import { useToast } from 'vue-toastification'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const formRef = ref()
const valid = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  email: route.query.email as string || '',
  token: route.query.token as string || '',
  password: '',
  password_confirmation: ''
})

const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid'
]

const tokenRules = [
  (v: string) => !!v || 'Reset token is required'
]

const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => (v && v.length >= 8) || 'Password must be at least 8 characters'
]

const confirmPasswordRules = [
  (v: string) => !!v || 'Please confirm your password',
  (v: string) => v === form.password || 'Passwords do not match'
]

async function handleResetPassword() {
  const { valid: formValid } = await formRef.value.validate()
  if (!formValid) return

  loading.value = true
  error.value = ''
  success.value = ''

  try {
    await authApi.resetPassword({
      email: form.email,
      token: form.token,
      password: form.password,
      password_confirmation: form.password_confirmation
    })
    
    success.value = 'Password reset successful! Redirecting to login...'
    toast.success('Password reset successful!')
    
    setTimeout(() => {
      router.push({ name: 'Login' })
    }, 2000)
  } catch (err: any) {
    const errorMessage = err.response?.data?.error?.message || 
                        err.response?.data?.message || 
                        'Failed to reset password. Please try again.'
    error.value = errorMessage
    toast.error(errorMessage)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.fill-height {
  min-height: 100vh;
}
</style>
