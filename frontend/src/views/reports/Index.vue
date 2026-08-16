<template>
  <v-container fluid class="reports-page">
    <v-row>
      <v-col cols="12">
        <v-sheet class="reports-hero" rounded="xl">
          <div class="reports-hero__inner">
            <div>
              <div class="reports-hero__eyebrow">System Reports</div>
              <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">Reports</h1>
              <div class="text-body-2 text-medium-emphasis">
                Search, filter, export, and review workflow records from one place.
              </div>
            </div>
            <div class="d-flex ga-3 flex-wrap">
              <v-btn
                color="primary"
                variant="flat"
                prepend-icon="mdi-refresh"
                :loading="dashboardLoading"
                @click="loadDashboard"
              >
                Refresh Dashboard
              </v-btn>
            </div>
          </div>
        </v-sheet>
      </v-col>

      <v-col cols="12">
        <v-tabs v-model="activeTab" bg-color="primary" class="reports-tabs">
          <v-tab value="dashboard">Application Dashboard</v-tab>
          <v-tab value="applications">Applications</v-tab>
          <v-tab value="vetting">Vetting</v-tab>
          <v-tab v-if="canViewAuditLogs" value="audit">Audit Log</v-tab>
        </v-tabs>
      </v-col>

      <v-col cols="12">
        <v-window v-model="activeTab" @update:model-value="handleTabChange">
          <v-window-item value="dashboard">
            <v-card class="mt-4" rounded="xl" elevation="2">
              <v-card-title class="text-h5">Dashboard Statistics</v-card-title>
              <v-card-text>
                <v-row>
                  <v-col
                    v-for="card in dashboardCards"
                    :key="card.label"
                    cols="12"
                    sm="6"
                    lg="3"
                  >
                    <v-card :color="card.color" variant="tonal" rounded="lg" class="h-100">
                      <v-card-text class="d-flex align-center justify-space-between">
                        <div>
                          <div class="text-h4 font-weight-bold">{{ card.value }}</div>
                          <div class="text-body-2">{{ card.label }}</div>
                        </div>
                        <v-icon :icon="card.icon" size="40" />
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>

                <v-row class="mt-4">
                  <v-col cols="12" lg="7">
                    <v-card rounded="lg" variant="outlined" class="h-100">
                      <v-card-title>Status Breakdown</v-card-title>
                      <v-card-text>
                        <v-list density="compact" class="py-0">
                          <v-list-item
                            v-for="status in dashboardData.status_breakdown"
                            :key="status.code"
                            class="px-0"
                          >
                            <v-list-item-title>{{ status.name }}</v-list-item-title>
                            <template #append>
                              <v-chip size="small" color="primary" variant="tonal">
                                {{ status.count }}
                              </v-chip>
                            </template>
                          </v-list-item>
                          <v-list-item v-if="!dashboardData.status_breakdown.length" class="px-0">
                            <v-list-item-title class="text-medium-emphasis">
                              No status breakdown available for the selected filters.
                            </v-list-item-title>
                          </v-list-item>
                        </v-list>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <v-col cols="12" lg="5">
                    <v-card rounded="lg" variant="outlined" class="h-100">
                      <v-card-title>Dashboard Filters</v-card-title>
                      <v-card-text>
                        <v-row dense>
                          <v-col cols="12" md="6">
                            <v-text-field
                              v-model="dashboardFilters.date_from"
                              label="Date From"
                              type="date"
                              variant="outlined"
                              density="compact"
                              hide-details
                            />
                          </v-col>
                          <v-col cols="12" md="6">
                            <v-text-field
                              v-model="dashboardFilters.date_to"
                              label="Date To"
                              type="date"
                              variant="outlined"
                              density="compact"
                              hide-details
                            />
                          </v-col>
                        </v-row>

                        <div class="d-flex ga-2 mt-3 flex-wrap">
                          <v-btn color="primary" :loading="dashboardLoading" @click="loadDashboard">
                            Apply
                          </v-btn>
                          <v-btn variant="outlined" @click="resetDashboardFilters">
                            Reset
                          </v-btn>
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-window-item>

          <v-window-item value="applications">
            <v-card class="mt-4" rounded="xl" elevation="2">
              <v-card-title class="text-h5">Application Reports</v-card-title>
              <v-card-text>
                <v-row dense class="mb-2">
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="applicationFilters.search"
                      label="Search applications"
                      prepend-inner-icon="mdi-magnify"
                      variant="outlined"
                      density="compact"
                      clearable
                      hint="Search application number, names, ID, location, reason, or status"
                      persistent-hint
                      @keyup.enter="searchApplications"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="applicationFilters.status_id"
                      :items="statusOptions"
                      item-title="title"
                      item-value="id"
                      label="Status"
                      clearable
                      variant="outlined"
                      density="compact"
                      :menu-props="{ maxHeight: 360 }"
                    />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      v-model="applicationFilters.date_from"
                      label="Date From"
                      type="date"
                      variant="outlined"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      v-model="applicationFilters.date_to"
                      label="Date To"
                      type="date"
                      variant="outlined"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex align-end justify-end">
                    <v-btn
                      icon="mdi-filter-remove-outline"
                      variant="tonal"
                      :title="'Reset filters'"
                      @click="resetApplicationFilters"
                    />
                  </v-col>
                </v-row>

                <div class="d-flex ga-2 flex-wrap mb-4">
                  <v-btn color="primary" :loading="applicationLoading" @click="searchApplications">
                    Search
                  </v-btn>
                  <v-btn variant="outlined" :loading="exportingApplications" @click="exportReport('applications')">
                    Export
                  </v-btn>
                </div>

                <v-data-table-server
                  :headers="applicationHeaders"
                  :items="applications"
                  :items-length="applicationPagination.total"
                  :items-per-page="applicationPagination.per_page"
                  :page="applicationPagination.page"
                  :loading="applicationLoading"
                  :items-per-page-options="[10, 25, 50]"
                  item-value="id"
                  no-data-text="No applications found for the selected filters"
                  @update:options="onApplicationTableOptions"
                >
                  <template #item.application_number="{ item }">
                    <router-link
                      :to="{ name: 'ApplicationDetail', params: { id: item.id } }"
                      class="text-decoration-none text-primary"
                    >
                      {{ item.application_number }}
                    </router-link>
                  </template>

                  <template #item.full_name="{ item }">
                    {{ item.full_name || '-' }}
                  </template>

                  <template #item.requested_name="{ item }">
                    {{ item.requested_name || '-' }}
                  </template>

                  <template #item.national_id="{ item }">
                    {{ item.national_id || '-' }}
                  </template>

                  <template #item.district="{ item }">
                    {{ item.district || '-' }}
                  </template>

                  <template #item.status="{ item }">
                    <v-chip size="small" :color="getStatusColor(item.status?.code)" variant="tonal">
                      {{ item.status?.name || '-' }}
                    </v-chip>
                  </template>

                  <template #item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                  </template>

                  <template #item.actions="{ item }">
                    <v-btn
                      icon="mdi-eye"
                      size="small"
                      variant="text"
                      :title="'View application'"
                      @click="$router.push({ name: 'ApplicationDetail', params: { id: item.id } })"
                    />
                  </template>
                </v-data-table-server>
              </v-card-text>
            </v-card>
          </v-window-item>

          <v-window-item value="vetting">
            <v-card class="mt-4" rounded="xl" elevation="2">
              <v-card-title class="text-h5">Vetting Reports</v-card-title>
              <v-card-text>
                <v-row dense class="mb-2">
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="vettingFilters.search"
                      label="Search vetting records"
                      prepend-inner-icon="mdi-magnify"
                      variant="outlined"
                      density="compact"
                      clearable
                      hint="Search application number, applicant, vetting type, status, or officer"
                      persistent-hint
                      @keyup.enter="searchVetting"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="vettingFilters.date_from"
                      label="Date From"
                      type="date"
                      variant="outlined"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="vettingFilters.date_to"
                      label="Date To"
                      type="date"
                      variant="outlined"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="2" class="d-flex align-end justify-end">
                    <v-btn
                      icon="mdi-filter-remove-outline"
                      variant="tonal"
                      :title="'Reset filters'"
                      @click="resetVettingFilters"
                    />
                  </v-col>
                </v-row>

                <div class="d-flex ga-2 flex-wrap mb-4">
                  <v-btn color="primary" :loading="vettingLoading" @click="searchVetting">
                    Search
                  </v-btn>
                  <v-btn variant="outlined" :loading="exportingVetting" @click="exportReport('vetting')">
                    Export
                  </v-btn>
                </div>

                <v-data-table-server
                  :headers="vettingHeaders"
                  :items="vettingRecords"
                  :items-length="vettingPagination.total"
                  :items-per-page="vettingPagination.per_page"
                  :page="vettingPagination.page"
                  :loading="vettingLoading"
                  :items-per-page-options="[10, 25, 50]"
                  item-value="id"
                  no-data-text="No vetting records found for the selected filters"
                  @update:options="onVettingTableOptions"
                >
                  <template #item.application_number="{ item }">
                    <router-link
                      :to="{ name: 'ApplicationDetail', params: { id: item.application?.id } }"
                      class="text-decoration-none text-primary"
                    >
                      {{ item.application?.application_number || '-' }}
                    </router-link>
                  </template>

                  <template #item.applicant_name="{ item }">
                    {{ item.application?.full_name || '-' }}
                  </template>

                  <template #item.vetting_type="{ item }">
                    {{ item.vettingType?.name || '-' }}
                  </template>

                  <template #item.recommendation="{ item }">
                    {{ item.recommendation?.name || '-' }}
                  </template>

                  <template #item.status="{ item }">
                    <v-chip size="small" :color="getStatusColor(item.status?.code)" variant="tonal">
                      {{ item.status?.name || '-' }}
                    </v-chip>
                  </template>

                  <template #item.conducted_by="{ item }">
                    {{ item.conductedBy?.username || '-' }}
                  </template>

                  <template #item.completed_at="{ item }">
                    {{ item.completed_at ? formatDate(item.completed_at) : '-' }}
                  </template>

                  <template #item.actions="{ item }">
                    <v-btn
                      icon="mdi-eye"
                      size="small"
                      variant="text"
                      :title="'View application'"
                      @click="$router.push({ name: 'ApplicationDetail', params: { id: item.application?.id } })"
                    />
                  </template>
                </v-data-table-server>
              </v-card-text>
            </v-card>
          </v-window-item>

          <v-window-item v-if="canViewAuditLogs" value="audit">
            <v-card class="mt-4" rounded="xl" elevation="2">
              <v-card-title class="text-h5">Audit Log</v-card-title>
              <v-card-text>
                <v-row dense class="mb-2">
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="auditFilters.search"
                      label="Search audit logs"
                      prepend-inner-icon="mdi-magnify"
                      variant="outlined"
                      density="compact"
                      clearable
                      hint="Search actions, users, models, or record IDs"
                      persistent-hint
                      @keyup.enter="searchAudit"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="auditFilters.date_from"
                      label="Date From"
                      type="date"
                      variant="outlined"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="auditFilters.date_to"
                      label="Date To"
                      type="date"
                      variant="outlined"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex align-end justify-end">
                    <v-btn
                      icon="mdi-filter-remove-outline"
                      variant="tonal"
                      :title="'Reset filters'"
                      @click="resetAuditFilters"
                    />
                  </v-col>
                </v-row>

                <div class="d-flex ga-2 flex-wrap mb-4">
                  <v-btn color="primary" :loading="auditLoading" @click="searchAudit">
                    Search
                  </v-btn>
                  <v-btn variant="outlined" :loading="exportingAudit" @click="exportReport('audit')">
                    Export
                  </v-btn>
                </div>

                <v-data-table-server
                  :headers="auditHeaders"
                  :items="auditLogs"
                  :items-length="auditPagination.total"
                  :items-per-page="auditPagination.per_page"
                  :page="auditPagination.page"
                  :loading="auditLoading"
                  :items-per-page-options="[10, 25, 50]"
                  item-value="id"
                  no-data-text="No audit logs found for the selected filters"
                  @update:options="onAuditTableOptions"
                >
                  <template #item.action="{ item }">
                    <v-chip size="small" variant="tonal">
                      {{ item.action || '-' }}
                    </v-chip>
                  </template>

                  <template #item.user="{ item }">
                    {{ item.user?.username || item.user_id || '-' }}
                  </template>

                  <template #item.model="{ item }">
                    {{ formatModelType(item.model_type) }}
                  </template>

                  <template #item.record_id="{ item }">
                    {{ item.model_id || '-' }}
                  </template>

                  <template #item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                  </template>
                </v-data-table-server>
              </v-card-text>
            </v-card>
          </v-window-item>
        </v-window>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { format } from 'date-fns'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { adminApi } from '@/api/admin'
import { reportsApi, type ReportParams } from '@/api/reports'

type TableOptions = {
  page?: number
  itemsPerPage?: number
}

type SummaryCard = {
  label: string
  value: number
  color: string
  icon: string
}

type PaginationState = {
  page: number
  per_page: number
  total: number
  last_page: number
}

const authStore = useAuthStore()
const toast = useToast()

const canViewAuditLogs = computed(() => authStore.hasPermission('view audit logs'))
const activeTab = ref<'dashboard' | 'applications' | 'vetting' | 'audit'>('dashboard')

const dashboardLoading = ref(false)
const applicationLoading = ref(false)
const vettingLoading = ref(false)
const auditLoading = ref(false)

const exportingApplications = ref(false)
const exportingVetting = ref(false)
const exportingAudit = ref(false)

const dashboardData = reactive({
  summary: {
    total: 0,
    handoff_to_admin: 0,
    approved: 0,
    denied: 0,
    returned_to_admin: 0,
    returned_to_police: 0,
    returned_to_nis: 0,
    police_vetting_completed: 0,
    police_vetting_total: 0,
    police_vetting_active: 0,
    nis_vetting_completed: 0,
    nis_vetting_total: 0,
    nis_vetting_active: 0,
  },
  status_breakdown: [] as any[]
})

const dashboardFilters = reactive({
  date_from: '',
  date_to: ''
})

const applicationFilters = reactive({
  search: '',
  status_id: null as number | null,
  date_from: '',
  date_to: '',
  page: 1,
  per_page: 10,
})

const vettingFilters = reactive({
  search: '',
  date_from: '',
  date_to: '',
  page: 1,
  per_page: 10,
})

const auditFilters = reactive({
  search: '',
  date_from: '',
  date_to: '',
  page: 1,
  per_page: 10,
})

const applicationPagination = reactive<PaginationState>({
  page: 1,
  per_page: 10,
  total: 0,
  last_page: 1
})

const vettingPagination = reactive<PaginationState>({
  page: 1,
  per_page: 10,
  total: 0,
  last_page: 1
})

const auditPagination = reactive<PaginationState>({
  page: 1,
  per_page: 10,
  total: 0,
  last_page: 1
})

const applications = ref<any[]>([])
const vettingRecords = ref<any[]>([])
const auditLogs = ref<any[]>([])
const statusOptions = ref<any[]>([])

const applicationsLoaded = ref(false)
const vettingLoaded = ref(false)
const auditLoaded = ref(false)

const dashboardCards = computed<SummaryCard[]>(() => [
  {
    label: 'Total Applications',
    value: dashboardData.summary.total,
    color: 'primary',
    icon: 'mdi-file-document-outline'
  },
  {
    label: 'Sent to Admin',
    value: dashboardData.summary.handoff_to_admin,
    color: 'warning',
    icon: 'mdi-send'
  },
  {
    label: 'Approved',
    value: dashboardData.summary.approved,
    color: 'success',
    icon: 'mdi-check-circle-outline'
  },
  {
    label: 'Denied',
    value: dashboardData.summary.denied,
    color: 'error',
    icon: 'mdi-close-circle-outline'
  }
])

const applicationHeaders = [
  { title: 'Application #', key: 'application_number', sortable: false },
  { title: 'Current Full Name', key: 'full_name' },
  { title: 'Requested Full Name', key: 'requested_name' },
  { title: 'National ID', key: 'national_id' },
  { title: 'District', key: 'district', sortable: false },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const vettingHeaders = [
  { title: 'Application #', key: 'application_number', sortable: false },
  { title: 'Applicant', key: 'applicant_name' },
  { title: 'Type', key: 'vetting_type' },
  { title: 'Recommendation', key: 'recommendation' },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'Conducted By', key: 'conducted_by' },
  { title: 'Completed At', key: 'completed_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const auditHeaders = [
  { title: 'Action', key: 'action' },
  { title: 'User', key: 'user' },
  { title: 'Model', key: 'model' },
  { title: 'Record ID', key: 'record_id' },
  { title: 'Created At', key: 'created_at' }
]

function formatDate(date: string | null | undefined) {
  if (!date) return '-'
  return format(new Date(date), 'MMM dd, yyyy HH:mm')
}

function formatModelType(modelType?: string | null) {
  if (!modelType) return '-'
  return modelType.split('\\').pop() || modelType
}

function getStatusColor(code?: string) {
  const colors: Record<string, string> = {
    handoff_to_admin: 'warning',
    returned_to_admin: 'warning',
    returned_to_police: 'info',
    returned_to_nis: 'info',
    approved: 'success',
    denied: 'error',
    in_progress: 'info',
    completed: 'success',
    police_vetting: 'primary',
    nis_vetting: 'primary',
    opc_review: 'secondary',
    pending_approval: 'indigo'
  }
  return colors[code || ''] || 'default'
}

function syncPagination(target: PaginationState, meta: any) {
  target.page = meta?.current_page ?? target.page
  target.per_page = meta?.per_page ?? target.per_page
  target.total = meta?.total ?? 0
  target.last_page = meta?.last_page ?? 1
}

function applicationRequestParams(): ReportParams {
  return {
    search: applicationFilters.search || undefined,
    status_id: applicationFilters.status_id || undefined,
    date_from: applicationFilters.date_from || undefined,
    date_to: applicationFilters.date_to || undefined,
    page: applicationFilters.page,
    per_page: applicationFilters.per_page
  }
}

function vettingRequestParams(): ReportParams {
  return {
    search: vettingFilters.search || undefined,
    date_from: vettingFilters.date_from || undefined,
    date_to: vettingFilters.date_to || undefined,
    page: vettingFilters.page,
    per_page: vettingFilters.per_page
  }
}

function auditRequestParams(): ReportParams {
  return {
    search: auditFilters.search || undefined,
    date_from: auditFilters.date_from || undefined,
    date_to: auditFilters.date_to || undefined,
    page: auditFilters.page,
    per_page: auditFilters.per_page
  }
}

async function loadDashboard() {
  dashboardLoading.value = true
  try {
    const response = await reportsApi.dashboard({
      date_from: dashboardFilters.date_from || undefined,
      date_to: dashboardFilters.date_to || undefined
    })

    if (response.data.success) {
      Object.assign(dashboardData.summary, response.data.data?.summary || {})
      dashboardData.status_breakdown = response.data.data?.status_breakdown || []
    } else {
      toast.error(response.data.error?.message || 'Failed to load dashboard data')
    }
  } catch (err) {
    console.error(err)
    toast.error('Failed to load dashboard data')
  } finally {
    dashboardLoading.value = false
  }
}

async function loadApplications() {
  applicationLoading.value = true
  try {
    const response = await reportsApi.applications(applicationRequestParams())
    if (response.data.success) {
      applications.value = response.data.data || []
      syncPagination(applicationPagination, response.data.meta)
      applicationsLoaded.value = true
    } else {
      toast.error(response.data.error?.message || 'Failed to load applications')
    }
  } catch (err: any) {
    console.error(err)
    toast.error(err.response?.data?.error?.message || 'Failed to load applications')
  } finally {
    applicationLoading.value = false
  }
}

async function loadVetting() {
  vettingLoading.value = true
  try {
    const response = await reportsApi.vetting(vettingRequestParams())
    if (response.data.success) {
      vettingRecords.value = response.data.data || []
      syncPagination(vettingPagination, response.data.meta)
      vettingLoaded.value = true
    } else {
      toast.error(response.data.error?.message || 'Failed to load vetting records')
    }
  } catch (err: any) {
    console.error(err)
    toast.error(err.response?.data?.error?.message || 'Failed to load vetting records')
  } finally {
    vettingLoading.value = false
  }
}

async function loadAudit() {
  if (!canViewAuditLogs.value) return
  auditLoading.value = true
  try {
    const response = await reportsApi.audit(auditRequestParams())
    if (response.data.success) {
      auditLogs.value = response.data.data || []
      syncPagination(auditPagination, response.data.meta)
      auditLoaded.value = true
    } else {
      toast.error(response.data.error?.message || 'Failed to load audit logs')
    }
  } catch (err: any) {
    console.error(err)
    toast.error(err.response?.data?.error?.message || 'Failed to load audit logs')
  } finally {
    auditLoading.value = false
  }
}

function searchApplications() {
  applicationPagination.page = 1
  applicationFilters.page = 1
  loadApplications()
}

function searchVetting() {
  vettingPagination.page = 1
  vettingFilters.page = 1
  loadVetting()
}

function searchAudit() {
  auditPagination.page = 1
  auditFilters.page = 1
  loadAudit()
}

function resetDashboardFilters() {
  dashboardFilters.date_from = ''
  dashboardFilters.date_to = ''
  loadDashboard()
}

function resetApplicationFilters() {
  applicationFilters.search = ''
  applicationFilters.status_id = null
  applicationFilters.date_from = ''
  applicationFilters.date_to = ''
  applicationFilters.page = 1
  applicationPagination.page = 1
  loadApplications()
}

function resetVettingFilters() {
  vettingFilters.search = ''
  vettingFilters.date_from = ''
  vettingFilters.date_to = ''
  vettingFilters.page = 1
  vettingPagination.page = 1
  loadVetting()
}

function resetAuditFilters() {
  auditFilters.search = ''
  auditFilters.date_from = ''
  auditFilters.date_to = ''
  auditFilters.page = 1
  auditPagination.page = 1
  loadAudit()
}

function onApplicationTableOptions(options: TableOptions) {
  applicationFilters.page = options.page || 1
  applicationFilters.per_page = options.itemsPerPage || applicationFilters.per_page
  applicationPagination.page = applicationFilters.page
  applicationPagination.per_page = applicationFilters.per_page

  if (applicationsLoaded.value) {
    loadApplications()
  }
}

function onVettingTableOptions(options: TableOptions) {
  vettingFilters.page = options.page || 1
  vettingFilters.per_page = options.itemsPerPage || vettingFilters.per_page
  vettingPagination.page = vettingFilters.page
  vettingPagination.per_page = vettingFilters.per_page

  if (vettingLoaded.value) {
    loadVetting()
  }
}

function onAuditTableOptions(options: TableOptions) {
  auditFilters.page = options.page || 1
  auditFilters.per_page = options.itemsPerPage || auditFilters.per_page
  auditPagination.page = auditFilters.page
  auditPagination.per_page = auditFilters.per_page

  if (auditLoaded.value) {
    loadAudit()
  }
}

async function loadStatusOptions() {
  try {
    const response = await adminApi.getApplicationStatuses()
    if (response.data.success) {
      statusOptions.value = (response.data.data || [])
        .filter((status: any) => status.is_active !== false)
        .filter((status: any) => status.code !== 'pending')
        .sort((a: any, b: any) => (a.order ?? 0) - (b.order ?? 0))
        .map((status: any) => ({
          id: status.id,
          title: status.code === 'pending_approval' ? 'Pending Approval' : status.name,
          code: status.code,
          order: status.order
        }))
    }
  } catch (err) {
    console.error('Failed to load status options:', err)
  }
}

async function exportReport(type: 'applications' | 'vetting' | 'audit') {
  if (type === 'applications') exportingApplications.value = true
  if (type === 'vetting') exportingVetting.value = true
  if (type === 'audit') exportingAudit.value = true

  try {
    const params =
      type === 'applications'
        ? { ...applicationRequestParams(), type }
        : type === 'vetting'
          ? { ...vettingRequestParams(), type }
          : { ...auditRequestParams(), type }

    const response = await reportsApi.export(params as any)
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `report-${type}-${Date.now()}.xlsx`
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Report exported successfully')
  } catch (err) {
    console.error(err)
    toast.error('Failed to export report')
  } finally {
    exportingApplications.value = false
    exportingVetting.value = false
    exportingAudit.value = false
  }
}

function handleTabChange(tab: string) {
  if (tab === 'applications' && !applicationsLoaded.value) {
    loadApplications()
  } else if (tab === 'vetting' && !vettingLoaded.value) {
    loadVetting()
  } else if (tab === 'audit' && canViewAuditLogs.value && !auditLoaded.value) {
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

<style scoped>
.reports-page {
  padding-bottom: 32px;
}

.reports-hero {
  padding: 24px 28px;
  background: linear-gradient(135deg, rgba(15, 54, 102, 0.06), rgba(15, 54, 102, 0.02));
}

.reports-hero__inner {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.reports-hero__eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 0.75rem;
  font-weight: 700;
  color: rgba(15, 54, 102, 0.72);
  margin-bottom: 8px;
}

.reports-tabs {
  border-radius: 14px;
  overflow: hidden;
}
</style>
