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

    <v-row class="mt-4">
      <v-col
        v-for="stat in summaryCards"
        :key="stat.title"
        cols="12"
        sm="6"
        md="3"
      >
        <v-card
          class="gov-card h-100"
          :class="{
            'metric-card--clickable': dashboardRole === 'approver' && !!stat.tabKey,
            'metric-card--active': dashboardRole === 'approver' && stat.tabKey === activeApproverView
          }"
          elevation="2"
          @click="stat.tabKey && setApproverView(stat.tabKey)"
        >
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
                  {{ tab.label }}
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

              <v-btn-toggle
                v-model="activeWorkspaceView"
                class="dashboard-tabs"
                color="primary"
                mandatory
                variant="outlined"
              >
                <v-btn
                  v-for="tab in dashboardProfile.workspaceTabs"
                  :key="tab.key"
                  :value="tab.key"
                  class="dashboard-tabs__pill"
                  rounded="pill"
                  :prepend-icon="tab.icon"
                >
                  {{ tab.label }}
                </v-btn>
              </v-btn-toggle>
            </v-card-title>

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

      <v-row class="mt-4" v-if="dashboardProfile.actions.length">
        <v-col cols="12">
          <v-card class="gov-card" elevation="2">
            <v-card-title class="gov-card__title">
              <div>
                <div class="text-h6">{{ dashboardProfile.actionsTitle }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ dashboardProfile.actionsSubtitle }}
                </div>
              </div>
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col
                  v-for="action in dashboardProfile.actions"
                  :key="action.title"
                  cols="12"
                  sm="6"
                  md="4"
                >
                  <v-btn
                    block
                    size="large"
                    :color="action.color"
                    variant="tonal"
                    :prepend-icon="action.icon"
                    @click="handleAction(action)"
                  >
                    {{ action.title }}
                  </v-btn>
                </v-col>
              </v-row>
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
import { reportsApi } from '@/api/reports'

type DashboardRole = 'admin' | 'approver' | 'police' | 'nis' | 'data_entry' | 'default'
type ApproverView =
  | 'pending_approval'
  | 'approved'
  | 'denied_by_me'
  | 'returned_to_admin'
  | 'returned_to_police'
  | 'returned_to_nis'
type WorkspaceView = string

type DashboardMetric = {
  title: string
  value: number
  icon: string
  color: string
  tabKey?: ApproverView
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
const dashboardData = ref<any>(null)
const activeApproverView = ref<ApproverView>('pending_approval')
const approverApplications = ref<Application[]>([])
const approverLoading = ref(false)
const approverSortBy = ref<Array<{ key: string; order?: 'asc' | 'desc' }>>([])
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

const approverTabs: Array<{ key: ApproverView; label: string; icon: string }> = [
  { key: 'pending_approval', label: 'Pending Approval', icon: 'mdi-badge-account' },
  { key: 'approved', label: 'Approved', icon: 'mdi-check-circle' },
  { key: 'denied_by_me', label: 'Denied', icon: 'mdi-close-circle' },
  { key: 'returned_to_admin', label: 'Returned to Admin', icon: 'mdi-arrow-u-left-bottom' },
  { key: 'returned_to_police', label: 'Returned to Police', icon: 'mdi-shield-check' },
  { key: 'returned_to_nis', label: 'Returned to NIS', icon: 'mdi-shield-account' }
]

const dashboardRole = computed<DashboardRole>(() => {
  if (authStore.isAdmin) return 'admin'
  if (authStore.hasAnyRole(['opc_approver'])) return 'approver'
  if (authStore.hasAnyRole(['police_officer'])) return 'police'
  if (authStore.hasAnyRole(['nis_officer'])) return 'nis'
  if (authStore.hasAnyRole(['opc_data_entry'])) return 'data_entry'
  return 'default'
})

const statusCounts = computed<Record<string, number>>(() => {
  const breakdown = dashboardData.value?.status_breakdown || []
  return breakdown.reduce((acc: Record<string, number>, item: any) => {
    acc[item.code] = Number(item.count) || 0
    return acc
  }, {})
})

const totalApplications = computed(() =>
  Number(dashboardData.value?.total_applications ?? dashboardData.value?.summary?.total ?? 0)
)

function getStatusCount(code: string): number {
  return statusCounts.value[code] || 0
}

function getDashboardCount(key: string): number {
  const summaryValue = dashboardData.value?.summary?.[key]
  if (summaryValue !== undefined && summaryValue !== null) {
    return Number(summaryValue) || 0
  }

  const workflowValue = dashboardData.value?.workflow_counts?.[key]
  if (workflowValue !== undefined && workflowValue !== null) {
    return Number(workflowValue) || 0
  }

  const topLevelValue = dashboardData.value?.[key]
  return Number(topLevelValue) || 0
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
  return approverTabs.find(tab => tab.key === view)?.label ?? 'Pending Approval'
}

function getApproverViewParams(view: ApproverView): ApplicationListParams {
  const userId = authStore.user?.id

  switch (view) {
    case 'approved':
      return {
        status: 'approved',
        assigned_opc_approver_id: userId
      }
    case 'denied_by_me':
      return {
        status: 'denied',
        assigned_opc_approver_id: userId
      }
    case 'returned_to_admin':
      return {
        status: 'opc_review',
        assigned_opc_approver_id: userId
      }
    case 'returned_to_police':
      return {
        status: 'police_vetting',
        assigned_opc_approver_id: userId,
        vetting_type: 'police',
        vetting_state: 'returned'
      }
    case 'returned_to_nis':
      return {
        status: 'nis_vetting',
        assigned_opc_approver_id: userId,
        vetting_type: 'nis',
        vetting_state: 'returned'
      }
    case 'pending_approval':
    default:
      return {
        status: 'pending_approval',
        assigned_opc_approver_id: userId
      }
  }
}

function getCurrentApproverListTitle(): string {
  return getApproverViewLabel(activeApproverView.value)
}

function getCurrentApproverListSubtitle(): string {
  switch (activeApproverView.value) {
    case 'approved':
      return 'Applications approved by your account.'
    case 'denied_by_me':
      return 'Applications denied by your account.'
    case 'returned_to_admin':
      return 'Applications returned to admin for further review.'
    case 'returned_to_police':
      return 'Applications sent back to police by OPC for correction.'
    case 'returned_to_nis':
      return 'Applications sent back to NIS by OPC for correction.'
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
      : 'pending_approval'

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
  return dashboardProfile.value.workspaceTabs.find(tab => tab.key === view)?.label ?? 'Applications'
}

function getWorkspaceViewParams(view: WorkspaceView): ApplicationListParams {
  const userId = authStore.user?.id

  switch (dashboardRole.value) {
    case 'admin':
      switch (view) {
        case 'pending_approval':
          return { status: 'pending_approval' }
        case 'returned_to_admin':
          return { status: 'opc_review' }
        case 'returned_to_police':
          return { status: 'police_vetting', vetting_type: 'police', vetting_state: 'returned' }
        case 'returned_to_nis':
          return { status: 'nis_vetting', vetting_type: 'nis', vetting_state: 'returned' }
        case 'approved':
          return { status: 'approved' }
        case 'denied':
          return { status: 'denied' }
        case 'all':
        default:
          return {}
      }
    case 'police':
      switch (view) {
        case 'queue':
          return { status: 'police_vetting', assigned_police_officer_id: userId, vetting_type: 'police', vetting_state: 'active' }
        case 'completed':
          return { status: 'police_completed', assigned_police_officer_id: userId }
        case 'returned':
          return { status: 'police_vetting', assigned_police_officer_id: userId, vetting_type: 'police', vetting_state: 'returned' }
        case 'assigned':
        default:
          return { assigned_police_officer_id: userId }
      }
    case 'nis':
      switch (view) {
        case 'queue':
          return { status: 'nis_vetting', assigned_nis_officer_id: userId, vetting_type: 'nis', vetting_state: 'active' }
        case 'completed':
          return { status: 'nis_completed', assigned_nis_officer_id: userId }
        case 'returned':
          return { status: 'nis_vetting', assigned_nis_officer_id: userId, vetting_type: 'nis', vetting_state: 'returned' }
        case 'assigned':
        default:
          return { assigned_nis_officer_id: userId }
      }
    case 'data_entry':
      switch (view) {
        case 'pending':
          return { status: 'pending' }
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
        case 'pending':
        case 'all':
        default:
          return {}
      }
  }
}

function getCurrentWorkspaceListTitle(): string {
  return getWorkspaceViewLabel(activeWorkspaceView.value)
}

function getCurrentWorkspaceListSubtitle(): string {
  switch (dashboardRole.value) {
    case 'admin':
      switch (activeWorkspaceView.value) {
        case 'pending_approval':
          return 'Applications waiting for final approval.'
        case 'returned_to_admin':
          return 'Applications returned from approval for further action.'
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
        case 'assigned':
        default:
          return 'All applications assigned to you.'
      }
    case 'nis':
      switch (activeWorkspaceView.value) {
        case 'queue':
          return 'Applications awaiting NIS vetting.'
        case 'completed':
          return 'Applications completed by NIS.'
        case 'returned':
          return 'Applications sent back by OPC for correction.'
        case 'assigned':
        default:
          return 'All applications assigned to you.'
      }
    case 'data_entry':
      switch (activeWorkspaceView.value) {
        case 'pending':
          return 'New submissions awaiting processing.'
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
          { key: 'all', label: 'All Applications', icon: 'mdi-view-list' },
          { key: 'pending_approval', label: 'Pending Approval', icon: 'mdi-badge-account' },
          { key: 'returned_to_admin', label: 'Returned to Admin', icon: 'mdi-arrow-u-left-bottom' },
          { key: 'returned_to_police', label: 'Returned to Police', icon: 'mdi-shield-check' },
          { key: 'returned_to_nis', label: 'Returned to NIS', icon: 'mdi-shield-account' },
          { key: 'approved', label: 'Approved', icon: 'mdi-check-circle' },
          { key: 'denied', label: 'Denied', icon: 'mdi-close-circle' }
        ],
        defaultWorkspaceView: 'all',
        workflowSubtitle: 'Monitor the complete application flow across every stage.',
        actionsTitle: 'Admin Actions',
        actionsSubtitle: 'Jump to the modules used most often by administrators.',
        recentTitle: 'Recent Applications',
        recentSubtitle: 'Latest submissions across the full register.',
        metrics: [
          { title: 'All Applications', value: totalApplications.value, icon: 'mdi-file-document-multiple', color: 'primary' },
          { title: 'Pending', value: getStatusCount('pending'), icon: 'mdi-clock-outline', color: 'warning' },
          { title: 'Returned to Admin', value: getDashboardCount('returned_to_admin'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
          { title: 'Returned to Police', value: getDashboardCount('returned_to_police'), icon: 'mdi-shield-check', color: 'primary' },
          { title: 'Returned to NIS', value: getDashboardCount('returned_to_nis'), icon: 'mdi-shield-account', color: 'primary' },
          { title: 'Police Vetting', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { title: 'NIS Vetting', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' }
        ],
        stages: [
          { label: 'Pending Approval', value: getStatusCount('pending_approval'), icon: 'mdi-badge-account', color: 'indigo' },
          { label: 'Approved', value: getStatusCount('approved'), icon: 'mdi-check-circle', color: 'success' },
          { label: 'Denied', value: getStatusCount('denied'), icon: 'mdi-close-circle', color: 'error' },
          { label: 'Returned to Admin', value: getDashboardCount('returned_to_admin'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
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
            title: 'Returned to Admin',
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
        description: 'Track assigned cases, completed vetting, and applications awaiting the next workflow step.',
        scope: 'Assigned cases',
        workspaceTitle: 'Police Workspace',
        workspaceSubtitle: 'Switch between your queue, completed cases, and returned applications.',
        workspaceTabs: [
          { key: 'queue', label: 'Queue', icon: 'mdi-shield-check' },
          { key: 'completed', label: 'Completed', icon: 'mdi-check-circle' },
          { key: 'returned', label: 'Returned by OPC', icon: 'mdi-arrow-u-left-bottom' },
          { key: 'assigned', label: 'All Assigned', icon: 'mdi-view-list' }
        ],
        defaultWorkspaceView: 'queue',
        workflowSubtitle: 'Follow the police vetting queue and completed cases.',
        actionsTitle: 'Police Actions',
        actionsSubtitle: 'Jump straight to the assigned vetting queue or application register.',
        recentTitle: 'Assigned Applications',
        recentSubtitle: 'Latest records visible to the police officer workspace.',
        metrics: [
          { title: 'Police Queue', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { title: 'Completed', value: getStatusCount('police_completed'), icon: 'mdi-check-circle', color: 'success' },
          { title: 'Returned by OPC', value: getDashboardCount('returned_to_police'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
          { title: 'Total Visible', value: totalApplications.value, icon: 'mdi-file-document-multiple', color: 'secondary' }
        ],
        stages: [
          { label: 'Police Vetting', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { label: 'Police Completed', value: getStatusCount('police_completed'), icon: 'mdi-checkbox-marked-circle', color: 'success' },
          { label: 'OPC Review', value: getStatusCount('opc_review'), icon: 'mdi-account-eye', color: 'purple' },
          { label: 'Returned by OPC', value: getDashboardCount('returned_to_police'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' }
        ],
        actions: [
          { title: 'Police Vetting Queue', icon: 'mdi-shield-check', color: 'primary', to: { name: 'PoliceVettingList' } },
          { title: 'Applications', icon: 'mdi-file-document-multiple', color: 'secondary', to: { name: 'Applications' } }
        ]
      }
    case 'nis':
      return {
        eyebrow: 'NIS Vetting',
        title: 'NIS Application Dashboard',
        description: 'Monitor assigned NIS cases and the transition into final approval.',
        scope: 'Assigned cases',
        workspaceTitle: 'NIS Workspace',
        workspaceSubtitle: 'Switch between your queue, completed cases, and returned applications.',
        workspaceTabs: [
          { key: 'queue', label: 'Queue', icon: 'mdi-shield-account' },
          { key: 'completed', label: 'Completed', icon: 'mdi-check-circle' },
          { key: 'returned', label: 'Returned by OPC', icon: 'mdi-arrow-u-left-bottom' },
          { key: 'assigned', label: 'All Assigned', icon: 'mdi-view-list' }
        ],
        defaultWorkspaceView: 'queue',
        workflowSubtitle: 'Follow the NIS vetting queue and completed cases.',
        actionsTitle: 'NIS Actions',
        actionsSubtitle: 'Move directly to the assigned vetting queue or register.',
        recentTitle: 'Assigned Applications',
        recentSubtitle: 'Latest records visible to the NIS officer workspace.',
        metrics: [
          { title: 'NIS Queue', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' },
          { title: 'Completed', value: getStatusCount('nis_completed'), icon: 'mdi-check-circle', color: 'success' },
          { title: 'Returned by OPC', value: getDashboardCount('returned_to_nis'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
          { title: 'Total Visible', value: totalApplications.value, icon: 'mdi-file-document-multiple', color: 'secondary' }
        ],
        stages: [
          { label: 'NIS Vetting', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' },
          { label: 'NIS Completed', value: getStatusCount('nis_completed'), icon: 'mdi-checkbox-marked-circle', color: 'success' },
          { label: 'OPC Review', value: getStatusCount('opc_review'), icon: 'mdi-account-eye', color: 'purple' },
          { label: 'Returned by OPC', value: getDashboardCount('returned_to_nis'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' }
        ],
        actions: [
          { title: 'NIS Vetting Queue', icon: 'mdi-shield-account', color: 'primary', to: { name: 'NisVettingList' } },
          { title: 'Applications', icon: 'mdi-file-document-multiple', color: 'secondary', to: { name: 'Applications' } }
        ]
      }
    case 'data_entry':
      return {
        eyebrow: 'Data Entry',
        title: 'Application Dashboard',
        description: 'Track submissions, corrections, and the full application register from the data-entry workspace.',
        scope: 'All applications',
        workspaceTitle: 'Data Entry Workspace',
        workspaceSubtitle: 'Switch between pending, returned, and vetting-stage applications.',
        workspaceTabs: [
          { key: 'all', label: 'All Applications', icon: 'mdi-view-list' },
          { key: 'pending', label: 'Pending', icon: 'mdi-clock-outline' },
          { key: 'returned', label: 'Returned', icon: 'mdi-alert-circle' },
          { key: 'police_vetting', label: 'Police Vetting', icon: 'mdi-shield-check' },
          { key: 'nis_vetting', label: 'NIS Vetting', icon: 'mdi-shield-account' }
        ],
        defaultWorkspaceView: 'pending',
        workflowSubtitle: 'Watch applications move from entry into vetting and approval.',
        actionsTitle: 'Data Entry Actions',
        actionsSubtitle: 'Create a new application or open the register to continue work.',
        recentTitle: 'Recent Applications',
        recentSubtitle: 'Latest records entered or corrected in the system.',
        metrics: [
          { title: 'All Applications', value: totalApplications.value, icon: 'mdi-file-document-multiple', color: 'primary' },
          { title: 'Pending', value: getStatusCount('pending'), icon: 'mdi-clock-outline', color: 'warning' },
          { title: 'Returned', value: getStatusCount('returned_to_data_entry'), icon: 'mdi-alert-circle', color: 'error' },
          { title: 'In Vetting', value: getDashboardCount('police_vetting_active') + getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-search', color: 'info' }
        ],
        stages: [
          { label: 'Pending', value: getStatusCount('pending'), icon: 'mdi-clock-outline', color: 'warning' },
          { label: 'Returned to Data Entry', value: getStatusCount('returned_to_data_entry'), icon: 'mdi-arrow-u-left-bottom', color: 'error' },
          { label: 'Police Vetting', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { label: 'NIS Vetting', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' }
        ],
        actions: [
          { title: 'Create Application', icon: 'mdi-file-document-plus', color: 'primary', to: { name: 'CreateApplication' } },
          { title: 'Applications', icon: 'mdi-file-document-multiple', color: 'secondary', to: { name: 'Applications' } }
        ]
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
          { key: 'all', label: 'All Applications', icon: 'mdi-view-list' },
          { key: 'pending', label: 'Pending', icon: 'mdi-clock-outline' },
          { key: 'returned', label: 'Returned', icon: 'mdi-arrow-u-left-bottom' },
          { key: 'approved', label: 'Approved', icon: 'mdi-check-circle' },
          { key: 'denied', label: 'Denied', icon: 'mdi-close-circle' }
        ],
        defaultWorkspaceView: 'all',
        workflowSubtitle: 'Track the main workflow stages.',
        actionsTitle: 'Quick Actions',
        actionsSubtitle: 'Open the primary workspace modules.',
        recentTitle: 'Recent Applications',
        recentSubtitle: 'Latest submissions and status updates.',
        metrics: [
          { title: 'Total Applications', value: totalApplications.value, icon: 'mdi-file-document', color: 'primary' },
          { title: 'Pending', value: getStatusCount('pending'), icon: 'mdi-clock', color: 'warning' },
          { title: 'Returned', value: getStatusCount('returned_to_data_entry'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
          { title: 'Approved', value: getStatusCount('approved'), icon: 'mdi-check-circle', color: 'success' },
          { title: 'Denied', value: getStatusCount('denied'), icon: 'mdi-close-circle', color: 'error' }
        ],
        stages: [
          { label: 'Police Vetting', value: getDashboardCount('police_vetting_active'), icon: 'mdi-shield-check', color: 'primary' },
          { label: 'NIS Vetting', value: getDashboardCount('nis_vetting_active'), icon: 'mdi-shield-account', color: 'primary' },
          { label: 'Pending Approval', value: getStatusCount('pending_approval'), icon: 'mdi-badge-account', color: 'indigo' },
          { label: 'Returned', value: getStatusCount('returned_to_data_entry'), icon: 'mdi-arrow-u-left-bottom', color: 'warning' },
          { label: 'Approved', value: getStatusCount('approved'), icon: 'mdi-check-circle', color: 'success' }
        ],
        actions: [
          { title: 'Applications', icon: 'mdi-file-document-multiple', color: 'primary', to: { name: 'Applications' } }
        ]
      }
  }
})

const summaryCards = computed(() => dashboardProfile.value.metrics)
const workflowStages = computed(() => dashboardProfile.value.stages)
const workspaceTabs = computed(() => dashboardProfile.value.workspaceTabs)

function getStatusColor(statusCode?: string) {
  const colors: Record<string, string> = {
    pending: 'warning',
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

function getApproverListParams() {
  return {
    page: approverPagination.current_page,
    per_page: approverPagination.per_page,
    order_by: approverSortBy.value[0]?.key || 'created_at',
    order_dir: approverSortBy.value[0]?.order || 'desc',
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
    const response = await applicationsApi.list({
      page: workspacePagination.current_page,
      per_page: workspacePagination.per_page,
      order_by: sortBy[0]?.key || 'created_at',
      order_dir: sortBy[0]?.order || 'desc',
      ...getWorkspaceViewParams(activeWorkspaceView.value)
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

function handleAction(action: DashboardAction) {
  if (action.scrollTo) {
    document.getElementById(action.scrollTo)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    return
  }

  if (action.to) {
    router.push(action.to)
  }
}

function openApplicationDetails(id: number) {
  router.push({
    name: 'ApplicationDetail',
    params: { id },
    query: { returnTo: route.fullPath }
  })
}

async function fetchDashboardData() {
  loading.value = true
  try {
    if (dashboardRole.value === 'approver') {
      const userId = authStore.user?.id
      const response = await reportsApi.dashboard({ assigned_opc_approver_id: userId })
      if (response.data.success) {
        dashboardData.value = response.data.data
      }
      return
    }

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

async function fetchApproverApplications(sortBy: Array<{ key: string; order?: 'asc' | 'desc' }> = approverSortBy.value) {
  if (dashboardRole.value !== 'approver') {
    return
  }

  approverLoading.value = true
  try {
    approverSortBy.value = sortBy
    const response = await applicationsApi.list({
      page: approverPagination.current_page,
      per_page: approverPagination.per_page,
      order_by: sortBy[0]?.key || 'created_at',
      order_dir: sortBy[0]?.order || 'desc',
      ...getApproverViewParams(activeApproverView.value)
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
  await fetchDashboardData()
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

.metric-card--clickable {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.metric-card--clickable:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(18, 56, 95, 0.14);
}

.metric-card--active {
  border: 2px solid rgba(18, 56, 95, 0.28);
  box-shadow: 0 10px 26px rgba(18, 56, 95, 0.16);
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
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.dashboard-tabs {
  flex-wrap: wrap;
  gap: 8px;
}

.dashboard-tabs__pill {
  border-radius: 999px;
  text-transform: none;
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
}
</style>
