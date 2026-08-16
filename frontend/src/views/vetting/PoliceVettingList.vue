<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-4">
      <h1 class="text-h4">Police Vetting</h1>
    </div>

    <!-- Filters -->
    <v-card class="mb-4">
      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="filters.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="fetchApplications"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.status_id"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="fetchApplications"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.vetting_status"
              :items="vettingStatusOptions"
              label="Vetting Status"
              variant="outlined"
              density="compact"
              clearable
              @update:model-value="fetchApplications"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-btn
              color="primary"
              block
              @click="fetchApplications"
            >
              Filter
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Applications Table -->
    <v-card>
      <v-card-text>
        <v-data-table
          :headers="headers"
          :items="applications"
          :loading="loading"
          :items-per-page="pagination.per_page"
          :page="pagination.current_page"
          :server-items-length="pagination.total"
          @update:page="handlePageChange"
          @update:items-per-page="handlePerPageChange"
          item-value="id"
        >
          <template v-slot:item.application_number="{ item }">
            <router-link
              :to="{ name: 'ApplicationDetail', params: { id: item.id } }"
              class="text-decoration-none text-primary"
            >
              {{ item.application_number }}
            </router-link>
          </template>

          <template v-slot:item.status="{ item }">
            <v-chip
              :color="getStatusColor(item.status?.code)"
              size="small"
            >
              {{ item.status?.name }}
            </v-chip>
          </template>

          <template v-slot:item.police_vetting="{ item }">
            <v-chip
              v-if="item.police_vetting_completed_at"
              color="success"
              size="small"
            >
              Completed
            </v-chip>
            <v-chip
              v-else-if="item.assigned_police_officer?.id"
              color="info"
              size="small"
            >
              Assigned
            </v-chip>
            <v-chip v-else color="warning" size="small">
              Not Assigned
            </v-chip>
          </template>

          <template v-slot:item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>

          <template v-slot:item.actions="{ item }">
            <v-btn
              icon="mdi-eye"
              size="small"
              variant="text"
              @click="$router.push({ name: 'ApplicationDetail', params: { id: item.id } })"
            />
            <v-btn
              v-if="canVet(item)"
              icon="mdi-shield-check"
              size="small"
              variant="text"
              color="primary"
              @click="startVetting(item)"
            >
              <v-icon>mdi-shield-check</v-icon>
            </v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { applicationsApi, type Application } from '@/api/applications'
import { adminApi } from '@/api/admin'
import { useToast } from 'vue-toastification'
import { format } from 'date-fns'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)
const applications = ref<Application[]>([])
const statusOptions = ref<Array<{ title: string; value: number }>>([])
const vettingStatusOptions = ref([
  { title: 'Not Started', value: 'not_started' },
  { title: 'In Progress', value: 'in_progress' },
  { title: 'Completed', value: 'completed' }
])

const filters = reactive({
  search: '',
  status_id: null as number | null,
  vetting_status: null as string | null,
  date_from: '',
  date_to: ''
})

const pagination = reactive({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1
})

const headers = [
  { title: 'Application #', key: 'application_number', sortable: false },
  { title: 'Current Full Name', key: 'full_name' },
  { title: 'Requested Full Name', key: 'requested_name' },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'Police Vetting', key: 'police_vetting', sortable: false },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

function getStatusColor(statusCode: string) {
  const colors: Record<string, string> = {
    'pending': 'warning',
    'approved': 'success',
    'denied': 'error',
    'under_review': 'info',
    'police_vetting': 'primary',
    'nis_vetting': 'primary',
    'police_vetting_completed': 'success',
    'nis_vetting_completed': 'success'
  }
  return colors[statusCode] || 'grey'
}

function getVettingStatusColor(statusCode?: string) {
  const colors: Record<string, string> = {
    'pending': 'warning',
    'in_progress': 'info',
    'completed': 'success',
    'rejected': 'error'
  }
  return colors[statusCode || ''] || 'grey'
}

function formatDate(date: string) {
  if (!date) return ''
  return format(new Date(date), 'MMM dd, yyyy')
}

function canVet(item: Application): boolean {
  return item.allowed_actions?.includes('conduct_police_vetting') ?? false
}

function startVetting(item: Application) {
  router.push({ name: 'PoliceVetting', params: { id: item.id } })
}

async function fetchApplications() {
  loading.value = true
  try {
    const params: any = {
      page: pagination.current_page,
      per_page: pagination.per_page,
      assigned_police_officer_id: authStore.user?.id // Only show assigned applications
    }

    if (filters.search) params.search = filters.search
    if (filters.status_id) params.status_id = filters.status_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const response = await applicationsApi.list(params)
    if (response.data.success) {
      // Handle both paginated and non-paginated responses
      if (Array.isArray(response.data.data)) {
        applications.value = response.data.data
      } else if (response.data.data?.data) {
        applications.value = response.data.data.data
      } else {
        applications.value = []
      }
      pagination.current_page = response.data.meta?.current_page || 1
      pagination.total = response.data.meta?.total || 0
      pagination.last_page = response.data.meta?.last_page || 1
    }
  } catch (error: any) {
    toast.error('Failed to fetch applications')
    console.error('Error fetching applications:', error)
  } finally {
    loading.value = false
  }
}

function handlePageChange(page: number) {
  pagination.current_page = page
  fetchApplications()
}

function handlePerPageChange(perPage: number) {
  pagination.per_page = perPage
  pagination.current_page = 1
  fetchApplications()
}

async function loadStatusOptions() {
  try {
    const response = await adminApi.getApplicationStatuses()
    if (response.data.success) {
      statusOptions.value = (response.data.data || []).map((s: any) => ({
        title: s.name,
        value: s.id
      }))
    }
  } catch (err) {
    console.error('Failed to load status options:', err)
  }
}

onMounted(() => {
  fetchApplications()
  loadStatusOptions()
})
</script>
