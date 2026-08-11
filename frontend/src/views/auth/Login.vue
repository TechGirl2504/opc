<template>
  <v-container fluid class="gov-auth-shell pa-4 pa-sm-8">
    <v-row align="center" justify="center" class="fill-height">
      <v-col cols="12" sm="9" md="6" lg="4" xl="3">
        <v-card elevation="6" class="gov-auth-card pa-4 pa-sm-6" max-width="520">
          <div class="gov-auth-card__content">
            <div class="text-center mb-6">
              <div class="gov-auth-brand mb-4">CNMIS</div>
              <v-card-title class="text-h5 text-sm-h4 text-center mb-2">
                Login
              </v-card-title>
              <v-card-subtitle class="text-center text-medium-emphasis">
                Change of Name Management Information System
              </v-card-subtitle>
            </div>

            <v-form ref="formRef" v-model="valid" @submit.prevent="handleLogin">
              <v-text-field
                v-model="form.username"
                label="Username"
                prepend-inner-icon="mdi-account"
                :rules="usernameRules"
                required
                variant="outlined"
                density="comfortable"
                autocomplete="username"
                class="mb-3"
              />

              <v-text-field
                v-model="form.password"
                label="Password"
                type="password"
                prepend-inner-icon="mdi-lock"
                :rules="passwordRules"
                required
                variant="outlined"
                density="comfortable"
                autocomplete="current-password"
                class="mb-3"
                @keyup.enter="handleLogin"
              />

              <div class="d-flex flex-wrap justify-space-between align-center gap-2 mb-4">
                <v-checkbox
                  v-model="rememberMe"
                  label="Remember me"
                  hide-details
                  density="compact"
                />
                <router-link
                  to="/forgot-password"
                  class="text-decoration-none text-primary"
                >
                  Forgot Password?
                </router-link>
              </div>

              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                :loading="loading"
                :disabled="!valid || loading"
              >
                Login
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
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const formRef = ref()
const valid = ref(false)
const loading = ref(false)
const rememberMe = ref(false)
const error = ref('')

const form = reactive({
  username: '',
  password: ''
})

const usernameRules = [
  (v: string) => !!v || 'Username is required',
  (v: string) => (v && v.length >= 3) || 'Username must be at least 3 characters'
]

const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => (v && v.length >= 6) || 'Password must be at least 6 characters'
]

async function handleLogin() {
  const { valid: formValid } = await formRef.value.validate()
  if (!formValid) return

  loading.value = true
  error.value = ''

  try {
    await authStore.login({
      username: form.username,
      password: form.password
    })

    toast.success('Login successful!')

    // Redirect based on user role
    const role = authStore.userRole
    if (role === 'admin') {
      router.push({ name: 'Dashboard' })
    } else {
      router.push({ name: 'Dashboard' })
    }
  } catch (err: any) {
    console.error('Login error:', err)
    console.error('Error response:', err.response)
    console.error('Error data:', err.response?.data)

    // Handle validation errors (422) - Laravel validation
    if (err.response?.status === 422) {
      const validationErrors = err.response.data?.errors
      if (validationErrors?.username) {
        error.value = validationErrors.username[0]
      } else if (err.response.data?.message) {
        error.value = err.response.data.message
      } else {
        error.value = 'Invalid credentials. Please check your username and password.'
      }
    }
    // Handle network errors
    else if (!err.response) {
      error.value = 'Network error. Please check if the API server is running.'
      console.error('Network error - API might be down or CORS issue')
    }
    // Handle other errors
    else {
      const errorMessage = err.response?.data?.error?.message ||
                          err.response?.data?.message ||
                          err.message ||
                          'Login failed. Please check your credentials.'
      error.value = errorMessage
    }

    toast.error(error.value)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.fill-height {
  min-height: 100vh;
}

.gap-2 {
  gap: 8px;
}
</style>
