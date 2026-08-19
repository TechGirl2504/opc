<template>
  <v-container fluid class="role-selection pa-4 pa-sm-8">
    <v-row justify="center">
      <v-col cols="12" md="9" lg="8" xl="6">
        <div class="text-center mb-8">
          <div class="gov-auth-brand mb-4">CNMIS</div>
          <h1 class="text-h4 mb-2">Choose your working role</h1>
          <p class="text-medium-emphasis">
            Select the role you want to perform. You can switch roles at any time.
          </p>
        </div>

        <v-row>
          <v-col v-for="role in roles" :key="role" cols="12" sm="6">
            <v-card
              class="role-card pa-5"
              :disabled="loading"
              elevation="2"
              @click="chooseRole(role)"
            >
              <v-avatar color="primary" size="52" class="mb-4">
                <v-icon>{{ roleIcon(role) }}</v-icon>
              </v-avatar>
              <div class="text-h6 mb-1">{{ roleLabel(role) }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ roleDescription(role) }}</div>
              <v-btn color="primary" variant="text" class="mt-4 px-0">
                Continue as {{ roleLabel(role) }}
                <v-icon end>mdi-arrow-right</v-icon>
              </v-btn>
            </v-card>
          </v-col>
        </v-row>

        <v-alert v-if="error" type="error" variant="tonal" class="mt-6">
          {{ error }}
        </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const loading = ref(false)
const error = ref('')
const roles = computed(() => authStore.user?.roles ?? [])

const labels: Record<string, string> = {
  admin: 'Administrator',
  opc_data_entry: 'Data Entry Officer',
  opc_approver: 'OPC Approver',
  police_officer: 'Police Officer',
  nis_officer: 'NIS Officer',
}

const descriptions: Record<string, string> = {
  admin: 'Manage users, configuration, assignments, reports, and system administration.',
  opc_data_entry: 'Create, update, upload documents, and forward applications.',
  opc_approver: 'Review applications and make approval or denial decisions.',
  police_officer: 'Conduct and complete police vetting assigned to you.',
  nis_officer: 'Conduct and complete NIS vetting assigned to you.',
}

function roleLabel(role: string) {
  return labels[role] ?? role.replace(/_/g, ' ').replace(/\b\w/g, (char: string) => char.toUpperCase())
}

function roleDescription(role: string) {
  return descriptions[role] ?? 'Access the functions assigned to this role.'
}

function roleIcon(role: string) {
  if (role === 'admin') return 'mdi-shield-account'
  if (role.includes('police')) return 'mdi-police-badge'
  if (role.includes('nis')) return 'mdi-security'
  if (role.includes('approver')) return 'mdi-clipboard-check'
  return 'mdi-file-edit'
}

async function chooseRole(role: string) {
  loading.value = true
  error.value = ''
  try {
    await authStore.selectRole(role)
    await router.push({ name: 'Dashboard' })
  } catch (err: any) {
    error.value = err.response?.data?.error?.message ?? 'Unable to select this role.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.role-selection {
  min-height: 100vh;
  background: #f8fafc;
}

.role-card {
  height: 100%;
  cursor: pointer;
  border: 1px solid rgba(18, 56, 95, 0.12);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.role-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(18, 56, 95, 0.14) !important;
}
</style>
