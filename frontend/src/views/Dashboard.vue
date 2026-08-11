<template>
  <div class="gov-page">
    <v-sheet class="gov-hero">
      <div class="gov-hero__inner">
        <div>
          <div class="gov-hero__eyebrow">Operational overview</div>
          <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">Dashboard</h1>
          <div class="text-body-2 text-medium-emphasis">
            Monitor application volumes, review workload, and recent activity.
          </div>
        </div>
      </div>
    </v-sheet>

    <v-row>
      <!-- Statistics Cards -->
      <v-col cols="12" sm="6" md="3" v-for="stat in statistics" :key="stat.title">
        <v-card class="gov-card h-100" elevation="2">
          <v-card-text>
            <div class="d-flex align-center">
              <v-avatar :color="stat.color" size="56" class="mr-4">
                <v-icon :icon="stat.icon" size="32" color="white" />
              </v-avatar>
              <div>
                <div class="text-h6">{{ stat.value }}</div>
                <div class="text-caption text-medium-emphasis">{{ stat.title }}</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Applications -->
    <v-row class="mt-4">
      <v-col cols="12">
        <v-card class="gov-card" elevation="2">
          <v-card-title class="gov-card__title">
            <div>
              <div class="text-h6">Recent Applications</div>
              <div class="text-caption text-medium-emphasis">Latest submissions and status updates</div>
            </div>
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="recentApplications"
              :loading="loading"
              item-value="id"
            >
              <template v-slot:item.status="{ item }">
                <v-chip
                  :color="getStatusColor(item.status?.code)"
                  size="small"
                >
                  {{ item.status?.name }}
                </v-chip>
              </template>
              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="$router.push({ name: 'ApplicationDetail', params: { id: item.id } })"
                />
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { applicationsApi, type Application } from '@/api/applications'
import { reportsApi } from '@/api/reports'

const authStore = useAuthStore()
const loading = ref(false)
const recentApplications = ref<Application[]>([])
const dashboardData = ref<any>(null)

const statistics = computed(() => {
  if (!dashboardData.value) {
    return [
      { title: 'Total Applications', value: 0, icon: 'mdi-file-document', color: 'primary' },
      { title: 'Pending', value: 0, icon: 'mdi-clock', color: 'warning' },
      { title: 'Approved', value: 0, icon: 'mdi-check-circle', color: 'success' },
      { title: 'Denied', value: 0, icon: 'mdi-close-circle', color: 'error' }
    ]
  }

  const summary = dashboardData.value.summary || {}
  
  return [
    { 
      title: 'Total Applications', 
      value: dashboardData.value.total_applications || summary.total || 0, 
      icon: 'mdi-file-document', 
      color: 'primary' 
    },
    { 
      title: 'Pending', 
      value: dashboardData.value.pending_applications || summary.pending || 0, 
      icon: 'mdi-clock', 
      color: 'warning' 
    },
    { 
      title: 'Approved', 
      value: dashboardData.value.approved_applications || summary.approved || 0, 
      icon: 'mdi-check-circle', 
      color: 'success' 
    },
    { 
      title: 'Denied', 
      value: dashboardData.value.denied_applications || summary.denied || 0, 
      icon: 'mdi-close-circle', 
      color: 'error' 
    }
  ]
})

const headers = [
  { title: 'Application Number', key: 'application_number' },
  { title: 'Full Name', key: 'full_name' },
  { title: 'Requested Name', key: 'requested_name' },
  { title: 'Status', key: 'status' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

function getStatusColor(statusCode?: string) {
  const colors: Record<string, string> = {
    'pending': 'warning',
    'approved': 'success',
    'denied': 'error',
    'under_review': 'info',
    'police_vetting': 'primary',
    'nis_vetting': 'primary'
  }
  return colors[statusCode || ''] || 'grey'
}

async function fetchDashboardData() {
  loading.value = true
  try {
    const response = await reportsApi.dashboard()
    if (response.data.success) {
      dashboardData.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching dashboard data:', error)
  } finally {
    loading.value = false
  }
}

async function fetchRecentApplications() {
  try {
    const response = await applicationsApi.list({ per_page: 10 })
    if (response.data.success) {
      // Handle paginated response structure
      if (response.data.data?.data) {
        recentApplications.value = response.data.data.data
      } else if (Array.isArray(response.data.data)) {
        recentApplications.value = response.data.data
      } else {
        recentApplications.value = []
      }
    }
  } catch (error) {
    console.error('Error fetching recent applications:', error)
  }
}

onMounted(() => {
  fetchDashboardData()
  fetchRecentApplications()
})
</script>

<style scoped>
.h-100 {
  height: 100%;
}
</style>
