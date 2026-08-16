<template>
  <v-container class="gov-page">
    <v-row>
      <v-col cols="12">
        <v-sheet class="gov-hero">
          <div class="gov-hero__inner">
            <div>
              <div class="gov-hero__eyebrow">Administration</div>
              <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">Admin Panel</h1>
              <div class="text-body-2 text-medium-emphasis">
                Manage users, workflows, and master data from one controlled interface.
              </div>
            </div>
          </div>
        </v-sheet>

        <v-card class="gov-card" elevation="2">
          <v-tabs v-model="activeTab" bg-color="primary" slider-color="accent" class="gov-tabs" show-arrows>
          <v-tab value="users">Users</v-tab>
          <v-tab value="institutions">Institutions</v-tab>
          <v-tab value="statuses">Application Statuses</v-tab>
          <v-tab value="name-change-reasons">Name Change Reasons</v-tab>
          <v-tab value="reject-reasons">Reject Reasons</v-tab>
          <v-tab value="vetting-types">Vetting Types</v-tab>
          <v-tab value="document-types">Document Types</v-tab>
          <v-tab value="roles">Roles & Permissions</v-tab>
        </v-tabs>

        <v-window v-model="activeTab" class="pa-4">
          <!-- Users Tab -->
          <v-window-item value="users">
            <UsersManagement />
          </v-window-item>

          <!-- Institutions Tab -->
          <v-window-item value="institutions">
            <ConfigManagement
              title="Institutions"
              :items="institutions"
              :loading="loading"
              :headers="institutionHeaders"
              @create="handleCreateInstitution"
              @update="handleUpdateInstitution"
              @delete="handleDeleteInstitution"
              @refresh="loadInstitutions"
            />
          </v-window-item>

          <!-- Application Statuses Tab -->
          <v-window-item value="statuses">
            <ConfigManagement
              title="Application Statuses"
              :items="applicationStatuses"
              :loading="loading"
              :headers="statusHeaders"
              @create="handleCreateStatus"
              @update="handleUpdateStatus"
              @delete="handleDeleteStatus"
              @refresh="loadApplicationStatuses"
            />
          </v-window-item>

          <!-- Name Change Reasons Tab -->
          <v-window-item value="name-change-reasons">
            <ConfigManagement
              title="Name Change Reasons"
              :items="nameChangeReasons"
              :loading="loading"
              :headers="nameChangeReasonHeaders"
              :show-order="true"
              @create="handleCreateNameChangeReason"
              @update="handleUpdateNameChangeReason"
              @delete="handleDeleteNameChangeReason"
              @refresh="loadNameChangeReasons"
            />
          </v-window-item>

          <!-- Reject Reasons Tab -->
          <v-window-item value="reject-reasons">
            <ConfigManagement
              title="Reject Reasons"
              :items="rejectReasons"
              :loading="loading"
              :headers="rejectReasonHeaders"
              :show-order="true"
              @create="handleCreateRejectReason"
              @update="handleUpdateRejectReason"
              @delete="handleDeleteRejectReason"
              @refresh="loadRejectReasons"
            />
          </v-window-item>

          <!-- Vetting Types Tab -->
          <v-window-item value="vetting-types">
            <ConfigManagement
              title="Vetting Types"
              :items="vettingTypes"
              :loading="loading"
              :headers="vettingTypeHeaders"
              @create="handleCreateVettingType"
              @update="handleUpdateVettingType"
              @delete="handleDeleteVettingType"
              @refresh="loadVettingTypes"
            />
          </v-window-item>

          <!-- Document Types Tab -->
          <v-window-item value="document-types">
            <ConfigManagement
              title="Document Types"
              :items="documentTypes"
              :loading="loading"
              :headers="documentTypeHeaders"
              @create="handleCreateDocumentType"
              @update="handleUpdateDocumentType"
              @delete="handleDeleteDocumentType"
              @refresh="loadDocumentTypes"
            />
          </v-window-item>

          <!-- Roles Tab -->
          <v-window-item value="roles">
            <RolesManagement />
          </v-window-item>
        </v-window>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>
.gov-tabs {
  border-bottom: 1px solid rgba(18, 56, 95, 0.08);
  overflow-x: auto;
  white-space: nowrap;
}
</style>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { adminApi, type Institution, type ApplicationStatus, type NameChangeReason, type RejectReason, type VettingType, type DocumentType } from '@/api/admin'
import UsersManagement from '@/components/admin/UsersManagement.vue'
import ConfigManagement from '@/components/admin/ConfigManagement.vue'
import RolesManagement from '@/components/admin/RolesManagement.vue'

const toast = useToast()
const activeTab = ref('users')
const loading = ref(false)

const institutions = ref<Institution[]>([])
const applicationStatuses = ref<ApplicationStatus[]>([])
const nameChangeReasons = ref<NameChangeReason[]>([])
const rejectReasons = ref<RejectReason[]>([])
const vettingTypes = ref<VettingType[]>([])
const documentTypes = ref<DocumentType[]>([])

const institutionHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Active', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const statusHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Order', key: 'order' },
  { title: 'Active', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const nameChangeReasonHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Order', key: 'order' },
  { title: 'Active', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const rejectReasonHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Order', key: 'order' },
  { title: 'Active', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const vettingTypeHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Active', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const documentTypeHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Max Size (MB)', key: 'max_size_mb' },
  { title: 'Active', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Institutions
async function loadInstitutions() {
  loading.value = true
  try {
    const response = await adminApi.getInstitutions()
    if (response.data.success) {
      institutions.value = response.data.data || []
    }
  } catch (err: any) {
    toast.error('Failed to load institutions')
  } finally {
    loading.value = false
  }
}

async function handleCreateInstitution(data: any) {
  try {
    const response = await adminApi.createInstitution(data)
    if (response.data.success) {
      toast.success('Institution created successfully')
      await loadInstitutions()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to create institution')
  }
}

async function handleUpdateInstitution(id: number, data: any) {
  try {
    const response = await adminApi.updateInstitution(id, data)
    if (response.data.success) {
      toast.success('Institution updated successfully')
      await loadInstitutions()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to update institution')
  }
}

async function handleDeleteInstitution(id: number) {
  try {
    const response = await adminApi.deleteInstitution(id)
    if (response.data.success) {
      toast.success('Institution deleted successfully')
      await loadInstitutions()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to delete institution')
  }
}

// Application Statuses
async function loadApplicationStatuses() {
  loading.value = true
  try {
    const response = await adminApi.getApplicationStatuses()
    if (response.data.success) {
      applicationStatuses.value = response.data.data || []
    }
  } catch (err: any) {
    toast.error('Failed to load application statuses')
  } finally {
    loading.value = false
  }
}

async function handleCreateStatus(data: any) {
  try {
    const response = await adminApi.createApplicationStatus(data)
    if (response.data.success) {
      toast.success('Status created successfully')
      await loadApplicationStatuses()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to create status')
  }
}

async function handleUpdateStatus(id: number, data: any) {
  try {
    const response = await adminApi.updateApplicationStatus(id, data)
    if (response.data.success) {
      toast.success('Status updated successfully')
      await loadApplicationStatuses()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to update status')
  }
}

async function handleDeleteStatus(id: number) {
  try {
    const response = await adminApi.deleteApplicationStatus(id)
    if (response.data.success) {
      toast.success('Status deleted successfully')
      await loadApplicationStatuses()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to delete status')
  }
}

// Name Change Reasons
async function loadNameChangeReasons() {
  loading.value = true
  try {
    const response = await adminApi.getNameChangeReasons()
    if (response.data.success) {
      nameChangeReasons.value = response.data.data || []
    }
  } catch (err: any) {
    toast.error('Failed to load name change reasons')
  } finally {
    loading.value = false
  }
}

async function handleCreateNameChangeReason(data: any) {
  try {
    const response = await adminApi.createNameChangeReason(data)
    if (response.data.success) {
      toast.success('Name change reason created successfully')
      await loadNameChangeReasons()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to create reason')
  }
}

async function handleUpdateNameChangeReason(id: number, data: any) {
  try {
    const response = await adminApi.updateNameChangeReason(id, data)
    if (response.data.success) {
      toast.success('Name change reason updated successfully')
      await loadNameChangeReasons()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to update reason')
  }
}

async function handleDeleteNameChangeReason(id: number) {
  try {
    const response = await adminApi.deleteNameChangeReason(id)
    if (response.data.success) {
      toast.success('Name change reason deleted successfully')
      await loadNameChangeReasons()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to delete reason')
  }
}

// Reject Reasons
async function loadRejectReasons() {
  loading.value = true
  try {
    const response = await adminApi.getRejectReasons()
    if (response.data.success) {
      rejectReasons.value = response.data.data || []
    }
  } catch (err: any) {
    toast.error('Failed to load reject reasons')
  } finally {
    loading.value = false
  }
}

async function handleCreateRejectReason(data: any) {
  try {
    const response = await adminApi.createRejectReason(data)
    if (response.data.success) {
      toast.success('Reject reason created successfully')
      await loadRejectReasons()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to create reject reason')
  }
}

async function handleUpdateRejectReason(id: number, data: any) {
  try {
    const response = await adminApi.updateRejectReason(id, data)
    if (response.data.success) {
      toast.success('Reject reason updated successfully')
      await loadRejectReasons()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to update reject reason')
  }
}

async function handleDeleteRejectReason(id: number) {
  try {
    const response = await adminApi.deleteRejectReason(id)
    if (response.data.success) {
      toast.success('Reject reason deleted successfully')
      await loadRejectReasons()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to delete reject reason')
  }
}

// Vetting Types
async function loadVettingTypes() {
  loading.value = true
  try {
    const response = await adminApi.getVettingTypes()
    if (response.data.success) {
      vettingTypes.value = response.data.data || []
    }
  } catch (err: any) {
    toast.error('Failed to load vetting types')
  } finally {
    loading.value = false
  }
}

async function handleCreateVettingType(data: any) {
  try {
    const response = await adminApi.createVettingType(data)
    if (response.data.success) {
      toast.success('Vetting type created successfully')
      await loadVettingTypes()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to create vetting type')
  }
}

async function handleUpdateVettingType(id: number, data: any) {
  try {
    const response = await adminApi.updateVettingType(id, data)
    if (response.data.success) {
      toast.success('Vetting type updated successfully')
      await loadVettingTypes()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to update vetting type')
  }
}

async function handleDeleteVettingType(id: number) {
  try {
    const response = await adminApi.deleteVettingType(id)
    if (response.data.success) {
      toast.success('Vetting type deleted successfully')
      await loadVettingTypes()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to delete vetting type')
  }
}

// Document Types
async function loadDocumentTypes() {
  loading.value = true
  try {
    const response = await adminApi.getDocumentTypes()
    if (response.data.success) {
      documentTypes.value = response.data.data || []
    }
  } catch (err: any) {
    toast.error('Failed to load document types')
  } finally {
    loading.value = false
  }
}

async function handleCreateDocumentType(data: any) {
  try {
    const response = await adminApi.createDocumentType(data)
    if (response.data.success) {
      toast.success('Document type created successfully')
      await loadDocumentTypes()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to create document type')
  }
}

async function handleUpdateDocumentType(id: number, data: any) {
  try {
    const response = await adminApi.updateDocumentType(id, data)
    if (response.data.success) {
      toast.success('Document type updated successfully')
      await loadDocumentTypes()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to update document type')
  }
}

async function handleDeleteDocumentType(id: number) {
  try {
    const response = await adminApi.deleteDocumentType(id)
    if (response.data.success) {
      toast.success('Document type deleted successfully')
      await loadDocumentTypes()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to delete document type')
  }
}

onMounted(async () => {
  await Promise.all([
    loadInstitutions(),
    loadApplicationStatuses(),
    loadNameChangeReasons(),
    loadRejectReasons(),
    loadVettingTypes(),
    loadDocumentTypes()
  ])
})
</script>
