<template>
  <div class="gov-page">
    <v-sheet class="gov-hero">
      <div class="gov-hero__inner dashboard-hero">
        <div>
          <div class="gov-hero__eyebrow">{{ dashboardProfile.eyebrow }}</div>
          <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">{{ dashboardProfile.title }}</h1>
          <div class="text-body-2 text-medium-emphasis">
            {{ dashboardProfile.description }}
          </div>
        </div>

        <div class="dashboard-hero__badges">
          <v-chip color="primary" variant="tonal">
            {{ dashboardProfile.scope }}
          </v-chip>
          <v-chip
            v-if="dashboardRole !== 'approver' && authStore.canViewAllApplications"
            color="success"
            variant="tonal"
          >
            All data access
          </v-chip>
          <v-chip v-else color="warning" variant="tonal">
            Scoped workspace
          </v-chip>
        </div>
      </div>
    </v-sheet>

    <template v-if="dashboardRole === 'approver'">
      <v-row class="mt-4">
        <v-col cols="12">
          <v-card class="gov-card" elevation="2">
            <v-card-title class="gov-card__title dashboard-tabs__header">
              <div>
                <div class="text-h6">Approval Queue</div>
                <div class="text-caption text-medium-emphasis">
                  Use the tabs below to switch the list without leaving the dashboard.
                </div>
              </div>

              <v-btn-toggle
                v-model="activeApproverView"
                class="dashboard-tabs"
                color="primary"
                mandatory
                variant="outlined"
              >
                <v-btn
                  v-for="tab in approverTabs"
                  :key="tab.key"
                  :value="tab.key"
                  class="dashboard-tabs__pill"
                  rounded="pill"
                  :prepend-icon="tab.icon"
                  @click="setApproverView(tab.key)"
                >
                  <v-badge
                    class="dashboard-tabs__bubble"
                    :content="getTabBadgeCount(tab.badgeKey || tab.key)"
                    :color="tab.badgeColor || getTabBadgeColor(tab.key)"
                    bordered
                    floating
                  >
                    <span class="dashboard-tabs__label">{{ tab.label }}</span>
                  </v-badge>
                </v-btn>
              </v-btn-toggle>
            </v-card-title>

            <v-divider />

            <v-card-text>
              <div class="d-flex flex-wrap align-center justify-space-between mb-4">
                <div>
                  <div class="text-h6">{{ getCurrentApproverListTitle() }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ getCurrentApproverListSubtitle() }}
                  </div>
                </div>
                <v-chip color="primary" variant="tonal">
                  {{ approverPagination.total }} records
                </v-chip>
              </div>

              <v-row class="dashboard-search-row" dense>
                <v-col cols="12" md="6" lg="5" class="dashboard-search-col">
                  <v-text-field
                    v-model="approverSearch"
                    label="Search"
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="compact"
                    clearable
                    hint="Search application number, names, district, T/A, village, reason, or status"
                    persistent-hint
                    class="dashboard-search-field"
                    @update:model-value="applyApproverSearch"
                  />
                </v-col>
              </v-row>

              <v-data-table-server
                :headers="tableHeaders"
                :items="approverApplications"
                :loading="approverLoading"
                :items-length="approverPagination.total"
                :items-per-page="approverPagination.per_page"
                :page="approverPagination.current_page"
                @update:options="handleApproverTableOptions"
                item-value="id"
                no-data-text="No applications found for this tab"
              >
                <template #item.status="{ item }">
                  <v-chip :color="getStatusColor(item.status?.code)" size="small">
                    {{ item.status?.name }}
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
                    @click="openApplicationDetails(item.id)"
                  />
                </template>
              </v-data-table-server>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>

    <template v-else>
      <v-row class="mt-4">
        <v-col cols="12">
          <v-card class="gov-card" elevation="2">
            <v-card-title class="gov-card__title dashboard-tabs__header">
              <div>
                <div class="text-h6">{{ dashboardProfile.workspaceTitle }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ dashboardProfile.workspaceSubtitle }}
                </div>
              </div>
            </v-card-title>

            <v-card-text v-if="dashboardRole !== 'admin'" class="dashboard-tabs__panel dashboard-tabs__panel--compact">
              <div class="dashboard-tabs--compact">
                <div class="dashboard-tabs__row-grid">
                  <v-btn
                    v-for="tab in workspaceTabs"
                    :key="tab.key"
                    :value="tab.key"
                    class="dashboard-tabs__pill dashboard-tabs__pill--compact"
                    :class="{ 'dashboard-tabs__pill--active': activeWorkspaceView === tab.key }"
                    :variant="activeWorkspaceView === tab.key ? 'tonal' : 'outlined'"
                    rounded="pill"
                    :prepend-icon="tab.icon"
                    @click="activeWorkspaceView = tab.key"
                  >
                    <v-badge
                      class="dashboard-tabs__bubble"
                      :content="getTabBadgeCount(tab.badgeKey || tab.key)"
                      :color="tab.badgeColor || getTabBadgeColor(tab.key)"
                      bordered
                      floating
                      >
                        <span class="dashboard-tabs__label">{{ tab.label }}</span>
                      </v-badge>
                    </v-btn>
                </div>
              </div>
            </v-card-text>

            <v-card-text v-if="dashboardRole === 'admin'" class="dashboard-tabs__panel">
              <div class="dashboard-tabs">
                <section
                  v-for="group in workspaceTabGroups"
                  :key="group.title || 'workspace-group'"
                  class="dashboard-tabs__group"
                >
                  <div v-if="group.title && workspaceTabGroups.length > 1" class="dashboard-tabs__group-label">
                    {{ group.title }}
                  </div>
                  <div class="dashboard-tabs__group-toggle">
                    <v-btn
                      v-for="tab in group.tabs"
                      :key="tab.key"
                      :value="tab.key"
                      class="dashboard-tabs__pill"
                      :class="{ 'dashboard-tabs__pill--active': activeWorkspaceView === tab.key }"
                      :variant="activeWorkspaceView === tab.key ? 'tonal' : 'outlined'"
                      rounded="pill"
                      :prepend-icon="tab.icon"
                      @click="activeWorkspaceView = tab.key"
                    >
                      <v-badge
                        class="dashboard-tabs__bubble"
                        :content="getTabBadgeCount(tab.badgeKey || tab.key)"
                        :color="tab.badgeColor || getTabBadgeColor(tab.key)"
                        bordered
                        floating
                      >
                        <span class="dashboard-tabs__label">{{ tab.label }}</span>
                      </v-badge>
                    </v-btn>
                  </div>
                </section>
              </div>
            </v-card-text>

            <v-divider />

            <v-card-text>
              <div class="d-flex flex-wrap align-center justify-space-between mb-4">
                <div>
                  <div class="text-h6">{{ getCurrentWorkspaceListTitle() }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ getCurrentWorkspaceListSubtitle() }}
                  </div>
                </div>
                <v-chip color="primary" variant="tonal">
                  {{ workspacePagination.total }} records
                </v-chip>
              </div>

              <v-row class="dashboard-search-row" dense>
                <v-col cols="12" md="6" lg="5" class="dashboard-search-col">
                  <v-text-field
                    v-model="workspaceSearch"
                    label="Search"
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="compact"
                    clearable
                    hint="Search application number, names, district, T/A, village, reason, or status"
                    persistent-hint
                    class="dashboard-search-field"
                    @update:model-value="applyWorkspaceSearch"
                  />
                </v-col>
              </v-row>

              <v-data-table-server
                :headers="tableHeaders"
                :items="workspaceApplications"
                :loading="workspaceLoading"
                :items-length="workspacePagination.total"
                :items-per-page="workspacePagination.per_page"
                :page="workspacePagination.current_page"
                @update:options="handleWorkspaceTableOptions"
                item-value="id"
                no-data-text="No applications found for this workspace"
              >
                <template #item.status="{ item }">
                  <div class="d-flex flex-wrap align-center ga-2">
                    <v-chip :color="getWorkspaceStatusColor(item)" size="small">
                      {{ getWorkspaceStatusLabel(item) }}
                    </v-chip>
                    <v-chip
                      v-if="dashboardRole === 'admin' && activeWorkspaceView === 'returned_to_admin' && item.approver_send_back_reason"
                      color="warning"
                      size="small"
                      variant="tonal"
                    >
                      Return
                    </v-chip>
                  </div>
                </template>
                <template #item.created_at="{ item }">
                  {{ formatDate(item.created_at) }}
                </template>
                <template #item.actions="{ item }">
                  <v-btn
                    icon="mdi-eye"
                    size="small"
                    variant="text"
                    @click="openApplicationDetails(item.id)"
                  />
                </template>
              </v-data-table-server>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'
import { applicationsApi, type Application, type ApplicationListParams } from '@/api/applications'

type DashboardRole = 'admin' | 'approver' | 'police' | 'nis' | 'data_entry' | 'default'
type ApproverView =
  | 'all'
  | 'pending_approval'
  | 'approved'
  | 'denied_by_me'
type WorkspaceView = string

type DashboardMetric = {
  title: string
  value: number
  icon: string
  color: string
  tabKey?: ApproverView | WorkspaceView
  to?: {
    name: string
    query?: Record<string, string | undefined>
  }
}

type DashboardStage = {
  label: string
  value: number
  icon: string
  color: string
}

type DashboardWorkspaceTab = {
  key: WorkspaceView
  label: string
  icon: string
  badgeKey?: string
  badgeColor?: string
}

type DashboardAction = {
  title: string
  icon: string
  color: string
  to?: { name: string }
  scrollTo?: string
}

type DashboardProfile = {
  eyebrow: string
  title: string
  description: string
  scope: string
  workspaceTitle: string
  workspaceSubtitle: string
  workspaceTabs: DashboardWorkspaceTab[]
  workspaceTabGroups?: Array<{
    title: string
    tabs: DashboardWorkspaceTab[]
  }>
  defaultWorkspaceView: WorkspaceView
  workflowSubtitle: string
  actionsTitle: string
  actionsSubtitle: string
  recentTitle: string
  recentSubtitle: string
  metrics: DashboardMetric[]
  stages: DashboardStage[]
  actions: DashboardAction[]
}

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const loading = ref(false)
const derivedCounts = reactive<Record<string, number>>({})
const activeApproverView = ref<ApproverView>('all')
const approverApplications = ref<Application[]>([])
const approverLoading = ref(false)
const approverSortBy = ref<Array<{ key: string; order?: 'asc' | 'desc' }>>([])
const approverSearch = ref('')
const approverPagination = reactive({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1
})
const activeWorkspaceView = ref<WorkspaceView>('')
const workspaceApplications = ref<Application[]>([])
const workspaceLoading = ref(false)
const workspaceSortBy = ref<Array<{ key: string; order?: 'asc' | 'desc' }>>([])
const workspaceSearch = ref('')
const workspacePagination = reactive({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1
})
const workspaceInitialized = ref(false)

const tableHeaders = [
  { title: 'Application Number', key: 'application_number' },
  { title: 'Current Full Name', key: 'full_name' },
  { title: 'Requested Full Name', key: 'requested_name' },
  { title: 'Status', key: 'status' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const approverTabs: Array<{ key: ApproverView; label: string; icon: string; badgeKey?: string; badgeColor?: string }> = [
  { key: 'all', label: 'All Applications', icon: 'mdi-view-list', badgeKey: 'total_visible', badgeColor: 'secondary' },
  { key: 'pending_approval', label: 'Pending Approval', icon: 'mdi-badge-account', badgeColor: 'indigo' },
  { key: 'approved', label: 'Approved', icon: 'mdi-check-circle', badgeColor: 'success' },
  { key: 'denied_by_me', label: 'Denied', icon: 'mdi-close-circle', badgeKey: 'denied', badgeColor: 'error' }
]

const dashboardRole = computed<DashboardRole>(() => {
  if (authStore.isAdmin) return 'admin'
  if (authStore.hasAnyRole(['opc_approver'])) return 'approver'
  if (authStore.hasAnyRole(['police_officer'])) return 'police'
  if (authStore.hasAnyRole(['nis_officer'])) return 'nis'
  if (authStore.hasAnyRole(['opc_data_entry'])) return 'data_entry'
  return 'default'
})

const totalApplications = computed(() => Number(derivedCounts.total_visible ?? derivedCounts.total ?? 0))

function getStatusCount(code: string): number {
  return Number(derivedCounts[code] ?? 0)
}

function getDashboardCount(key: string): number {
  return Number(derivedCounts[key] ?? 0)
}

function getTabBadgeCount(key: string): number {
  return Number(derivedCounts[key] ?? 0)
}

function getTabBadgeColor(key: string): string {
  switch (key) {
    case 'handoff_to_admin':
    case 'pending_approval':
      return 'blue-grey-darken-1'
    case 'approved':
    case 'police_vetting_completed':
    case 'nis_vetting_completed':
      return 'success'
    case 'denied':
    case 'denied_by_me':
      return 'error'
    case 'returned':
    case 'returned_to_admin':
    case 'returned_to_police':
    case 'returned_to_nis':
    case 'returned_to_data_entry':
      return 'warning'
    case 'queue':
    case 'police_vetting_active':
    case 'nis_vetting_active':
      return 'primary'
    case 'draft':
      return 'grey'
    case 'all':
    case 'total_visible':
      return 'secondary'
    default:
      return 'primary'
  }
}

function formatDate(value?: string | null): string {
  if (!value) return ''
  return format(new Date(value), 'MMM dd, yyyy')
}

function extractApplications(responseData: any): Application[] {
  if (responseData?.data?.data) return responseData.data.data
  if (Array.isArray(responseData?.data)) return responseData.data
  return []
}

function getApproverViewLabel(view: ApproverView): string {
  return approverTabs.find(tab => tab.key === view)?.label ?? 'All Applications'
}

function getApproverScopeParams(): ApplicationListParams {
  return {}
}

function getApproverViewParams(view: ApproverView): ApplicationListParams {
  const scopeParams = getApproverScopeParams()

  switch (view) {
    case 'all':
      return scopeParams
    case 'approved':
      return {
        ...scopeParams,
        status: 'approved'
      }
    case 'denied_by_me':
      return {
        ...scopeParams,
        status: 'denied'
      }
    case 'pending_approval':
    default:
      return {
        ...scopeParams,
        status: 'pending_approval'
      }
  }
}

function getCurrentApproverListTitle(): string {
  return getApproverViewLabel(activeApproverView.value)
}

function getCurrentApproverListSubtitle(): string {
  switch (activeApproverView.value) {
    case 'all':
      return 'All applications in the approval queue.'
    case 'approved':
      return 'Applications approved by your account.'
    case 'denied_by_me':
      return 'Applications denied by your account.'
    case 'pending_approval':
    default:
      return 'Applications waiting for your decision.'
  }
}

function setApproverView(view: ApproverView) {
  activeApproverView.value = view
}

function getRouteViewQuery(): string | null {
  return typeof route.query.view === 'string' && route.query.view ? route.query.view : null
}

function applyInitialDashboardView() {
  const requestedView = getRouteViewQuery()

  if (dashboardRole.value === 'approver') {
    const view = approverTabs.some(tab => tab.key === requestedView)
      ? (requestedView as ApproverView)
      : 'all'

    activeApproverView.value = view
    return
  }

  const fallbackView = dashboardProfile.value.defaultWorkspaceView
  const view = requestedView && dashboardProfile.value.workspaceTabs.some(tab => tab.key === requestedView)
    ? requestedView
    : fallbackView

  activeWorkspaceView.value = view
  workspaceInitialized.value = true
}

function getWorkspaceViewLabel(view: WorkspaceView): string {
  if (dashboardRole.value === 'admin' && view === 'handoff_to_admin') {
    return 'New'
  }

  if (dashboardRole.value === 'data_entry' && view === 'draft') {
    return 'Drafts'
  }

  return dashboardProfile.value.workspaceTabs.find(tab => tab.key === view)?.label ?? 'Applications'
}

function getWorkspaceViewParams(view: WorkspaceView): ApplicationListParams {
  const userId = authStore.user?.id

  switch (dashboardRole.value) {
    case 'admin':
      switch (view) {
        case 'handoff_to_admin':
          return { status: 'handoff_to_admin' }
        case 'all':
        default:
          return {}
        case 'opc_review':
          return { status: 'opc_review', review_state: 'open' }
        case 'returned_to_admin':
          return { status: 'opc_review', review_state: 'returned' }
        case 'returned_to_police':
          return { status: 'police_vetting', vetting_type: 'police', vetting_state: 'returned' }
        case 'returned_to_nis':
          return { status: 'nis_vetting', vetting_type: 'nis', vetting_state: 'returned' }
        case 'police_vetting':
          return { status: 'police_vetting', vetting_type: 'police', vetting_state: 'active' }
        case 'nis_vetting':
          return { status: 'nis_vetting', vetting_type: 'nis', vetting_state: 'active' }
        case 'approved':
          return { status: 'approved' }
        case 'denied':
          return { status: 'denied' }
      }
    case 'police':
      switch (view) {
        case 'queue':
          return { status: 'police_vetting', assigned_police_officer_id: userId, vetting_type: 'police', vetting_state: 'active' }
        case 'completed':
          return { assigned_police_officer_id: userId, vetting_type: 'police', vetting_state: 'completed' }
        case 'returned':
          return { status: 'police_vetting', assigned_police_officer_id: userId, vetting_type: 'police', vetting_state: 'returned' }
        default:
          return { assigned_police_officer_id: userId }
      }
    case 'nis':
      switch (view) {
        case 'queue':
          return { status: 'nis_vetting', assigned_nis_officer_id: userId, vetting_type: 'nis', vetting_state: 'active' }
        case 'completed':
          return { assigned_nis_officer_id: userId, vetting_type: 'nis', vetting_state: 'completed' }
        case 'returned':
          return { status: 'nis_vetting', assigned_nis_officer_id: userId, vetting_type: 'nis', vetting_state: 'returned' }
        default:
          return { assigned_nis_officer_id: userId }
      }
    case 'data_entry':
      switch (view) {
        case 'draft':
          return { status: 'draft' }
        case 'handoff_to_admin':
          return { status: 'handoff_to_admin' }
        case 'returned':
          return { status: 'returned_to_data_entry' }
        case 'police_vetting':
          return { status: 'police_vetting' }
        case 'nis_vetting':
          return { status: 'nis_vetting' }
        case 'all':
        default:
          return {}
      }
    default:
      switch (view) {
        case 'returned':
          return { status: 'returned_to_data_entry' }
        case 'approved':
          return { status: 'approved' }
        case 'denied':
          return { status: 'denied' }
        case 'all':
        default:
          return {}
      }
  }
}

function getWorkspaceScopeParams(): ApplicationListParams {
  const userId = authStore.user?.id

  switch (dashboardRole.value) {
    case 'admin':
    case 'data_entry':
    case 'default':
      return {}
    case 'police':
      return {
        assigned_police_officer_id: userId,
        vetting_type: 'police'
      }
    case 'nis':
      return {
        assigned_nis_officer_id: userId,
        vetting_type: 'nis'
      }
    case 'approver':
    default:
      return {}
  }
}

function getCurrentWorkspaceListTitle(): string {
  return getWorkspaceViewLabel(activeWorkspaceView.value)
}

function getCurrentWorkspaceListSubtitle(): string {
  switch (dashboardRole.value) {
    case 'admin':
      switch (activeWorkspaceView.value) {
        case 'handoff_to_admin':
          return 'Applications handed off by data entry and waiting for admin review.'
        case 'opc_review':
          return 'Applications currently under OPC review after police and NIS vetting.'
        case 'returned_to_admin':
          return 'Applications returned by the approver for further action.'
        case 'returned_to_police':
          return 'Applications sent back to police by OPC for corrections.'
        case 'returned_to_nis':
          return 'Applications sent back to NIS by OPC for corrections.'
        case 'approved':
          return 'Applications already approved.'
        case 'denied':
          return 'Applications denied in the workflow.'
        case 'all':
        default:
          return 'All applications in the register.'
      }
    case 'police':
      switch (activeWorkspaceView.value) {
        case 'queue':
          return 'Applications awaiting police vetting.'
        case 'completed':
          return 'Applications completed by police.'
        case 'returned':
          return 'Applications sent back by OPC for correction.'
        default:
          return 'Your assigned police vetting queue.'
      }
    case 'nis':
      switch (activeWorkspaceView.value) {
        case 'queue':
          return 'Applications awaiting NIS vetting.'
        case 'completed':
          return 'Applications completed by NIS.'
        case 'returned':
          return 'Applications sent back by OPC for correction.'
        default:
          return 'Your assigned NIS vetting queue.'
      }
    case 'data_entry':
      switch (activeWorkspaceView.value) {
        case 'draft':
          return 'Draft submissions saved by data entry.'
        case 'handoff_to_admin':
          return 'Applications handed off to admin for review.'
        case 'returned':
          return 'Applications returned for correction.'
        case 'police_vetting':
          return 'Applications currently with police.'
        case 'nis_vetting':
          return 'Applications currently with NIS.'
        case 'all':
        default:
          return 'All applications visible to data entry.'
      }
    default:
      switch (activeWorkspaceView.value) {
        case 'returned':
          return 'Applications returned to data entry.'
        case 'approved':
          return 'Applications already approved.'
        case 'denied':
          return 'Applications denied in the workflow.'
        default:
          return 'Applications visible in this workspace.'
      }
  }
}

function handleWorkspaceTableOptions(options: any) {
  workspacePagination.current_page = options.page || 1
  workspacePagination.per_page = options.itemsPerPage || workspacePagination.per_page
  workspaceSortBy.value = options.sortBy || []
  void fetchWorkspaceApplications(options.sortBy || [])
}

const dashboardProfile = computed<DashboardProfile>(() => {
  switch (dashboardRole.value) {
    case 'admin':
      return {
        eyebrow: 'Administration',
        title: 'Admin Application Dashboard',
        description: 'Full system overview with workflow visibility, administration shortcuts, and case management.',
        scope: 'All records',
        workspaceTitle: 'Application Register',
        workspaceSubtitle: 'Switch between the main admin views without leaving the dashboard.',
        workspaceTabs: [
          { key: 'all', label: 'All Applications', icon: 'mdi-view-list', badgeKey: 'total_visible', badgeColor: 'secondary' },
          { key: 'handoff_to_admin', label: 'New', icon: 'mdi-bell-badge-outline', badgeColor: 'blue-grey-darken-1' },
          { key: 'opc_review', label: 'OPC Review', icon: 'mdi-account-eye', badgeColor: 'purple' },
          { key: 'returned_to_admin', label: 'Returned', icon: 'mdi-arrow-u-left-bottom', badgeColor: 'warning' },
          { key: 'returned_to_police', label: 'Returned to Police', icon: 'mdi-shield-check', badgeColor: 'primary' },
          { key: 'returned_to_nis', label: 'Returned to NIS', icon: 'mdi-shield-account', badgeColor: 'primary' },
          { key: 'police_vetting', label: 'Police Vetting', icon: 'mdi-shield-check', badgeKey: 'police_vetting_active', badgeColor: 'primary' },
          { key: 'nis_vetting', label: 'NIS Vetting', icon: 'mdi-shield-account', badgeKey: 'nis_vetting_active', badgeColor: 'primary' },
          { key: 'approved', label: 'Approved', icon: 'mdi-check-circle', badgeColor: 'success' },
          { key: 'denied', label: 'Denied', icon: 'mdi-close-circle', badgeColor: 'error' }
        ],
        workspaceTabGroups: [
          {
            title: 'Register',
            tabs: [
              { key: 'all', label: 'All Applications', icon: 'mdi-view-list', badgeKey: 'total_visible', badgeColor: 'secondary' },
              { key: 'handoff_to_admin', label: 'New', icon: 'mdi-bell-badge-outline', badgeColor: 'blue-grey-darken-1' }
            ]
          },
          {
            title: 'Review',
            tabs: [
              { key: 'opc_review', label: 'OPC Review', icon: 'mdi-account-eye', badgeColor: 'purple' },
              { key: 'returned_to_admin', label: 'Returned', icon: 'mdi-arrow-u-left-bottom', badgeColor: 'warning' }
            ]
          },
          {
            title: 'Returns',
            tabs: [
              { key: 'returned_to_police', label: 'Returned to Police', icon: 'mdi-shield-check', badgeColor: 'primary' },
              { key: 'returned_to_nis', label: 'Returned to NIS', icon: 'mdi-shield-account', badgeColor: 'primary' }
            ]
          },
          {
            title: 'Vetting',
            tabs: [
              { key: 'police_vetting', label: 'Police Vetting', icon: 'mdi-shield-check', badgeKey: 'police_vetting_active', badgeColor: 'primary' },
              { key: 'nis_vetting', label: 'NIS Vetting', icon: 'mdi-shield-account', badgeKey: 'nis_vetting_active', badgeColor: 'primary' }
            ]
          },
          {
            title: 'Decisions',
            tabs: [
              { key: 'approved', label: 'Approved', icon: 'mdi-check-circle', badgeColor: 'success' },
              { key: 'denied', label: 'Denied', icon: 'mdi-close-circle', badgeColor: 'error' }
            ]
          }
        ],
        defaultWorkspaceView: 'all',
        workflowSubtitle: 'Monitor the complete application flow across every stage.',
        actionsTitle: 'Admin Actions',
        actionsSubtitle: 'Jump to the modules used most often by administrators.',
        recentTitle: 'Recent Applications',
        recentSubtitle: 'Latest submissions across the full register.',
        metrics: [
      { title: 'All Applications', value: totalApplications.value, icon: 'mdi-file-document-multiple', color: 'primary' },
      { title: 'New', value: getStatusCount('handoff_to_admin'), icon: 'mdi-bell-badge-outline', color: 'warning' },
      { title: 'OPC Review', value: getDashboardCount('opc_review'), icon: 'mdi-account-eye', color: 'purple' },
      { title: 'Returned', value: getDashboardCount('returned_to_admin'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
      { title: 'Returned to Police', value: getDashboardCount('returned_to_police'), icon: 'mdi-shield-check', color: 'primary' },
      { title: 'Returned to NIS', value: getDashboardCount('returned_to_nis'), icon: 'mdi-shield-account', color: 'primary' },
          { title: 'Police Vetting', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { title: 'NIS Vetting', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' }
        ],
        stages: [
      { label: 'New', value: getStatusCount('handoff_to_admin'), icon: 'mdi-bell-badge-outline', color: 'warning' },
      { label: 'Approved', value: getStatusCount('approved'), icon: 'mdi-check-circle', color: 'success' },
      { label: 'Denied', value: getStatusCount('denied'), icon: 'mdi-close-circle', color: 'error' },
      { label: 'OPC Review', value: getDashboardCount('opc_review'), icon: 'mdi-account-eye', color: 'purple' },
      { label: 'Returned', value: getDashboardCount('returned_to_admin'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
      { label: 'Returned to Police', value: getDashboardCount('returned_to_police'), icon: 'mdi-shield-check', color: 'primary' },
      { label: 'Returned to NIS', value: getDashboardCount('returned_to_nis'), icon: 'mdi-shield-account', color: 'primary' }
    ],
        actions: [
          { title: 'Applications', icon: 'mdi-file-document-multiple', color: 'primary', to: { name: 'Applications' } },
          { title: 'Reports', icon: 'mdi-chart-box', color: 'info', to: { name: 'Reports' } },
          { title: 'Admin Panel', icon: 'mdi-cog', color: 'secondary', to: { name: 'Admin' } }
        ]
      }
    case 'approver':
      return {
        eyebrow: 'Approval Desk',
        title: 'Approver Application Dashboard',
        description: 'Review pending approvals and switch between filtered application lists directly on the dashboard.',
        scope: 'Approval queue',
        workspaceTitle: '',
        workspaceSubtitle: '',
        workspaceTabs: [],
        defaultWorkspaceView: 'pending_approval',
        workflowSubtitle: '',
        actionsTitle: '',
        actionsSubtitle: '',
        recentTitle: '',
        recentSubtitle: '',
        metrics: [
          {
            title: 'Pending Approval',
            value: getStatusCount('pending_approval'),
            icon: 'mdi-progress-check',
            color: 'indigo',
            tabKey: 'pending_approval'
          },
          {
            title: 'Approved',
            value: getStatusCount('approved'),
            icon: 'mdi-check-circle',
            color: 'success',
            tabKey: 'approved'
          },
          {
            title: 'Denied',
            value: getStatusCount('denied'),
            icon: 'mdi-close-circle',
            color: 'error',
            tabKey: 'denied_by_me'
          },
    {
      title: 'OPC Review',
      value: getDashboardCount('opc_review'),
      icon: 'mdi-account-eye',
      color: 'purple',
      tabKey: 'opc_review'
    },
    {
      title: 'Returned',
      value: getDashboardCount('returned_to_admin'),
      icon: 'mdi-arrow-u-left-bottom',
      color: 'warning',
      tabKey: 'returned_to_admin'
    },
          {
            title: 'Returned to Police',
            value: getDashboardCount('returned_to_police'),
            icon: 'mdi-shield-check',
            color: 'primary',
            tabKey: 'returned_to_police'
          },
          {
            title: 'Returned to NIS',
            value: getDashboardCount('returned_to_nis'),
            icon: 'mdi-shield-account',
            color: 'primary',
            tabKey: 'returned_to_nis'
          }
        ],
        stages: [],
        actions: []
      }
    case 'police':
      return {
        eyebrow: 'Police Vetting',
        title: 'Police Application Dashboard',
        description: 'Track vetting cases, completed work, and applications awaiting the next workflow step.',
        scope: 'Vetting workspace',
        workspaceTitle: 'Police Workspace',
        workspaceSubtitle: 'Switch between your queue, completed cases, and returned applications.',
        workspaceTabs: [
          { key: 'queue', label: 'Queue', icon: 'mdi-shield-check', badgeKey: 'police_vetting_active', badgeColor: 'primary' },
          { key: 'completed', label: 'Completed', icon: 'mdi-check-circle', badgeKey: 'police_vetting_completed', badgeColor: 'success' },
          { key: 'returned', label: 'Returned by OPC', icon: 'mdi-arrow-u-left-bottom', badgeKey: 'returned_to_police', badgeColor: 'warning' }
        ],
        defaultWorkspaceView: 'queue',
        workflowSubtitle: 'Follow the police vetting queue and completed cases.',
        actionsTitle: '',
        actionsSubtitle: '',
        recentTitle: 'Vetting Applications',
        recentSubtitle: 'Latest records visible to the police officer workspace.',
        metrics: [
          { title: 'Police Queue', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { title: 'Completed', value: getDashboardCount('police_vetting_completed'), icon: 'mdi-check-circle', color: 'success' },
          { title: 'Returned by OPC', value: getDashboardCount('returned_to_police'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' }
        ],
        stages: [
          { label: 'Police Vetting', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { label: 'Police Completed', value: getDashboardCount('police_vetting_completed'), icon: 'mdi-checkbox-marked-circle', color: 'success' },
          { label: 'OPC Review', value: getStatusCount('opc_review'), icon: 'mdi-account-eye', color: 'purple' },
          { label: 'Returned by OPC', value: getDashboardCount('returned_to_police'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' }
        ],
        actions: []
      }
    case 'nis':
      return {
        eyebrow: 'NIS Vetting',
        title: 'NIS Application Dashboard',
        description: 'Monitor NIS vetting cases and the transition into final approval.',
        scope: 'Vetting workspace',
        workspaceTitle: 'NIS Workspace',
        workspaceSubtitle: 'Switch between your queue, completed cases, and returned applications.',
        workspaceTabs: [
          { key: 'queue', label: 'Queue', icon: 'mdi-shield-account', badgeKey: 'nis_vetting_active', badgeColor: 'primary' },
          { key: 'completed', label: 'Completed', icon: 'mdi-check-circle', badgeKey: 'nis_vetting_completed', badgeColor: 'success' },
          { key: 'returned', label: 'Returned by OPC', icon: 'mdi-arrow-u-left-bottom', badgeKey: 'returned_to_nis', badgeColor: 'warning' }
        ],
        defaultWorkspaceView: 'queue',
        workflowSubtitle: 'Follow the NIS vetting queue and completed cases.',
        actionsTitle: '',
        actionsSubtitle: '',
        recentTitle: 'Vetting Applications',
        recentSubtitle: 'Latest records visible to the NIS officer workspace.',
        metrics: [
          { title: 'NIS Queue', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' },
          { title: 'Completed', value: getDashboardCount('nis_vetting_completed'), icon: 'mdi-check-circle', color: 'success' },
          { title: 'Returned by OPC', value: getDashboardCount('returned_to_nis'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' }
        ],
        stages: [
          { label: 'NIS Vetting', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' },
          { label: 'NIS Completed', value: getDashboardCount('nis_vetting_completed'), icon: 'mdi-checkbox-marked-circle', color: 'success' },
          { label: 'OPC Review', value: getStatusCount('opc_review'), icon: 'mdi-account-eye', color: 'purple' },
          { label: 'Returned by OPC', value: getDashboardCount('returned_to_nis'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' }
        ],
        actions: []
      }
    case 'data_entry':
      return {
        eyebrow: 'Data Entry',
        title: 'Application Dashboard',
        description: 'Track submissions, corrections, and the full application register from the data-entry workspace.',
        scope: 'All applications',
        workspaceTitle: 'Data Entry Workspace',
        workspaceSubtitle: 'Switch between all, returned, and vetting-stage applications.',
        workspaceTabs: [
          { key: 'all', label: 'All Applications', icon: 'mdi-view-list', badgeKey: 'total_visible', badgeColor: 'secondary' },
          { key: 'draft', label: 'Drafts', icon: 'mdi-file-document-edit', badgeColor: 'grey' },
          { key: 'handoff_to_admin', label: 'Sent to Admin', icon: 'mdi-arrow-right', badgeColor: 'blue-grey-darken-1' },
          { key: 'returned', label: 'Returned', icon: 'mdi-alert-circle', badgeKey: 'returned_to_data_entry', badgeColor: 'warning' }
        ],
        defaultWorkspaceView: 'all',
        workflowSubtitle: 'Watch applications move from entry into vetting and approval.',
        actionsTitle: '',
        actionsSubtitle: '',
        recentTitle: 'Recent Applications',
        recentSubtitle: 'Latest records entered or corrected in the system.',
        metrics: [
          { title: 'All Applications', value: totalApplications.value, icon: 'mdi-file-document-multiple', color: 'primary' },
          { title: 'Drafts', value: getStatusCount('draft'), icon: 'mdi-file-document-edit', color: 'warning' },
          { title: 'Sent to Admin', value: getStatusCount('handoff_to_admin'), icon: 'mdi-arrow-right', color: 'info' },
          { title: 'Returned', value: getStatusCount('returned_to_data_entry'), icon: 'mdi-alert-circle', color: 'error' },
          { title: 'In Vetting', value: getDashboardCount('police_vetting_active') + getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-search', color: 'info' }
        ],
        stages: [
          { label: 'Drafts', value: getStatusCount('draft'), icon: 'mdi-file-document-edit', color: 'warning' },
          { label: 'Sent to Admin', value: getStatusCount('handoff_to_admin'), icon: 'mdi-arrow-right', color: 'info' },
          { label: 'Returned to Data Entry', value: getStatusCount('returned_to_data_entry'), icon: 'mdi-arrow-u-left-bottom', color: 'error' },
          { label: 'Police Vetting', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { label: 'NIS Vetting', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' }
        ],
        actions: []
      }
    default:
      return {
        eyebrow: 'Application Dashboard',
        title: 'Application Dashboard',
        description: 'Monitor application volumes and recent activity.',
        scope: 'Workspace',
        workspaceTitle: 'Workspace',
        workspaceSubtitle: 'Switch between the main application views.',
        workspaceTabs: [
          { key: 'all', label: 'All Applications', icon: 'mdi-view-list', badgeKey: 'total_visible', badgeColor: 'secondary' },
          { key: 'draft', label: 'Drafts', icon: 'mdi-file-document-edit', badgeColor: 'grey' },
          { key: 'handoff_to_admin', label: 'Sent to Admin', icon: 'mdi-arrow-right', badgeColor: 'blue-grey-darken-1' },
          { key: 'returned', label: 'Returned', icon: 'mdi-arrow-u-left-bottom', badgeKey: 'returned_to_data_entry', badgeColor: 'warning' },
          { key: 'approved', label: 'Approved', icon: 'mdi-check-circle', badgeColor: 'success' },
          { key: 'denied', label: 'Denied', icon: 'mdi-close-circle', badgeColor: 'error' }
        ],
        defaultWorkspaceView: 'all',
        workflowSubtitle: 'Track the main workflow stages.',
        actionsTitle: 'Quick Actions',
        actionsSubtitle: 'Open the primary workspace modules.',
        recentTitle: 'Recent Applications',
        recentSubtitle: 'Latest submissions and status updates.',
        metrics: [
          { title: 'Total Applications', value: totalApplications.value, icon: 'mdi-file-document', color: 'primary' },
          { title: 'Drafts', value: getStatusCount('draft'), icon: 'mdi-file-document-edit', color: 'warning' },
          { title: 'Sent to Admin', value: getStatusCount('handoff_to_admin'), icon: 'mdi-arrow-right', color: 'info' },
          { title: 'Returned', value: getStatusCount('returned_to_data_entry'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
          { title: 'Approved', value: getStatusCount('approved'), icon: 'mdi-check-circle', color: 'success' },
          { title: 'Denied', value: getStatusCount('denied'), icon: 'mdi-close-circle', color: 'error' }
        ],
        stages: [
          { label: 'Police Vetting', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { label: 'NIS Vetting', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' },
          { label: 'Handoff to Admin', value: getStatusCount('handoff_to_admin'), icon: 'mdi-arrow-right', color: 'indigo' },
          { label: 'Returned', value: getStatusCount('returned_to_data_entry'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
          { label: 'Approved', value: getStatusCount('approved'), icon: 'mdi-check-circle', color: 'success' }
        ],
        actions: [
          { title: 'Applications', icon: 'mdi-file-document-multiple', color: 'primary', to: { name: 'Applications' } }
        ]
      }
  }
})

const workflowStages = computed(() => dashboardProfile.value.stages)
const workspaceTabs = computed(() => dashboardProfile.value.workspaceTabs)
const workspaceTabGroups = computed(() => dashboardProfile.value.workspaceTabGroups || [{
  title: dashboardProfile.value.workspaceTitle,
  tabs: dashboardProfile.value.workspaceTabs
}])

function getStatusColor(statusCode?: string) {
  const colors: Record<string, string> = {
    draft: 'grey',
    handoff_to_admin: 'warning',
    returned_to_data_entry: 'orange',
    opc_review: 'purple',
    police_completed: 'teal',
    nis_completed: 'teal',
    pending_approval: 'indigo',
    approved: 'success',
    denied: 'error',
    police_vetting: 'primary',
    nis_vetting: 'primary',
    archived: 'grey'
  }
  return colors[statusCode || ''] || 'grey'
}

function getWorkspaceStatusLabel(item: Application): string {
  const roleSearchActive = dashboardRole.value === 'approver'
    ? !!approverSearch.value.trim()
    : !!workspaceSearch.value.trim()

  if (!roleSearchActive && dashboardRole.value === 'admin') {
    switch (activeWorkspaceView.value) {
      case 'handoff_to_admin':
        return 'New'
      case 'opc_review':
        return 'OPC Review'
      case 'returned_to_admin':
        return 'Returned'
      case 'returned_to_police':
        return 'Returned to Police'
      case 'returned_to_nis':
        return 'Returned to NIS'
      case 'approved':
        return 'Approved'
      case 'denied':
        return 'Denied'
      default:
        return item.status?.name || 'Unknown'
    }
  }

  if (!roleSearchActive && dashboardRole.value === 'data_entry') {
    switch (activeWorkspaceView.value) {
      case 'draft':
        return 'Draft'
      case 'handoff_to_admin':
        return 'Sent to Admin'
      case 'returned':
        return 'Returned'
      case 'police_vetting':
        return 'Police Vetting'
      case 'nis_vetting':
        return 'NIS Vetting'
      default:
        return item.status?.name || 'Unknown'
    }
  }

  if (!roleSearchActive && (dashboardRole.value === 'police' || dashboardRole.value === 'nis')) {
    switch (activeWorkspaceView.value) {
      case 'queue':
        return 'Queue'
      case 'completed':
        return 'Completed'
      case 'returned':
        return 'Returned by OPC'
      default:
        return 'Workspace'
    }
  }

  return item.status?.name || 'Unknown'
}

function getWorkspaceStatusColor(item: Application): string {
  const roleSearchActive = dashboardRole.value === 'approver'
    ? !!approverSearch.value.trim()
    : !!workspaceSearch.value.trim()

  if (!roleSearchActive && dashboardRole.value === 'admin') {
    switch (activeWorkspaceView.value) {
      case 'handoff_to_admin':
        return 'warning'
      case 'opc_review':
        return getStatusColor(item.status?.code)
      case 'returned_to_admin':
        return 'warning'
      case 'returned_to_police':
      case 'returned_to_nis':
        return 'warning'
      case 'approved':
        return 'success'
      case 'denied':
        return 'error'
      default:
        return getStatusColor(item.status?.code)
    }
  }

  if (!roleSearchActive && dashboardRole.value === 'data_entry') {
    switch (activeWorkspaceView.value) {
      case 'draft':
        return 'grey'
      case 'handoff_to_admin':
        return 'warning'
      case 'returned':
        return 'warning'
      case 'police_vetting':
      case 'nis_vetting':
        return 'primary'
      default:
        return getStatusColor(item.status?.code)
    }
  }

  if (!roleSearchActive && (dashboardRole.value === 'police' || dashboardRole.value === 'nis')) {
    switch (activeWorkspaceView.value) {
      case 'queue':
        return 'primary'
      case 'completed':
        return 'success'
      case 'returned':
        return 'warning'
      default:
        return 'primary'
    }
  }

  return getStatusColor(item.status?.code)
}

function getApproverListParams() {
  return {
    page: approverPagination.current_page,
    per_page: approverPagination.per_page,
    order_by: approverSortBy.value[0]?.key || 'created_at',
    order_dir: approverSortBy.value[0]?.order || 'desc',
    ...(approverSearch.value ? { search: approverSearch.value } : {}),
    ...getApproverViewParams(activeApproverView.value)
  }
}

async function fetchWorkspaceApplications(sortBy: Array<{ key: string; order?: 'asc' | 'desc' }> = workspaceSortBy.value) {
  if (dashboardRole.value === 'approver') {
    return
  }

  workspaceLoading.value = true
  try {
    workspaceSortBy.value = sortBy
    const search = workspaceSearch.value.trim()
    const response = await applicationsApi.list({
      page: workspacePagination.current_page,
      per_page: workspacePagination.per_page,
      order_by: sortBy[0]?.key || 'created_at',
      order_dir: sortBy[0]?.order || 'desc',
      ...getWorkspaceViewParams(activeWorkspaceView.value),
      ...(search ? { search } : {})
    })

    if (response.data.success) {
      workspaceApplications.value = extractApplications(response.data)
      workspacePagination.current_page = response.data.meta?.current_page || 1
      workspacePagination.total = response.data.meta?.total || 0
      workspacePagination.last_page = response.data.meta?.last_page || 1
    }
  } catch (error) {
    console.error('Error fetching workspace applications:', error)
  } finally {
    workspaceLoading.value = false
  }
}

function handleApproverTableOptions(options: any) {
  approverPagination.current_page = options.page || 1
  approverPagination.per_page = options.itemsPerPage || approverPagination.per_page
  approverSortBy.value = options.sortBy || []
  void fetchApproverApplications(options.sortBy || [])
}

function applyApproverSearch() {
  approverPagination.current_page = 1
  void fetchApproverApplications()
}

function applyWorkspaceSearch() {
  workspacePagination.current_page = 1
  void fetchWorkspaceApplications()
}

function handleAction(action: DashboardAction) {
  if (action.scrollTo) {
    document.getElementById(action.scrollTo)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    return
  }

  if (action.to) {
    router.push(action.to)
  }
}

function clearDerivedCounts() {
  Object.keys(derivedCounts).forEach((key) => {
    delete derivedCounts[key]
  })
}

async function fetchCountSnapshot(key: string, params: ApplicationListParams = {}) {
  try {
    const response = await applicationsApi.list({
      page: 1,
      per_page: 1,
      order_by: 'created_at',
      order_dir: 'desc',
      ...params
    })

    if (response.data.success) {
      derivedCounts[key] = Number(response.data.meta?.total ?? 0)
    }
  } catch (error) {
    console.error(`Error fetching dashboard count for ${key}:`, error)
  }
}

async function fetchDerivedCounts() {
  clearDerivedCounts()

  const userId = authStore.user?.id
  const tasks: Array<Promise<void>> = []
  const add = (key: string, params: ApplicationListParams) => {
    tasks.push(fetchCountSnapshot(key, params))
  }

  switch (dashboardRole.value) {
    case 'admin':
      add('total_visible', {})
      add('total', {})
      add('handoff_to_admin', { status: 'handoff_to_admin' })
      add('opc_review', { status: 'opc_review', review_state: 'open' })
      add('returned_to_admin', { status: 'opc_review', review_state: 'returned' })
      add('returned_to_police', { status: 'police_vetting', vetting_type: 'police', vetting_state: 'returned' })
      add('returned_to_nis', { status: 'nis_vetting', vetting_type: 'nis', vetting_state: 'returned' })
      add('approved', { status: 'approved' })
      add('denied', { status: 'denied' })
      add('police_vetting_active', { status: 'police_vetting', vetting_type: 'police', vetting_state: 'active' })
      add('nis_vetting_active', { status: 'nis_vetting', vetting_type: 'nis', vetting_state: 'active' })
      add('police_vetting_completed', { vetting_type: 'police', vetting_state: 'completed' })
      add('nis_vetting_completed', { vetting_type: 'nis', vetting_state: 'completed' })
      break
    case 'approver':
      add('total_visible', {})
      add('pending_approval', { status: 'pending_approval' })
      add('approved', { status: 'approved' })
      add('denied', { status: 'denied' })
      add('returned_to_admin', { status: 'opc_review' })
      add('returned_to_police', { status: 'police_vetting', vetting_type: 'police', vetting_state: 'returned' })
      add('returned_to_nis', { status: 'nis_vetting', vetting_type: 'nis', vetting_state: 'returned' })
      break
    case 'police':
      add('police_vetting_active', { status: 'police_vetting', assigned_police_officer_id: userId, vetting_type: 'police', vetting_state: 'active' })
      add('police_vetting_completed', { assigned_police_officer_id: userId, vetting_type: 'police', vetting_state: 'completed' })
      add('returned_to_police', { status: 'police_vetting', assigned_police_officer_id: userId, vetting_type: 'police', vetting_state: 'returned' })
      add('total_visible', { assigned_police_officer_id: userId })
      add('total', { assigned_police_officer_id: userId })
      break
    case 'nis':
      add('nis_vetting_active', { status: 'nis_vetting', assigned_nis_officer_id: userId, vetting_type: 'nis', vetting_state: 'active' })
      add('nis_vetting_completed', { assigned_nis_officer_id: userId, vetting_type: 'nis', vetting_state: 'completed' })
      add('returned_to_nis', { status: 'nis_vetting', assigned_nis_officer_id: userId, vetting_type: 'nis', vetting_state: 'returned' })
      add('total_visible', { assigned_nis_officer_id: userId })
      add('total', { assigned_nis_officer_id: userId })
      break
    case 'data_entry':
      add('total_visible', {})
      add('total', {})
      add('draft', { status: 'draft' })
      add('handoff_to_admin', { status: 'handoff_to_admin' })
      add('returned_to_data_entry', { status: 'returned_to_data_entry' })
      add('police_vetting_active', { status: 'police_vetting', vetting_type: 'police', vetting_state: 'active' })
      add('nis_vetting_active', { status: 'nis_vetting', vetting_type: 'nis', vetting_state: 'active' })
      break
    default:
      add('total_visible', {})
      add('total', {})
      add('returned_to_data_entry', { status: 'returned_to_data_entry' })
      add('approved', { status: 'approved' })
      add('denied', { status: 'denied' })
      break
  }

  await Promise.allSettled(tasks)
}

function openApplicationDetails(id: number) {
  router.push({
    name: 'ApplicationDetail',
    params: { id },
    query: { returnTo: route.fullPath }
  })
}

async function fetchApproverApplications(sortBy: Array<{ key: string; order?: 'asc' | 'desc' }> = approverSortBy.value) {
  if (dashboardRole.value !== 'approver') {
    return
  }

  approverLoading.value = true
  try {
    approverSortBy.value = sortBy
    const search = approverSearch.value.trim()
    const response = await applicationsApi.list({
      page: approverPagination.current_page,
      per_page: approverPagination.per_page,
      order_by: sortBy[0]?.key || 'created_at',
      order_dir: sortBy[0]?.order || 'desc',
      ...getApproverViewParams(activeApproverView.value),
      ...(search ? { search } : {})
    })

    if (response.data.success) {
      approverApplications.value = extractApplications(response.data)
      approverPagination.current_page = response.data.meta?.current_page || 1
      approverPagination.total = response.data.meta?.total || 0
      approverPagination.last_page = response.data.meta?.last_page || 1
    }
  } catch (error) {
    console.error('Error fetching approver applications:', error)
  } finally {
    approverLoading.value = false
  }
}

onMounted(async () => {
  applyInitialDashboardView()
  loading.value = true
  await fetchDerivedCounts()
  loading.value = false
  if (dashboardRole.value === 'approver') {
    await fetchApproverApplications()
  } else {
    if (!workspaceInitialized.value) {
      activeWorkspaceView.value = dashboardProfile.value.defaultWorkspaceView
      workspaceInitialized.value = true
    }
    await fetchWorkspaceApplications()
  }
})

watch(
  () => route.query.view,
  () => {
    applyInitialDashboardView()
  }
)

watch(activeApproverView, () => {
  if (dashboardRole.value !== 'approver') {
    return
  }

  approverPagination.current_page = 1
  void fetchApproverApplications()
})

watch(activeWorkspaceView, () => {
  if (dashboardRole.value === 'approver' || !workspaceInitialized.value) {
    return
  }

  workspacePagination.current_page = 1
  void fetchWorkspaceApplications()
})
</script>

<style scoped>
.h-100 {
  height: 100%;
}

.dashboard-hero {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
}

.dashboard-hero__badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.dashboard-tabs__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.dashboard-tabs__panel {
  padding-top: 0;
  padding-bottom: 20px;
}

.dashboard-tabs--compact {
  display: block;
  width: 100%;
  min-width: 0;
}

.dashboard-tabs__row-grid {
  display: flex;
  flex-wrap: nowrap;
  align-items: stretch;
  gap: 10px;
  width: 100%;
  min-width: 0;
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 4px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
}

.dashboard-tabs__row-grid :deep(.v-btn) {
  flex: 0 0 auto;
  width: auto;
  min-width: 0;
  white-space: nowrap;
}

.dashboard-tabs {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 10px;
  width: 100%;
  align-items: start;
}

.dashboard-tabs__group {
  display: grid;
  gap: 10px;
  min-width: 0;
  min-height: 164px;
  padding: 12px 10px 16px;
  border: 1px solid rgba(136, 156, 185, 0.24);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.88) 0%, rgba(247, 250, 255, 0.96) 100%);
  box-shadow: 0 12px 28px rgba(18, 56, 95, 0.04);
}

.dashboard-tabs__group-label {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: rgba(76, 96, 126, 0.88);
  padding-left: 4px;
}

.dashboard-tabs__group-toggle {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
  align-items: stretch;
  width: 100%;
}

.dashboard-tabs__group-toggle :deep(.v-btn) {
  width: 100%;
  min-width: 0;
  justify-content: flex-start;
}

.dashboard-tabs__pill {
  border-radius: 999px;
  text-transform: none;
  display: flex;
  align-items: center;
  gap: 6px;
  padding-inline: 12px;
  min-height: 48px;
  position: relative;
  white-space: nowrap;
  overflow: visible;
  margin-inline-end: 0;
  font-size: 0.95rem;
}

.dashboard-tabs__pill--compact {
  min-height: 46px;
  padding-inline: 14px;
  font-size: 0.92rem;
}

.dashboard-tabs__pill--active {
  box-shadow: 0 8px 20px rgba(18, 56, 95, 0.08);
}

.dashboard-tabs__label {
  display: inline-flex;
  align-items: center;
}

.dashboard-tabs__bubble :deep(.v-badge__badge) {
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 999px;
  font-size: 0.66rem;
  font-weight: 700;
  line-height: 1;
  letter-spacing: 0.01em;
  box-shadow: 0 3px 8px rgba(18, 56, 95, 0.16);
  transform: translate(3px, -3px);
}

.dashboard-search-row {
  margin-top: 6px;
  margin-bottom: 8px;
}

.dashboard-search-col {
  padding-top: 0;
  padding-bottom: 0;
}

.dashboard-search-field {
  max-width: 560px;
}

.dashboard-search-field :deep(.v-field) {
  border-radius: 16px;
}

.dashboard-search-field :deep(.v-input__details) {
  padding-top: 4px;
  min-height: 0;
}

.dashboard-search-field :deep(.v-messages) {
  min-height: 0;
}

.workflow-flow {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
}

.workflow-flow__card {
  border-radius: 16px;
}

@media (max-width: 960px) {
  .dashboard-hero {
    flex-direction: column;
  }

  .dashboard-hero__badges {
    justify-content: flex-start;
  }

  .dashboard-tabs {
    grid-template-columns: 1fr;
  }

  .dashboard-tabs--compact {
    width: 100%;
  }

  .dashboard-tabs__row-grid {
    flex-wrap: nowrap;
  }
}

@media (min-width: 1200px) and (max-width: 1599px) {
  .dashboard-tabs {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
</style>
