<template>
  <v-container fluid class="gov-auth-shell">
    <v-row align="center" justify="center" class="fill-height">
      <v-col cols="12" sm="8" md="6" lg="4">
        <v-card elevation="6" class="gov-auth-card pa-4">
          <div class="gov-auth-card__content">
            <div class="text-center mb-6">
              <div class="gov-auth-brand mb-4">CNMIS</div>
              <v-card-title class="text-h5 text-center mb-2">
                Forgot Password
              </v-card-title>
              <v-card-subtitle class="text-center text-medium-emphasis">
                Enter your email address and we'll send you a reset link
              </v-card-subtitle>
            </div>

            <v-form ref="formRef" v-model="valid" @submit.prevent="handleForgotPassword">
              <v-text-field
                v-model="form.email"
                label="Email Address"
                type="email"
                prepend-inner-icon="mdi-email"
                :rules="emailRules"
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
                Send Reset Link
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
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useToast } from 'vue-toastification'

const router = useRouter()
const toast = useToast()

const formRef = ref()
const valid = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  email: ''
})

const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid'
]

async function handleForgotPassword() {
  const { valid: formValid } = await formRef.value.validate()
  if (!formValid) return

  loading.value = true
  error.value = ''
  success.value = ''

  try {
    await authApi.forgotPassword({ email: form.email })
    success.value = 'Password reset link has been sent to your email address.'
    toast.success('Reset link sent!')
  } catch (err: any) {
    const errorMessage = err.response?.data?.error?.message || 
                        err.response?.data?.message || 
                        'Failed to send reset link. Please try again.'
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
