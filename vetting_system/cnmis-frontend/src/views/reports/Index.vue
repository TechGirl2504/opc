<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Reports</h1>

        <v-tabs v-model="activeTab" bg-color="primary">
          <v-tab value="dashboard">Dashboard</v-tab>
          <v-tab value="applications">Applications</v-tab>
          <v-tab value="vetting">Vetting</v-tab>
          <v-tab value="audit" v-if="isAdmin">Audit Log</v-tab>
        </v-tabs>

        <v-window v-model="activeTab" @update:model-value="handleTabChange">
          <!-- Dashboard Tab -->
          <v-window-item value="dashboard">
            <v-card class="mt-4">
              <v-card-title>Dashboard Statistics</v-card-title>
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="3">
                    <v-card color="primary" variant="tonal">
                      <v-card-text>
                        <div class="text-h4">{{ dashboardData.summary?.total || 0 }}</div>
                        <div class="text-caption">Total Applications</div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-card color="warning" variant="tonal">
                      <v-card-text>
                        <div class="text-h4">{{ dashboardData.summary?.pending || 0 }}</div>
                        <div class="text-caption">Pending</div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-card color="success" variant="tonal">
                      <v-card-text>
                        <div class="text-h4">{{ dashboardData.summary?.approved || 0 }}</div>
                        <div class="text-caption">Approved</div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-card color="error" variant="tonal">
                      <v-card-text>
                        <div class="text-h4">{{ dashboardData.summary?.denied || 0 }}</div>
                        <div class="text-caption">Denied</div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>

                <v-row class="mt-4">
                  <v-col cols="12" md="6">
                    <v-card>
                      <v-card-title>Status Breakdown</v-card-title>
                      <v-card-text>
                        <v-list>
                          <v-list-item
                            v-for="status in dashboardData.status_breakdown"
                            :key="status.code"
                          >
                            <v-list-item-title>{{ status.name }}</v-list-item-title>
                            <template v-slot:append>
                              <v-chip size="small">{{ status.count }}</v-chip>
                            </template>
                          </v-list-item>
                        </v-list>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>

                <v-row class="mt-4">
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="filters.date_from"
                      label="Date From"
                      type="date"
                      variant="outlined"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="filters.date_to"
                      label="Date To"
                      type="date"
                      variant="outlined"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" @click="loadDashboard" :loading="loading">
                      Refresh
                    </v-btn>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-window-item>

          <!-- Applications Tab -->
          <v-window-item value="applications">
            <v-card class="mt-4">
              <v-card-title>Application Reports</v-card-title>
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="filters.status_id"
                      :items="statusOptions"
                      item-title="name"
                      item-value="id"
                      label="Status"
                      clearable
                      variant="outlined"
                      density="compact"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="filters.date_from"
                      label="Date From"
                      type="date"
                      variant="outlined"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="filters.date_to"
                      label="Date To"
                      type="date"
                      variant="outlined"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" @click="loadApplications" :loading="loading">
                      Filter
                    </v-btn>
                    <v-btn
                      variant="outlined"
                      class="ml-2"
                      @click="exportReport('applications')"
                      :loading="exporting"
                    >
                      Export
                    </v-btn>
                  </v-col>
                </v-row>

                <v-data-table
                  :headers="applicationHeaders"
                  :items="applications"
                  :loading="loading"
                  class="mt-4"
                >
                  <template #item.application_number="{ item }">
                    <router-link :to="{ name: 'ApplicationDetail', params: { id: item.id } }">
                      {{ item.application_number }}
                    </router-link>
                  </template>
                  <template #item.status="{ item }">
                    <v-chip size="small" :color="getStatusColor(item.status?.code)">
                      {{ item.status?.name }}
                    </v-chip>
                  </template>
                  <template #item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
          </v-window-item>

          <!-- Vetting Tab -->
          <v-window-item value="vetting">
            <v-card class="mt-4">
              <v-card-title>Vetting Reports</v-card-title>
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="filters.date_from"
                      label="Date From"
                      type="date"
                      variant="outlined"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="filters.date_to"
                      label="Date To"
                      type="date"
                      variant="outlined"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" @click="loadVetting" :loading="loading">
                      Filter
                    </v-btn>
                    <v-btn
                      variant="outlined"
                      class="ml-2"
                      @click="exportReport('vetting')"
                      :loading="exporting"
                    >
                      Export
                    </v-btn>
                  </v-col>
                </v-row>

                <v-data-table
                  :headers="vettingHeaders"
                  :items="vettingRecords"
                  :loading="loading"
                  class="mt-4"
                  :items-per-page="50"
                >
                  <template #item.application_number="{ item }">
                    <router-link :to="{ name: 'ApplicationDetail', params: { id: item.application?.id } }">
                      {{ item.application?.application_number || '-' }}
                    </router-link>
                  </template>
                  <template #item.applicant_name="{ item }">
                    {{ item.application?.full_name || '-' }}
                  </template>
                  <template #item.vetting_type="{ item }">
                    {{ item.vettingType?.name || '-' }}
                  </template>
                  <template #item.status="{ item }">
                    <v-chip size="small" :color="getStatusColor(item.status?.code)">
                      {{ item.status?.name || '-' }}
                    </v-chip>
                  </template>
                  <template #item.conducted_by="{ item }">
                    {{ item.conductedBy?.username || '-' }}
                  </template>
                  <template #item.completed_at="{ item }">
                    {{ item.completed_at ? formatDate(item.completed_at) : '-' }}
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
          </v-window-item>

          <!-- Audit Tab -->
          <v-window-item value="audit" v-if="isAdmin">
            <v-card class="mt-4">
              <v-card-title>Audit Log</v-card-title>
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="filters.date_from"
                      label="Date From"
                      type="date"
                      variant="outlined"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="filters.date_to"
                      label="Date To"
                      type="date"
                      variant="outlined"
                      density="compact"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" @click="loadAudit" :loading="loading">
                      Filter
                    </v-btn>
                    <v-btn
                      variant="outlined"
                      class="ml-2"
                      @click="exportReport('audit')"
                      :loading="exporting"
                    >
                      Export
                    </v-btn>
                  </v-col>
                </v-row>

                <v-data-table
                  :headers="auditHeaders"
                  :items="auditLogs"
                  :loading="loading"
                  class="mt-4"
                  :items-per-page="50"
                >
                  <template #item.action="{ item }">
                    <v-chip size="small">{{ item.action || '-' }}</v-chip>
                  </template>
                  <template #item.user="{ item }">
                    {{ item.user?.username || item.user_id || '-' }}
                  </template>
                  <template #item.description="{ item }">
                    {{ item.description || '-' }}
                  </template>
                  <template #item.ip_address="{ item }">
                    {{ item.ip_address || '-' }}
                  </template>
                  <template #item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
          </v-window-item>
        </v-window>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { reportsApi, type ReportParams } from '@/api/reports'
import { adminApi } from '@/api/admin'
import { format } from 'date-fns'

const authStore = useAuthStore()
const toast = useToast()

const isAdmin = computed(() => authStore.isAdmin)
const activeTab = ref('dashboard')
const loading = ref(false)
const exporting = ref(false)

const dashboardData = reactive({
  summary: {
    total: 0,
    pending: 0,
    approved: 0,
    denied: 0
  },
  status_breakdown: [] as any[]
})

const applications = ref<any[]>([])
const vettingRecords = ref<any[]>([])
const auditLogs = ref<any[]>([])
const statusOptions = ref<any[]>([])

const filters = reactive<ReportParams>({
  date_from: '',
  date_to: '',
  status_id: undefined
})

const applicationHeaders = [
  { title: 'Application #', key: 'application_number' },
  { title: 'Full Name', key: 'full_name' },
  { title: 'Status', key: 'status' },
  { title: 'Created At', key: 'created_at' }
]

const vettingHeaders = [
  { title: 'Application #', key: 'application.application_number' },
  { title: 'Vetting Type', key: 'vetting_type' },
  { title: 'Status', key: 'status' },
  { title: 'Conducted By', key: 'conducted_by.username' },
  { title: 'Completed At', key: 'completed_at' }
]

const auditHeaders = [
  { title: 'Action', key: 'action' },
  { title: 'User', key: 'user.username' },
  { title: 'Description', key: 'description' },
  { title: 'IP Address', key: 'ip_address' },
  { title: 'Created At', key: 'created_at' }
]

function formatDate(date: string) {
  if (!date) return '-'
  return format(new Date(date), 'MMM dd, yyyy HH:mm')
}

function getStatusColor(code?: string) {
  const colors: Record<string, string> = {
    pending: 'warning',
    approved: 'success',
    denied: 'error',
    'in_progress': 'info',
    completed: 'success'
  }
  return colors[code || ''] || 'default'
}

async function loadDashboard() {
  loading.value = true
  try {
    const response = await reportsApi.dashboard(filters)
    if (response.data.success) {
      Object.assign(dashboardData, response.data.data)
    }
  } catch (err: any) {
    toast.error('Failed to load dashboard data')
    console.error(err)
  } finally {
    loading.value = false
  }
}

async function loadApplications() {
  loading.value = true
  try {
    const response = await reportsApi.applications(filters)
    console.log('Applications response:', response.data)
    if (response.data.success) {
      // Backend returns data as array directly, not nested
      applications.value = response.data.data || []
      if (applications.value.length === 0) {
        console.log('No applications found with current filters')
      }
    } else {
      console.error('API returned success=false:', response.data)
      toast.error(response.data.error?.message || 'Failed to load applications')
    }
  } catch (err: any) {
    console.error('Error loading applications:', err)
    toast.error(err.response?.data?.error?.message || 'Failed to load applications')
  } finally {
    loading.value = false
  }
}

async function loadVetting() {
  loading.value = true
  try {
    const response = await reportsApi.vetting(filters)
    console.log('Vetting response:', response.data)
    if (response.data.success) {
      // Backend now returns records directly in data array
      vettingRecords.value = response.data.data || []
      if (vettingRecords.value.length === 0) {
        console.log('No vetting records found with current filters')
      }
    } else {
      console.error('API returned success=false:', response.data)
      toast.error(response.data.error?.message || 'Failed to load vetting records')
    }
  } catch (err: any) {
    console.error('Error loading vetting:', err)
    toast.error(err.response?.data?.error?.message || 'Failed to load vetting records')
  } finally {
    loading.value = false
  }
}

async function loadAudit() {
  if (!isAdmin.value) return
  loading.value = true
  try {
    const response = await reportsApi.audit(filters)
    console.log('Audit response:', response.data)
    if (response.data.success) {
      // Backend returns data as array directly (paginated items)
      auditLogs.value = response.data.data || []
      if (auditLogs.value.length === 0) {
        console.log('No audit logs found with current filters')
      }
    } else {
      console.error('API returned success=false:', response.data)
      toast.error(response.data.error?.message || 'Failed to load audit logs')
    }
  } catch (err: any) {
    console.error('Error loading audit:', err)
    toast.error(err.response?.data?.error?.message || 'Failed to load audit logs')
  } finally {
    loading.value = false
  }
}

async function exportReport(type: string) {
  exporting.value = true
  try {
    const response = await reportsApi.export({ ...filters, type } as any)
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `report-${type}-${Date.now()}.xlsx`
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Report exported successfully')
  } catch (err: any) {
    toast.error('Failed to export report')
    console.error(err)
  } finally {
    exporting.value = false
  }
}

async function loadStatusOptions() {
  try {
    const response = await adminApi.getApplicationStatuses()
    if (response.data.success) {
      statusOptions.value = response.data.data || []
    }
  } catch (err) {
    console.error('Failed to load status options:', err)
  }
}

function handleTabChange(tab: string) {
  // Auto-load data when switching tabs
  if (tab === 'applications') {
    loadApplications()
  } else if (tab === 'vetting') {
    loadVetting()
  } else if (tab === 'audit' && isAdmin.value) {
    loadAudit()
  }
}

onMounted(async () => {
  await Promise.all([
    loadDashboard(),
    loadStatusOptions()
  ])
})
</script>
