<template>
  <div class="gov-page">
    <v-sheet class="gov-hero">
      <div class="gov-hero__inner">
        <div>
          <div class="gov-hero__eyebrow">Case register</div>
          <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">Applications</h1>
          <div class="text-body-2 text-medium-emphasis">
            Search, filter, and manage applications through the workflow.
          </div>
        </div>
        <v-btn
          v-if="authStore.canEditApplications"
          class="gov-hero__action"
          color="primary"
          variant="flat"
          prepend-icon="mdi-plus"
          @click="$router.push({ name: 'CreateApplication' })"
        >
          Create Application
        </v-btn>
      </div>
    </v-sheet>

    <!-- Applications Table -->
    <v-card class="gov-card" elevation="2">
      <v-card-text>
        <div class="table-shell">
          <v-data-table-server
            :headers="headers"
            :items="applications"
            :loading="loading"
            :items-length="pagination.total"
            :items-per-page="pagination.per_page"
            :page="pagination.current_page"
            @update:options="handleTableOptions"
            item-value="id"
            no-data-text="No applications found for the selected filters"
          >
            <template #top>
              <div class="table-toolbar">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="filters.search"
                      label="Search"
                      prepend-inner-icon="mdi-magnify"
                      variant="outlined"
                      density="compact"
                      clearable
                      hint="Search names, IDs, dates, locations, reason, or status"
                      persistent-hint
                      @update:model-value="applyFilters"
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
                      @update:model-value="applyFilters"
                    />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      v-model="filters.date_from"
                      label="Date From"
                      type="date"
                      variant="outlined"
                      density="compact"
                      @update:model-value="applyFilters"
                    />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      v-model="filters.date_to"
                      label="Date To"
                      type="date"
                      variant="outlined"
                      density="compact"
                      @update:model-value="applyFilters"
                    />
                  </v-col>
                  <v-col cols="12" md="1" class="d-flex justify-end align-end">
                    <v-btn
                      variant="tonal"
                      icon="mdi-filter-remove-outline"
                      @click="resetFilters"
                    />
                  </v-col>
                </v-row>
              </div>
            </template>

            <template v-slot:item.application_number="{ item }">
              <router-link
                :to="{ name: 'ApplicationDetail', params: { id: item.id } }"
                class="text-decoration-none text-primary"
              >
                {{ item.application_number }}
              </router-link>
            </template>

            <template v-slot:item.district="{ item }">
              {{ item.district }}
            </template>

            <template v-slot:item.traditional_authority="{ item }">
              {{ item.traditional_authority }}
            </template>

            <template v-slot:item.village="{ item }">
              {{ item.village }}
            </template>

            <template v-slot:item.status="{ item }">
              <v-chip
                :color="getStatusColor(item.status?.code)"
                size="small"
              >
                {{ item.status?.name }}
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
                v-if="canEditItem(item)"
                icon="mdi-pencil"
                size="small"
                variant="text"
                @click="editApplication(item)"
              />
              <v-btn
                v-if="canDeleteItem(item)"
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="deleteApplication(item)"
              />
            </template>
          </v-data-table-server>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { applicationsApi, type Application } from '@/api/applications'
import { adminApi } from '@/api/admin'
import { useToast } from 'vue-toastification'
import { format } from 'date-fns'

type StatusOption = {
  title: string
  value: number
  code: string
}

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)
const applications = ref<Application[]>([])
const statusOptions = ref<StatusOption[]>([])
const hasInitialized = ref(false)

const filters = reactive({
  search: '',
  status_id: null as number | null,
  assigned_opc_approver_id: null as number | null,
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
  { title: 'District', key: 'district', sortable: false },
  { title: 'T/A', key: 'traditional_authority', sortable: false },
  { title: 'Village', key: 'village', sortable: false },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

function getStatusColor(statusCode: string) {
  const colors: Record<string, string> = {
    'returned_to_data_entry': 'orange',
    'opc_review': 'purple',
    'police_completed': 'teal',
    'nis_completed': 'teal',
    'pending_approval': 'indigo',
    'approved': 'success',
    'denied': 'error',
    'police_vetting': 'primary',
    'nis_vetting': 'primary',
    'archived': 'grey'
  }
  return colors[statusCode] || 'grey'
}

function formatDate(date: string) {
  if (!date) return ''
  return format(new Date(date), 'MMM dd, yyyy')
}

function getSortParams(sortBy: Array<{ key: string; order?: 'asc' | 'desc' }>) {
  const primarySort = sortBy?.[0]
  if (!primarySort) {
    return {
      order_by: 'created_at',
      order_dir: 'desc'
    }
  }

  return {
    order_by: primarySort.key,
    order_dir: primarySort.order || 'asc'
  }
}

function getQueryValue(value: unknown): string {
  if (Array.isArray(value)) {
    return value[0] ?? ''
  }

  return typeof value === 'string' ? value : ''
}

function getQueryNumber(value: unknown): number | null {
  const raw = getQueryValue(value)
  if (!raw) return null

  const parsed = Number(raw)
  return Number.isNaN(parsed) ? null : parsed
}

function findStatusOptionByCode(code: string): StatusOption | undefined {
  return statusOptions.value.find(option => option.code === code)
}

function applyRouteFilters() {
  const statusCode = getQueryValue(route.query.status)
  const statusId = getQueryNumber(route.query.status_id)
  const assignedApproverId = getQueryNumber(route.query.assigned_opc_approver_id)
  const search = getQueryValue(route.query.search)
  const dateFrom = getQueryValue(route.query.date_from)
  const dateTo = getQueryValue(route.query.date_to)

  filters.search = search
  filters.date_from = dateFrom
  filters.date_to = dateTo
  filters.assigned_opc_approver_id = assignedApproverId

  if (statusId) {
    filters.status_id = statusId
  } else if (statusCode) {
    filters.status_id = findStatusOptionByCode(statusCode)?.value ?? null
  } else if (authStore.hasAnyRole(['opc_approver'])) {
    filters.status_id = findStatusOptionByCode('pending_approval')?.value ?? null
  } else {
    filters.status_id = null
  }
}

async function fetchApplications(sortBy: Array<{ key: string; order?: 'asc' | 'desc' }> = []) {
  loading.value = true
  try {
    const params: any = {
      page: pagination.current_page,
      per_page: pagination.per_page,
      ...getSortParams(sortBy)
    }

    if (filters.search) params.search = filters.search
    if (filters.status_id) params.status_id = filters.status_id
    if (filters.assigned_opc_approver_id) params.assigned_opc_approver_id = filters.assigned_opc_approver_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const response = await applicationsApi.list(params)
    if (response.data.success) {
      // Handle paginated response structure
      if (response.data.data?.data) {
        applications.value = response.data.data.data
      } else if (Array.isArray(response.data.data)) {
        applications.value = response.data.data
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

async function fetchStatusOptions() {
  try {
    const response = await adminApi.getApplicationStatuses()
    if (response.data.success) {
      statusOptions.value = response.data.data
        .map((status: any) => ({
          title: status.name,
          value: status.id,
          code: status.code
        }))
    }
  } catch (error) {
    console.error('Error fetching application statuses:', error)
  }
}

function applyFilters() {
  pagination.current_page = 1
  fetchApplications()
}

function resetFilters() {
  filters.search = ''
  filters.status_id = null
  filters.assigned_opc_approver_id = null
  filters.date_from = ''
  filters.date_to = ''
  if (authStore.hasAnyRole(['opc_approver'])) {
    filters.status_id = findStatusOptionByCode('pending_approval')?.value ?? null
  }
  pagination.current_page = 1
  fetchApplications()
}

function handleTableOptions(options: any) {
  pagination.current_page = options.page || 1
  pagination.per_page = options.itemsPerPage || pagination.per_page
  fetchApplications(options.sortBy || [])
}

function canEditItem(item: Application): boolean {
  return item.allowed_actions?.includes('edit_application') ?? false
}

function canDeleteItem(item: Application): boolean {
  return item.allowed_actions?.includes('delete_application') ?? false
}

function editApplication(application: Application) {
  router.push({ name: 'EditApplication', params: { id: application.id } })
}

async function deleteApplication(application: Application) {
  if (!confirm(`Are you sure you want to delete application ${application.application_number}?`)) {
    return
  }

  try {
    await applicationsApi.delete(application.id)
    toast.success('Application deleted successfully')
    fetchApplications()
  } catch (error: any) {
    toast.error('Failed to delete application')
  }
}

async function initializePage() {
  await fetchStatusOptions()
  applyRouteFilters()
  hasInitialized.value = true
  fetchApplications()
}

onMounted(() => {
  void initializePage()
})

watch(
  () => route.query,
  () => {
    if (!hasInitialized.value) return
    applyRouteFilters()
    pagination.current_page = 1
    fetchApplications()
  },
  { deep: true }
)
</script>

<style scoped>
.gov-hero {
  margin-bottom: 20px;
}

.gov-hero__inner {
  align-items: center;
}

.gov-hero__action {
  flex-shrink: 0;
}

.table-shell {
  overflow-x: auto;
}

.table-toolbar {
  padding: 16px 16px 0;
}

@media (max-width: 960px) {
  .table-shell :deep(.v-table) {
    min-width: 900px;
  }
}

@media (max-width: 600px) {
  .gov-hero__action {
    width: 100%;
  }
}
</style>
