<template>
  <div class="gov-page">
    <v-sheet class="gov-hero">
      <div class="gov-hero__inner">
        <div>
          <div class="gov-hero__eyebrow">Case entry</div>
          <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">
            {{ isEditMode ? 'Edit Application' : 'Create Application' }}
          </h1>
          <div class="text-body-2 text-medium-emphasis">
            Maintain complete and accurate records before they move into vetting.
          </div>
        </div>
        <v-btn
          class="gov-hero__action"
          variant="tonal"
          prepend-icon="mdi-arrow-left"
          @click="handleCancel"
        >
          {{ isEditMode ? 'Back to Application' : 'Back to List' }}
        </v-btn>
      </div>
    </v-sheet>

    <v-card class="gov-card" elevation="2">
      <v-card-title class="gov-card__title">
        <div>
          <div class="text-h6">Application Details</div>
          <div class="text-caption text-medium-emphasis">Capture the personal and location information required for processing</div>
        </div>
      </v-card-title>
      <v-card-text>
        <v-progress-linear
          v-if="loadingApplication"
          indeterminate
          color="primary"
          class="mb-4"
        ></v-progress-linear>
        <v-alert
          v-else-if="isEditMode && isEditLocked"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          This application has been assigned to an officer and cannot be edited.
        </v-alert>
        <v-form ref="formRef" v-model="valid" @submit.prevent="handleSubmit" v-else>
          <v-row>
            <v-col cols="12" v-if="isEditMode && applicationData">
              <v-text-field
                :model-value="applicationData.application_number"
                label="Application Number"
                variant="outlined"
                readonly
                disabled
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.full_name"
                label="Current Full Name *"
                :rules="fullNameRules"
                variant="outlined"
                :disabled="isEditLocked"
                required
                hint="Only letters and spaces allowed"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.national_id"
                label="National ID *"
                :rules="nationalIdRules"
                variant="outlined"
                :disabled="isEditLocked"
                required
                hint="8 characters, uppercase letters and numbers only (e.g., ABC12345)"
                persistent-hint
                @input="form.national_id = form.national_id.toUpperCase()"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.date_of_birth"
                label="Date of Birth"
                type="date"
                :rules="dateOfBirthRules"
                variant="outlined"
                :disabled="isEditLocked"
                hint="Optional: select the applicant's date of birth"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.phone_number"
                label="Phone Number"
                :rules="phoneNumberRules"
                variant="outlined"
                :disabled="isEditLocked"
                hint="Optional: digits, spaces, plus signs, hyphens, and parentheses only"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="4">
              <v-text-field
                v-model="form.district"
                label="District *"
                :rules="districtRules"
                variant="outlined"
                :disabled="isEditLocked"
                required
                hint="Enter the district"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="4">
              <v-text-field
                v-model="form.traditional_authority"
                label="T/A *"
                :rules="traditionalAuthorityRules"
                variant="outlined"
                :disabled="isEditLocked"
                required
                hint="Traditional authority"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="4">
              <v-text-field
                v-model="form.village"
                label="Village *"
                :rules="villageRules"
                variant="outlined"
                :disabled="isEditLocked"
                required
                hint="Enter the village"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.requested_name"
                label="Requested Full Name *"
                :rules="requestedNameRules"
                variant="outlined"
                :disabled="isEditLocked"
                required
                hint="Must be different from current full name"
                persistent-hint
              />
            </v-col>

            <v-col cols="12">
              <v-card variant="outlined" class="pa-4">
                <div class="text-subtitle-1 mb-4">Reason for Change *</div>
                <v-radio-group
                  v-model="form.reason_id"
                  :rules="reasonRules"
                  :disabled="isEditLocked"
                >
                  <div v-if="reasonOptions.length" class="reason-list">
                    <label
                      v-for="reason in reasonOptions"
                      :key="reason.id"
                      class="reason-choice"
                    >
                      <v-radio
                        :value="reason.id"
                        class="reason-choice__radio"
                      />
                      <span class="reason-choice__label">{{ reason.name }}</span>
                    </label>
                  </div>
                  <v-alert
                    v-else
                    type="info"
                    variant="tonal"
                    density="compact"
                  >
                    No active name change reasons are available. Ask an administrator to configure them first.
                  </v-alert>
                </v-radio-group>
              </v-card>
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <v-row>
            <v-col cols="12">
              <v-card variant="outlined">
                <v-card-title class="text-subtitle-1">Supporting Documents</v-card-title>
                <v-card-text>
                  <!-- Existing Documents (Edit Mode) -->
                  <div v-if="isEditMode && existingDocuments.length > 0" class="mb-4">
                    <v-list density="compact">
                      <v-list-subheader>Existing Documents ({{ existingDocuments.length }})</v-list-subheader>
                      <v-list-item
                        v-for="doc in existingDocuments"
                        :key="doc.id"
                        :prepend-icon="getDocumentIcon(doc.mime_type)"
                        :title="doc.file_name"
                        :subtitle="formatFileSize(doc.file_size)"
                      >
                        <template v-slot:append>
                          <v-btn
                            icon="mdi-eye"
                            size="small"
                            variant="text"
                            @click="previewDocument(doc)"
                          />
                          <v-btn
                            icon="mdi-download"
                            size="small"
                            variant="text"
                            @click="downloadDocument(doc.id)"
                          />
                          <v-btn
                            icon="mdi-delete"
                            size="small"
                            variant="text"
                            color="error"
                            :loading="deletingDocumentId === doc.id"
                            @click="deleteDocument(doc.id)"
                          />
                        </template>
                      </v-list-item>
                    </v-list>
                    <v-divider class="my-4" />
                  </div>

                  <!-- Upload New Documents -->
                  <div class="mb-2">
                    <label class="text-body-2 text-medium-emphasis mb-1 d-block">
                      {{ isEditMode ? 'Upload Additional Documents (Optional)' : 'Upload Documents (Optional)' }}
                      <span class="text-caption ml-1">Unlimited files, 50MB each (PDF, JPG, PNG)</span>
                    </label>
                    <input
                      ref="fileInputRef"
                      type="file"
                      multiple
                      accept=".pdf,.jpg,.jpeg,.png"
                      :disabled="loading || uploadingDocuments"
                      class="d-none"
                      @change="handleFileInputChange"
                    />
                    <v-btn
                      color="primary"
                      variant="outlined"
                      prepend-icon="mdi-paperclip"
                      :disabled="loading || uploadingDocuments"
                      @click="triggerFileInput"
                    >
                      Select Files
                    </v-btn>
                    <span v-if="validFiles.length > 0" class="ml-2 text-caption">
                      {{ validFiles.length }} files selected
                    </span>
                  </div>

                  <!-- Document Type (Edit Mode Only) -->
                  <v-select
                    v-if="isEditMode && validFiles.length > 0"
                    v-model="selectedDocumentType"
                    :items="documentTypes"
                    item-title="name"
                    item-value="id"
                    label="Document Type *"
                    variant="outlined"
                    class="mt-4"
                    required
                  />

                  <v-alert
                    v-if="fileErrors.length > 0"
                    type="error"
                    density="compact"
                    class="mt-2"
                  >
                    <ul class="mb-0 pl-4">
                      <li v-for="error in fileErrors" :key="error">{{ error }}</li>
                    </ul>
                  </v-alert>

                  <v-list v-if="validFiles.length > 0" class="mt-4">
                    <v-list-subheader>New Files to Upload ({{ validFiles.length }})</v-list-subheader>
                    <v-list-item
                      v-for="(file, index) in validFiles"
                      :key="index"
                      :title="file.name"
                      :subtitle="formatFileSize(file.size)"
                    >
                      <template v-slot:prepend>
                        <v-icon :color="getFileIconColor(file.name)">{{ getFileIcon(file.name) }}</v-icon>
                      </template>
                      <template v-slot:append>
                        <v-btn
                          icon="mdi-close"
                          size="small"
                          variant="text"
                          @click="removeFile(index)"
                        />
                      </template>
                    </v-list-item>
                  </v-list>

                  <!-- Upload Button for Edit Mode -->
                  <v-btn
                    v-if="isEditMode && validFiles.length > 0"
                    color="primary"
                    variant="tonal"
                    prepend-icon="mdi-upload"
                    :disabled="!selectedDocumentType || uploadingDocuments"
                    :loading="uploadingDocuments"
                    class="mt-4"
                    @click="uploadDocuments"
                  >
                    Upload {{ validFiles.length }} File(s)
                  </v-btn>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <v-divider class="my-4" />

          <div class="form-actions">
            <v-btn
              class="form-actions__button"
              variant="text"
              @click="handleCancel"
            >
              Cancel
            </v-btn>
            <v-btn
              class="form-actions__button"
              type="submit"
              color="primary"
              :loading="loading"
              :disabled="loading || isEditLocked"
            >
              {{ isEditMode ? 'Update Application' : 'Create Application' }}
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { applicationsApi, type Application } from '@/api/applications'
import { documentsApi, type Document } from '@/api/documents'
import { adminApi, type NameChangeReason } from '@/api/admin'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const formRef = ref()
const fileInputRef = ref<HTMLInputElement | null>(null)
const valid = ref(false)
const loading = ref(false)
const loadingApplication = ref(false)
const applicationData = ref<Application | null>(null)
const existingDocuments = ref<Document[]>([])
const selectedFiles = ref<File[]>([])
const validFiles = ref<File[]>([])
const fileErrors = ref<string[]>([])
const uploadingDocuments = ref(false)
const deletingDocumentId = ref<number | null>(null)
const documentTypes = ref<Array<{ id: number; name: string }>>([])
const selectedDocumentType = ref<number | null>(null)

const reasonOptions = ref<NameChangeReason[]>([])

// Preview dialog state (used by previewDocument/closePreview)
const showPreviewDialog = ref(false)
const previewLoading = ref(false)
const previewUrl = ref<string | null>(null)
const previewDocumentData = ref<any>(null)

const applicationId = computed(() => {
  return route.params.id ? parseInt(route.params.id as string) : null
})

const isEditMode = computed(() => !!applicationId.value)

const canEditLoadedApplication = computed(() => {
  return applicationData.value?.allowed_actions?.includes('edit_application') ?? false
})

const isEditLocked = computed(() => {
  if (!applicationData.value) return false
  return !canEditLoadedApplication.value
})

function getDashboardRedirectView(statusCode?: string | null): string {
  const normalizedStatus = statusCode?.toLowerCase() ?? ''

  if (authStore.isAdmin) {
    if (normalizedStatus === 'returned_to_admin' || normalizedStatus === 'opc_review') return 'returned_to_admin'
    if (normalizedStatus === 'returned_to_police') return 'returned_to_police'
    if (normalizedStatus === 'returned_to_nis') return 'returned_to_nis'
    if (normalizedStatus === 'pending_approval') return 'pending_approval'
    if (normalizedStatus === 'approved') return 'approved'
    if (normalizedStatus === 'denied') return 'denied'
    return 'all'
  }

  if (authStore.hasAnyRole(['opc_approver'])) {
    if (normalizedStatus === 'approved') return 'approved'
    if (normalizedStatus === 'denied') return 'denied_by_me'
    if (normalizedStatus === 'returned_to_admin' || normalizedStatus === 'opc_review') return 'returned_to_admin'
    if (normalizedStatus === 'returned_to_police') return 'returned_to_police'
    if (normalizedStatus === 'returned_to_nis') return 'returned_to_nis'
    return 'pending_approval'
  }

  if (authStore.hasAnyRole(['police_officer'])) {
    if (normalizedStatus === 'police_completed') return 'completed'
    if (normalizedStatus === 'returned_to_police') return 'returned'
    if (normalizedStatus === 'police_vetting') return 'queue'
    return 'assigned'
  }

  if (authStore.hasAnyRole(['nis_officer'])) {
    if (normalizedStatus === 'nis_completed') return 'completed'
    if (normalizedStatus === 'returned_to_nis') return 'returned'
    if (normalizedStatus === 'nis_vetting') return 'queue'
    return 'assigned'
  }

  if (authStore.hasAnyRole(['opc_data_entry'])) {
    if (normalizedStatus === 'returned_to_data_entry') return 'returned'
    if (normalizedStatus === 'police_vetting') return 'police_vetting'
    if (normalizedStatus === 'nis_vetting') return 'nis_vetting'
    if (normalizedStatus === 'pending_approval' || normalizedStatus === 'pending') return 'pending'
    return 'all'
  }

  if (normalizedStatus === 'returned_to_data_entry') return 'returned'
  if (normalizedStatus === 'approved') return 'approved'
  if (normalizedStatus === 'denied') return 'denied'
  return 'all'
}

function redirectToDashboard(statusCode?: string | null) {
  router.push({
    name: 'Dashboard',
    query: { view: getDashboardRedirectView(statusCode) }
  })
}

const form = reactive({
  full_name: '',
  national_id: '',
  date_of_birth: '',
  phone_number: '',
  district: '',
  traditional_authority: '',
  village: '',
  requested_name: '',
  reason_id: null as number | null,
  reason: ''
})

// Validation rules matching backend
const fullNameRules = [
  (v: string) => !!v || 'Full name is required',
  (v: string) => (v && v.length >= 2) || 'Full name must be at least 2 characters',
  (v: string) => (v && v.length <= 255) || 'Full name must not exceed 255 characters',
  (v: string) => /^[a-zA-Z\s]+$/.test(v) || 'Full name must contain only letters and spaces'
]

const nationalIdRules = [
  (v: string) => {
    if (!v) return 'National ID is required'
    if (v.length !== 8) return 'National ID must be exactly 8 characters'
    if (!/^[A-Z0-9]+$/.test(v)) return 'National ID must contain only uppercase letters and numbers'
    return true
  }
]

const dateOfBirthRules = [
  (v: string) => {
    if (!v) return true
    if (new Date(v) >= new Date(new Date().toDateString())) {
      return 'Date of birth must be a past date'
    }
    return true
  }
]

const phoneNumberRules = [
  (v: string) => {
    if (!v) return true
    if (!/^[0-9+\-\s()]+$/.test(v)) {
      return 'Phone number may contain only digits, spaces, plus signs, hyphens, and parentheses'
    }
    if (v.length > 25) {
      return 'Phone number must not exceed 25 characters'
    }
    return true
  }
]

const locationRules = (label: string) => [
  (v: string) => !!v || `${label} is required`,
  (v: string) => (v && v.length <= 255) || `${label} must not exceed 255 characters`
]

const districtRules = locationRules('District')
const traditionalAuthorityRules = locationRules('T/A')
const villageRules = locationRules('Village')

const requestedNameRules = [
  (v: string) => !!v || 'Requested name is required',
  (v: string) => (v && v.length >= 2) || 'Requested name must be at least 2 characters',
  (v: string) => (v && v.length <= 255) || 'Requested name must not exceed 255 characters',
  (v: string) => {
    if (form.full_name && v === form.full_name) {
      return 'Requested name must be different from current full name'
    }
    return true
  }
]

const reasonRules = [
  (v: number | null) => !!v || !!form.reason || 'Reason for change is required',
  (v: number | null) => {
    if (!v) return !!form.reason || 'Reason for change is required'
    return reasonOptions.value.some(option => option.id === Number(v)) || 'Select a valid reason for change'
  }
]

const fileRules = [
  (files: File[]) => {
    if (!files || files.length === 0) return true
    return true
  }
]

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

function getFileIcon(fileName: string): string {
  const ext = fileName.split('.').pop()?.toLowerCase()
  if (ext === 'pdf') return 'mdi-file-pdf-box'
  if (['jpg', 'jpeg', 'png'].includes(ext || '')) return 'mdi-file-image'
  return 'mdi-file-document'
}

function getFileIconColor(fileName: string): string {
  const ext = fileName.split('.').pop()?.toLowerCase()
  if (ext === 'pdf') return 'error'
  if (['jpg', 'jpeg', 'png'].includes(ext || '')) return 'primary'
  return 'grey'
}

function validateFile(file: File): string | null {
  // Check file size (50MB = 50 * 1024 * 1024 bytes)
  const maxSize = 50 * 1024 * 1024
  if (file.size > maxSize) {
    return `${file.name}: File size exceeds 50MB`
  }

  // Check file type
  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
  const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png']
  const fileExtension = file.name.split('.').pop()?.toLowerCase()

  if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension || '')) {
    return `${file.name}: File type not allowed. Only PDF, JPG, JPEG, PNG are allowed`
  }

  return null
}

function triggerFileInput() {
  fileInputRef.value?.click()
}

function handleFileInputChange(event: Event) {
  const target = event.target as HTMLInputElement
  const files = target.files

  if (!files || files.length === 0) {
    return
  }

  fileErrors.value = []

  // Convert FileList to Array
  const newFiles = Array.from(files)

  // Validate and add new files
  newFiles.forEach((file) => {
    // Check for duplicates
    const isDuplicate = validFiles.value.some(existingFile =>
      existingFile.name === file.name && existingFile.size === file.size
    )

    if (isDuplicate) {
      fileErrors.value.push(`${file.name}: This file is already selected.`)
      return
    }

    const error = validateFile(file)
    if (error) {
      fileErrors.value.push(error)
    } else {
      validFiles.value.push(file)
    }
  })

  // Update selectedFiles to match validFiles
  selectedFiles.value = [...validFiles.value]

  // Reset input to allow selecting the same files again if needed
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function removeFile(index: number) {
  validFiles.value.splice(index, 1)
  selectedFiles.value = [...validFiles.value]
  fileErrors.value = [] // Clear errors when removing files
}

async function loadApplication() {
  if (!applicationId.value) return

  loadingApplication.value = true
  try {
    const response = await applicationsApi.get(applicationId.value)
    if (response.data.success) {
      const app = response.data.data as Application
      applicationData.value = app

      if (!app.allowed_actions?.includes('edit_application')) {
        toast.error('Cannot edit application that has been assigned to an officer')
        redirectToDashboard(app.status?.code)
        return
      }

      form.full_name = app.full_name
      form.national_id = app.national_id || ''
      form.date_of_birth = app.date_of_birth || ''
      form.phone_number = app.phone_number || ''
      form.district = app.district || ''
      form.traditional_authority = app.traditional_authority || ''
      form.village = app.village || ''
      form.requested_name = app.requested_name
      form.reason_id = app.name_change_reason_id
        || app.name_change_reason?.id
        || reasonOptions.value.find(option => option.name === app.reason)?.id
        || null
      form.reason = app.name_change_reason?.name || app.reason || ''
    }

    // Fetch existing documents
    await fetchDocuments()

  } catch (error: any) {
    toast.error('Failed to load application')
    console.error('Error loading application:', error)
    redirectToDashboard(applicationData.value?.status?.code)
  } finally {
    loadingApplication.value = false
  }
}

async function fetchDocuments() {
  if (!applicationId.value) return

  try {
    const response = await documentsApi.list(applicationId.value)
    if (response.data.success) {
      existingDocuments.value = response.data.data || []
    }
  } catch (error) {
    console.error('Error fetching documents:', error)
  }
}

async function fetchDocumentTypes() {
  try {
    const response = await adminApi.getDocumentTypes()
    if (response.data.success) {
      documentTypes.value = response.data.data
        .filter((type: any) => type.is_active !== false)
        .map((type: any) => ({
          id: type.id,
          name: type.name
        }))

      // Set default document type (supporting_document)
      const defaultType = documentTypes.value.find((t: any) => t.name.toLowerCase().includes('supporting'))
      if (defaultType) {
        selectedDocumentType.value = defaultType.id
      } else if (documentTypes.value.length > 0) {
        selectedDocumentType.value = documentTypes.value[0]!.id
      }
    }
  } catch (error) {
    console.error('Error fetching document types:', error)
  }
}

async function fetchNameChangeReasons() {
  try {
    const response = await applicationsApi.getNameChangeReasons()
    if (response.data.success) {
      reasonOptions.value = (response.data.data || [])
        .filter((reason: NameChangeReason) => reason.is_active !== false)
        .sort((a: NameChangeReason, b: NameChangeReason) => {
          if ((a.order ?? 0) !== (b.order ?? 0)) {
            return (a.order ?? 0) - (b.order ?? 0)
          }
          return a.name.localeCompare(b.name)
        })
    }
  } catch (error) {
    console.error('Error fetching name change reasons:', error)
  }
}

function getDocumentIcon(mimeType: string) {
  if (mimeType?.includes('pdf')) return 'mdi-file-pdf-box'
  if (mimeType?.includes('image')) return 'mdi-file-image'
  return 'mdi-file-document'
}

function isPdfFile(mimeType: string): boolean {
  return mimeType?.includes('pdf') || mimeType === 'application/pdf'
}

function isImageFile(mimeType: string): boolean {
  return mimeType?.includes('image') || ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'].includes(mimeType)
}

async function previewDocument(doc: any) {
  previewDocumentData.value = doc
  previewLoading.value = true
  showPreviewDialog.value = true

  try {
    const response = await documentsApi.preview(doc.id)
    console.log('Preview response:', response)
    console.log('Response status:', response.status)
    console.log('Content-Type:', response.headers['content-type'])

    // Check if response is an error (status >= 400)
    if (response.status >= 400) {
      // If it's a blob containing JSON error, parse it
      if (response.data instanceof Blob) {
        const text = await response.data.text()
        try {
          const errorData = JSON.parse(text)
          throw new Error(errorData.error?.message || errorData.message || 'Failed to preview document')
        } catch (parseError) {
          throw new Error('Failed to preview document')
        }
      }
      throw new Error('Failed to preview document')
    }

    // Check if response is a blob
    if (response.data instanceof Blob) {
      const contentType = response.headers['content-type'] || doc.mime_type || 'application/octet-stream'
      const blob = new Blob([response.data], { type: contentType })
      previewUrl.value = window.URL.createObjectURL(blob)
    } else {
      // If it's not a blob, try to create one from the data
      const blob = new Blob([response.data], { type: doc.mime_type || 'application/octet-stream' })
      previewUrl.value = window.URL.createObjectURL(blob)
    }
  } catch (error: any) {
    console.error('Preview error:', error)
    console.error('Error response:', error.response)

    let errorMessage = 'Failed to preview document'

    // Handle blob error responses (when responseType is 'blob' but server returns JSON)
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const errorData = JSON.parse(text)
        errorMessage = errorData.error?.message || errorData.message || errorMessage
      } catch (parseError) {
        // If parsing fails, use default message
        errorMessage = error.response?.status === 403 ? 'You do not have permission to preview this document' :
                      error.response?.status === 404 ? 'Document not found' :
                      errorMessage
      }
    } else if (error.response?.data) {
      errorMessage = error.response.data.error?.message ||
                    error.response.data.message ||
                    errorMessage
    } else if (error.message) {
      errorMessage = error.message
    }

    toast.error(errorMessage)
    showPreviewDialog.value = false
  } finally {
    previewLoading.value = false
  }
}

function closePreview() {
  showPreviewDialog.value = false
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
  previewDocumentData.value = null
}

async function downloadDocument(id: number) {
  try {
    const response = await documentsApi.download(id)
    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `document-${id}`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    toast.error('Failed to download document')
  }
}

async function deleteDocument(id: number) {
  if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
    return
  }

  deletingDocumentId.value = id
  try {
    await documentsApi.delete(id)
    toast.success('Document deleted successfully')
    // Remove from local list
    existingDocuments.value = existingDocuments.value.filter(doc => doc.id !== id)
  } catch (error: any) {
    console.error('Delete document error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to delete document'
    toast.error(errorMessage)
  } finally {
    deletingDocumentId.value = null
  }
}

async function uploadDocuments() {
  if (validFiles.value.length === 0) {
    toast.error('Please select at least one file')
    return
  }

  if (!selectedDocumentType.value) {
    toast.error('Please select a document type')
    return
  }

  uploadingDocuments.value = true

  try {
    // Upload files one by one
    const uploadPromises = validFiles.value.map(async (file) => {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('document_type_id', selectedDocumentType.value!.toString())

      return documentsApi.upload(applicationId.value!, formData)
    })

    await Promise.all(uploadPromises)

    toast.success(`Successfully uploaded ${validFiles.value.length} file(s)`)

    // Clear uploaded files and refresh document list
    validFiles.value = []
    selectedFiles.value = []
    await fetchDocuments()
  } catch (error: any) {
    console.error('Upload error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to upload document(s)'
    toast.error(errorMessage)
  } finally {
    uploadingDocuments.value = false
  }
}

function handleCancel() {
  if (isEditMode.value) {
    redirectToDashboard(applicationData.value?.status?.code)
    return
  }

  redirectToDashboard()
}

async function handleSubmit() {
  const { valid: formValid } = await formRef.value.validate()
  if (!formValid) return

  // Validate files if any selected
  if (fileErrors.value.length > 0) {
    toast.error('Please fix file errors before submitting')
    return
  }

  loading.value = true

  try {
    let response
    if (isEditMode.value) {
      // Check if application is assigned - cannot edit if assigned, unless this is the admin approver-return review flow.
      if (isEditLocked.value) {
        toast.error('Cannot edit application that has been assigned to an officer')
        redirectToDashboard(applicationData.value?.status?.code)
        return
      }

      // Check if user can edit this application
      if (!canEditLoadedApplication.value) {
        toast.error('You do not have permission to edit applications')
        redirectToDashboard(applicationData.value?.status?.code)
        return
      }

      response = await applicationsApi.update(applicationId.value!, form)
      if (response.data.success) {
        toast.success('Application updated successfully!')
        // Refresh documents before navigating
        await fetchDocuments()
        redirectToDashboard(response.data.data?.status?.code || applicationData.value?.status?.code)
      }
    } else {
      // Create FormData for file upload
      const formData = new FormData()
      formData.append('full_name', form.full_name)
      if (form.national_id) formData.append('national_id', form.national_id)
      if (form.date_of_birth) formData.append('date_of_birth', form.date_of_birth)
      if (form.phone_number) formData.append('phone_number', form.phone_number)
      formData.append('district', form.district)
      formData.append('traditional_authority', form.traditional_authority)
      formData.append('village', form.village)
      formData.append('requested_name', form.requested_name)
      if (form.reason_id) {
        formData.append('reason_id', String(form.reason_id))
      } else if (form.reason) {
        formData.append('reason', form.reason)
      }

      // Append files (Laravel expects 'documents[]' for array)
      validFiles.value.forEach((file) => {
        formData.append('documents[]', file)
      })

      response = await applicationsApi.createWithFiles(formData)
      if (response.data.success) {
        toast.success('Application created successfully!')
        // Reset form and files
        selectedFiles.value = []
        validFiles.value = []
        fileErrors.value = []
        redirectToDashboard(response.data.data?.status?.code)
      }
    }
  } catch (error: any) {
    console.error('Application error:', error)
    console.error('Error response:', error.response)

    // Handle validation errors (422)
    if (error.response?.status === 422) {
      const validationErrors = error.response.data?.errors
      if (validationErrors) {
        // Show first validation error
        const firstError = Object.values(validationErrors)[0]
        const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError
        toast.error(errorMessage || 'Validation failed. Please check your input.')
      } else {
        toast.error(error.response.data?.message || 'Validation failed. Please check your input.')
      }
    } else if (error.response?.status === 403) {
      toast.error('You do not have permission to perform this action')
    } else {
      const errorMessage = error.response?.data?.error?.message ||
                          error.response?.data?.message ||
                          (isEditMode.value ? 'Failed to update application' : 'Failed to create application')
      toast.error(errorMessage)
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchNameChangeReasons()
  await fetchDocumentTypes()

  if (isEditMode.value) {
    await loadApplication()
  }
})
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

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.form-actions__button {
  min-width: 140px;
}

.gov-card :deep(.v-card) {
  border-radius: 16px;
}

.reason-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 14px 20px;
}

.reason-choice {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  width: fit-content;
  cursor: pointer;
}

.reason-choice__radio {
  flex-shrink: 0;
}

.reason-choice__label {
  line-height: 1.5;
  padding-top: 0;
}

.reason-choice :deep(.v-selection-control) {
  flex: 0 0 auto;
  width: auto;
  min-width: 0;
}

.reason-choice :deep(.v-selection-control__input) {
  margin-inline-end: 8px;
}

@media (max-width: 600px) {
  .gov-hero__action {
    width: 100%;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .form-actions__button {
    width: 100%;
    min-width: 0;
  }
}
</style>
