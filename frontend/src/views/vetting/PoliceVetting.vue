<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Police Vetting</h1>
          <v-btn
            variant="text"
            prepend-icon="mdi-arrow-left"
            @click="goBack"
          >
            Back
          </v-btn>
        </div>

        <!-- Application Info Card -->
        <v-card class="mb-4" v-if="application">
          <v-card-title>Application Information</v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" sm="6">
                <div class="text-caption text-grey">Application Number</div>
                <div class="text-body-1 font-weight-bold">{{ application.application_number }}</div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-grey">Applicant Name</div>
                <div class="text-body-1">{{ application.full_name }}</div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-grey">National ID</div>
                <div class="text-body-1">{{ application.national_id }}</div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-grey">Requested Name</div>
                <div class="text-body-1 font-weight-bold">{{ application.requested_name }}</div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Vetting Form -->
        <v-card>
          <v-card-title>Vetting Details</v-card-title>
          <v-card-text>
            <v-form ref="formRef">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.vetting_date"
                    label="Vetting Date"
                    type="date"
                    :rules="[rules.required]"
                    :disabled="!canEditVetting"
                    variant="outlined"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="form.recommendation_id"
                    :items="recommendations"
                    item-title="name"
                    item-value="id"
                    label="Recommendation"
                    :rules="[rules.required]"
                    :disabled="!canEditVetting"
                    variant="outlined"
                  ></v-select>
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.findings"
                    label="Findings"
                    rows="5"
                    :disabled="!canEditVetting"
                    hint="Enter detailed findings from the vetting process"
                    persistent-hint
                    variant="outlined"
                  ></v-textarea>
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.remarks"
                    label="Remarks"
                    rows="3"
                    :disabled="!canEditVetting"
                    hint="Additional remarks or notes"
                    persistent-hint
                    variant="outlined"
                  ></v-textarea>
                </v-col>
                <v-col cols="12">
                  <v-card variant="outlined">
                    <v-card-title class="text-subtitle-1">Vetting Report Documents</v-card-title>
                    <v-card-text>
                      <div class="mb-2">
                        <label class="text-body-2 text-medium-emphasis mb-1 d-block">
                          Select Documents
                          <span class="text-caption ml-1">Unlimited files, 50MB each (PDF, JPG, PNG)</span>
                        </label>
                        <input
                          ref="fileInputRef"
                          type="file"
                          multiple
                          accept=".pdf,.jpg,.jpeg,.png"
                          :disabled="loading"
                          class="d-none"
                          @change="handleFileInputChange"
                        />
                        <v-btn
                          color="primary"
                          variant="outlined"
                          prepend-icon="mdi-paperclip"
                          :disabled="loading"
                          @click="triggerFileInput"
                        >
                          Select Files
                        </v-btn>
                        <span v-if="validFiles.length > 0" class="ml-2 text-caption">
                          {{ validFiles.length }} files selected
                        </span>
                      </div>

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

                      <!-- Existing Documents -->
                      <v-list v-if="existingDocuments.length > 0" class="mt-4">
                        <v-list-subheader>Existing Vetting Report Documents</v-list-subheader>
                        <v-list-item
                          v-for="doc in existingDocuments"
                          :key="doc.id"
                          :title="doc.file_name"
                          :subtitle="formatFileSize(doc.file_size)"
                        >
                          <template v-slot:prepend>
                            <v-icon :color="getFileIconColor(doc.file_name)">{{ getFileIcon(doc.file_name) }}</v-icon>
                          </template>
                          <template v-slot:append>
                            <v-btn
                              icon="mdi-eye"
                              size="small"
                              variant="text"
                              class="mr-1"
                              :loading="previewLoading && previewDocumentId === doc.id"
                              @click="previewDocument(doc.id)"
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

                      <!-- New Files -->
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
                              icon="mdi-eye"
                              size="small"
                              variant="text"
                              class="mr-1"
                              @click="previewFile(file)"
                            />
                            <v-btn
                              icon="mdi-close"
                              size="small"
                              variant="text"
                              @click="removeFile(index)"
                            />
                          </template>
                        </v-list-item>
                      </v-list>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" v-if="existingVetting">
                  <v-alert
                    type="info"
                    variant="tonal"
                  >
                    A vetting record already exists. Submitting will update the existing record.
                  </v-alert>
                </v-col>
                <v-col cols="12">
                  <v-alert v-if="error" type="error" class="mb-4">{{ error }}</v-alert>
                  <div class="d-flex justify-end gap-2">
                    <v-btn
                      variant="outlined"
                      @click="goBack"
                      :disabled="loading || savingDraft"
                    >
                      Cancel
                    </v-btn>
                    <v-btn
                      color="info"
                      variant="outlined"
                      :loading="savingDraft"
                      :disabled="loading || savingDraft || !canEditVetting"
                      @click="handleSaveDraft"
                    >
                      Save as Draft
                    </v-btn>
                    <v-btn
                      color="success"
                      :loading="loading"
                      :disabled="loading || savingDraft || !canEditVetting"
                      @click="handleComplete"
                    >
                      {{ existingVetting?.status?.code === 'completed' ? 'Update & Complete' : 'Mark as Complete' }}
                    </v-btn>
                  </div>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Preview Dialog -->
    <v-dialog v-model="showPreviewDialog" max-width="90vw" max-height="90vh">
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Preview: {{ previewFileData?.name }}</span>
          <v-btn icon="mdi-close" size="small" variant="text" @click="closePreview" />
        </v-card-title>
        <v-card-text class="d-flex justify-center align-center" style="height: calc(90vh - 64px);">
          <v-progress-circular
            v-if="previewLoading"
            indeterminate
            color="primary"
            size="64"
          ></v-progress-circular>
          <div v-else-if="previewUrl" class="d-flex justify-center align-center w-100 h-100">
            <iframe
              v-if="previewFileData && isPdfFile(previewFileData.name || '')"
              :src="previewUrl"
              width="100%"
              height="100%"
              frameborder="0"
            ></iframe>
            <img
              v-else-if="previewFileData && isImageFile(previewFileData.name || '')"
              :src="previewUrl"
              style="max-width: 100%; max-height: 100%; object-fit: contain;"
            />
            <v-alert v-else type="info" variant="tonal" class="text-center">
              <p>No preview available for this file type.</p>
            </v-alert>
          </div>
          <v-alert v-else type="error" variant="tonal">
            Failed to load preview.
          </v-alert>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { vettingApi, type SubmitVettingRequest } from '@/api/vetting'
import { applicationsApi } from '@/api/applications'
import { adminApi } from '@/api/admin'
import { documentsApi } from '@/api/documents'
import type { Application } from '@/api/applications'
import type { VettingRecord } from '@/api/vetting'
import type { DecisionValue } from '@/api/admin'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const applicationId = parseInt(route.params.id as string)
const formRef = ref<HTMLFormElement | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const loading = ref(false)
const savingDraft = ref(false)
const error = ref('')
const application = ref<Application | null>(null)
const existingVetting = ref<VettingRecord | null>(null)
const recommendations = ref<DecisionValue[]>([])

// File handling
const validFiles = ref<File[]>([])
const fileErrors = ref<string[]>([])
const existingDocuments = ref<Array<{ id: number; file_name: string; file_size: number; mime_type: string }>>([])
const deletingDocumentId = ref<number | null>(null)

// Preview state
const showPreviewDialog = ref(false)
const previewFileData = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const previewLoading = ref(false)
const previewDocumentId = ref<number | null>(null)

const form = reactive<SubmitVettingRequest & { vetting_date?: string }>({
  remarks: '',
  findings: '',
  recommendation_id: undefined,
  vetting_date: new Date().toISOString().split('T')[0],
  document: undefined
})

const rules = {
  required: (v: any) => !!v || 'This field is required',
  fileSize: (v: File[] | null | undefined) => {
    if (!v || !Array.isArray(v) || v.length === 0) return true
    const file = v[0]
    if (!file || !file.size) return true
    return file.size <= 50 * 1024 * 1024 || 'File size must be less than 50MB'
  },
  fileType: (v: File[] | null | undefined) => {
    if (!v || !Array.isArray(v) || v.length === 0) return true
    const file = v[0]
    if (!file || !file.type) return true
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
    return allowedTypes.includes(file.type) || 'File must be PDF, JPG, JPEG, or PNG'
  }
}

async function loadApplication() {
  try {
    const response = await applicationsApi.get(applicationId)
    if (response.data.success) {
      application.value = response.data.data
      // Verify user is assigned to this application (only police officers can vet)
      const assignedId = application.value?.assigned_police_officer?.id
      if (!authStore.hasPermission('conduct police vetting') || assignedId !== authStore.user?.id) {
        toast.error('You are not authorized to perform police vetting on this application')
        router.push({ name: 'Applications' })
        return
      }
    }
  } catch (err: any) {
    toast.error('Failed to load application')
    console.error(err)
  }
}

const canEditVetting = computed(() => {
  if (!existingVetting.value) return true // Can create new vetting
  const statusCode = existingVetting.value.status?.code
  // Can edit if pending, in_progress, or sent_back
  // Cannot edit if completed
  return statusCode !== 'completed'
})

async function loadExistingVetting() {
  try {
    const response = await vettingApi.getPoliceVetting(applicationId)
    if (response.data.success && response.data.data) {
      const vetting = response.data.data as any
      existingVetting.value = vetting

      // Check if vetting is completed and not sent back - if so, redirect
      if (vetting.status?.code === 'completed') {
        toast.error('This vetting has been completed and cannot be edited. It has been sent to OPC for review.')
        router.push({ name: 'ApplicationDetail', params: { id: applicationId } })
        return
      }

      // Pre-fill form with existing data
      form.remarks = vetting.remarks || ''
      form.findings = vetting.findings || ''
      form.recommendation_id = vetting.recommendation?.id
      if (vetting.vetting_date) {
        form.vetting_date = new Date(vetting.vetting_date).toISOString().split('T')[0]
      }
    }
    // Load existing vetting report documents
    await loadExistingDocuments()
  } catch (err: any) {
    console.error('Error loading existing vetting:', err)
  }
}

async function loadExistingDocuments() {
  try {
    const response = await documentsApi.list(applicationId)
    if (response.data.success) {
      const allDocuments = response.data.data || []
      // Filter for police vetting report documents
      const policeReportDocs = allDocuments.filter((doc: any) =>
        doc.document_type?.code === 'police_vetting_report'
      )
      existingDocuments.value = policeReportDocs.map((doc: any) => ({
        id: doc.id,
        file_name: doc.file_name,
        file_size: doc.file_size,
        mime_type: doc.mime_type
      }))
    }
  } catch (err: any) {
    console.error('Error loading existing documents:', err)
  }
}

async function deleteDocument(documentId: number) {
  if (!confirm('Are you sure you want to delete this document?')) {
    return
  }

  deletingDocumentId.value = documentId
  try {
    await documentsApi.delete(documentId)
    toast.success('Document deleted successfully')
    // Remove from list
    existingDocuments.value = existingDocuments.value.filter(doc => doc.id !== documentId)
  } catch (err: any) {
    toast.error('Failed to delete document')
    console.error(err)
  } finally {
    deletingDocumentId.value = null
  }
}

async function previewDocument(documentId: number) {
  previewDocumentId.value = documentId
  previewLoading.value = true
  showPreviewDialog.value = true
  previewUrl.value = null
  previewFileData.value = null

  try {
    const response = await documentsApi.preview(documentId)
    const blob = response.data
    const url = window.URL.createObjectURL(blob)
    previewUrl.value = url

    // Get document info for display
    const doc = existingDocuments.value.find(d => d.id === documentId)
    if (doc) {
      previewFileData.value = {
        name: doc.file_name,
        size: doc.file_size,
        type: doc.mime_type
      } as any
    }
  } catch (err: any) {
    toast.error('Failed to preview document')
    console.error(err)
  } finally {
    previewLoading.value = false
  }
}

async function loadRecommendations() {
  try {
    // Get decision values that are recommendations (approve, reject)
    const response = await adminApi.getDecisionValues()
    if (response.data.success) {
      // Filter for recommendation values (approve, reject)
      recommendations.value = response.data.data.filter((dv: DecisionValue) =>
        dv.code === 'approve' || dv.code === 'reject'
      )
    }
  } catch (err) {
    console.error('Error loading recommendations:', err)
    // Fallback to hardcoded values
    recommendations.value = [
      { id: 4, name: 'Approve', code: 'approve' } as DecisionValue,
      { id: 5, name: 'Reject', code: 'reject' } as DecisionValue
    ]
  }
}

// File handling functions
const MAX_FILE_SIZE_MB = 50
const ALLOWED_MIMES = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png']

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
  if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
    return `${file.name}: File size exceeds ${MAX_FILE_SIZE_MB}MB`
  }
  const fileExtension = file.name.split('.').pop()?.toLowerCase()
  if (!ALLOWED_MIMES.includes(file.type) && !ALLOWED_EXTENSIONS.includes(fileExtension || '')) {
    return `${file.name}: File type not allowed. Only PDF, JPG, JPEG, PNG are allowed`
  }
  return null
}

function triggerFileInput() {
  fileInputRef.value?.click()
}

function handleFileInputChange(event: Event) {
  const target = event.target as HTMLInputElement
  const newFiles = Array.from(target.files || [])

  fileErrors.value = []

  newFiles.forEach((file) => {
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

  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function removeFile(index: number) {
  validFiles.value.splice(index, 1)
  fileErrors.value = []
}

function isPdfFile(fileName: string): boolean {
  const ext = fileName.split('.').pop()?.toLowerCase()
  return ext === 'pdf'
}

function isImageFile(fileName: string): boolean {
  const ext = fileName.split('.').pop()?.toLowerCase()
  return ['jpg', 'jpeg', 'png', 'gif'].includes(ext || '')
}

function previewFile(file: File) {
  previewFileData.value = file
  previewDocumentId.value = null
  previewLoading.value = true
  showPreviewDialog.value = true
  previewUrl.value = null

  try {
    const reader = new FileReader()
    reader.onload = (e) => {
      previewUrl.value = e.target?.result as string
      previewLoading.value = false
    }
    reader.onerror = () => {
      previewLoading.value = false
      toast.error('Failed to load preview')
    }
    if (isPdfFile(file.name)) {
      reader.readAsDataURL(file)
    } else if (isImageFile(file.name)) {
      reader.readAsDataURL(file)
    } else {
      previewLoading.value = false
      toast.info('Preview not available for this file type')
    }
  } catch (err) {
    previewLoading.value = false
    toast.error('Failed to preview file')
  }
}

function closePreview() {
  showPreviewDialog.value = false
  if (previewUrl.value && previewDocumentId.value) {
    // Only revoke URL if it was created from a document (not from FileReader)
    window.URL.revokeObjectURL(previewUrl.value)
  }
  previewUrl.value = null
  previewFileData.value = null
  previewDocumentId.value = null
}

async function handleSaveDraft() {
  const { valid } = await formRef.value?.validate()
  if (!valid) return

  savingDraft.value = true
  error.value = ''

  try {
    const submitData: SubmitVettingRequest = {
      remarks: form.remarks,
      findings: form.findings,
      recommendation_id: form.recommendation_id,
      vetting_date: form.vetting_date,
      document: validFiles.value.length > 0 ? validFiles.value[0] : undefined
    }

    const response = await vettingApi.savePoliceVettingDraft(applicationId, submitData)

    if (response.data.success) {
      // Upload additional documents if any
      if (validFiles.value.length > 1) {
        const documentType = await adminApi.getDocumentTypes()
        const policeReportType = documentType.data.data.find((dt: any) => dt.code === 'police_vetting_report')

        if (policeReportType) {
          for (let i = 1; i < validFiles.value.length; i++) {
            const formData = new FormData()
            const file = validFiles.value[i]
            if (!file) continue
            formData.append('file', file)
            formData.append('document_type_id', policeReportType.id.toString())
            formData.append('description', 'Police vetting report document')
            await documentsApi.upload(applicationId, formData)
          }
        }
      }

      toast.success('Vetting saved as draft successfully')
      // Reload to get updated status
      await loadExistingVetting()
    }
  } catch (err: any) {
    const errorMessage = err.response?.data?.error?.message ||
                        err.response?.data?.message ||
                        'Failed to save draft'
    error.value = errorMessage
    toast.error(errorMessage)
  } finally {
    savingDraft.value = false
  }
}

async function handleComplete() {
  const { valid } = await formRef.value?.validate()
  if (!valid) return

  loading.value = true
  error.value = ''

  try {
    const submitData: SubmitVettingRequest = {
      remarks: form.remarks,
      findings: form.findings,
      recommendation_id: form.recommendation_id,
      vetting_date: form.vetting_date,
      document: validFiles.value.length > 0 ? validFiles.value[0] : undefined
    }

    const response = await vettingApi.completePoliceVetting(applicationId, submitData)

    if (response.data.success) {
      // Upload additional documents if any
      if (validFiles.value.length > 1) {
        const documentType = await adminApi.getDocumentTypes()
        const policeReportType = documentType.data.data.find((dt: any) => dt.code === 'police_vetting_report')

        if (policeReportType) {
          for (let i = 1; i < validFiles.value.length; i++) {
            const formData = new FormData()
            const file = validFiles.value[i]
            if (!file) continue
            formData.append('file', file)
            formData.append('document_type_id', policeReportType.id.toString())
            formData.append('description', 'Police vetting report document')
            await documentsApi.upload(applicationId, formData)
          }
        }
      }

      toast.success('Vetting completed successfully')
      router.push({ name: 'ApplicationDetail', params: { id: applicationId } })
    }
  } catch (err: any) {
    const errorMessage = err.response?.data?.error?.message ||
                        err.response?.data?.message ||
                        'Failed to complete vetting'
    error.value = errorMessage
    toast.error(errorMessage)
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push({ name: 'ApplicationDetail', params: { id: applicationId } })
}

onMounted(async () => {
  await Promise.all([
    loadApplication(),
    loadExistingVetting(),
    loadRecommendations()
  ])
})

onUnmounted(() => {
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
  }
})
</script>

<style scoped>
.gap-2 {
  gap: 8px;
}
</style>
