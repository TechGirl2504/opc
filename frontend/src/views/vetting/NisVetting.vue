<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">NIS Vetting</h1>
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
                <div class="text-caption text-grey">Current Full Name</div>
                <div class="text-body-1">{{ application.full_name }}</div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-grey">National ID</div>
                <div class="text-body-1">{{ application.national_id }}</div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="text-caption text-grey">Requested Full Name</div>
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
                  ></v-text-field>
                </v-col>
                <v-col cols="12">
                  <v-card variant="outlined" class="pa-4">
                    <div class="text-subtitle-1 mb-3">Recommendation</div>
                    <v-radio-group
                      v-model="form.recommendation_id"
                      :rules="[rules.required]"
                      @update:model-value="handleRecommendationChange"
                    >
                      <div class="d-flex flex-column gap-2">
                        <v-radio
                          v-for="recommendation in recommendations"
                          :key="recommendation.id"
                          :label="recommendation.name"
                          :value="recommendation.id"
                        />
                      </div>
                    </v-radio-group>
                  </v-card>
                </v-col>
                <v-col cols="12" v-if="selectedRecommendation?.code === 'reject' && form.return_reason">
                  <v-alert color="primary" variant="tonal" border="start" class="mb-4">
                    <div class="text-subtitle-2 mb-1">Saved Rejection Reason</div>
                    <div>{{ form.return_reason }}</div>
                  </v-alert>
                </v-col>
                <v-col cols="12" v-if="showReportUploadSection">
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
                        <v-btn
                          class="ml-2"
                          color="primary"
                          variant="flat"
                          prepend-icon="mdi-upload"
                          :loading="uploadingDocuments"
                          :disabled="loading || uploadingDocuments || validFiles.length === 0"
                          @click="uploadReportDocuments"
                        >
                          Upload Documents
                        </v-btn>
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
                  <v-alert type="info" variant="tonal">
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
                      :disabled="loading || savingDraft || !canEditVetting || !canCompleteVetting"
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
              v-if="isPdfFile(previewFileData?.name || '')"
              :src="previewUrl"
              width="100%"
              height="100%"
              frameborder="0"
            ></iframe>
            <img
              v-else-if="isImageFile(previewFileData?.name || '')"
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

    <v-dialog v-model="showRejectDialog" max-width="720">
      <v-card>
        <v-card-title class="text-h6">Rejection Reason</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="rejectReasonDraft"
            label="Reason for rejection"
            rows="4"
            variant="outlined"
            placeholder="Explain why this vetting should be rejected"
            :rules="[rules.required]"
            persistent-hint
            hint="This will be saved with the vetting record."
          />
        </v-card-text>
        <v-card-actions class="justify-end">
          <v-btn variant="text" @click="cancelRejectReason">Cancel</v-btn>
          <v-btn color="primary" @click="confirmRejectReason">Save Reason</v-btn>
        </v-card-actions>
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
const uploadingDocuments = ref(false)
const error = ref('')
const application = ref<Application | null>(null)
const existingVetting = ref<VettingRecord | null>(null)
const recommendations = ref<DecisionValue[]>([])
const showRejectDialog = ref(false)
const rejectReasonDraft = ref('')

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
  recommendation_id: undefined,
  return_reason: '',
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
      // Trust backend action matrix to decide if the user may vet this application.
      if (!application.value?.allowed_actions?.includes('conduct_nis_vetting')) {
        toast.error('You are not authorized to perform NIS vetting on this application')
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

const selectedRecommendation = computed(() => {
  return recommendations.value.find((item) => item.id === form.recommendation_id) || null
})

const showReportUploadSection = computed(() => selectedRecommendation.value?.code === 'approve')
const canManageSupportingDocuments = computed(() => true)
const hasUploadedAttachment = computed(() => existingDocuments.value.length > 0)
const canCompleteVetting = computed(() => {
  if (!canEditVetting.value || !selectedRecommendation.value) {
    return false
  }

  if (selectedRecommendation.value.code === 'approve') {
    return canManageSupportingDocuments.value ? hasUploadedAttachment.value : true
  }

  if (selectedRecommendation.value.code === 'reject') {
    return !!form.return_reason?.trim()
  }

  return false
})

async function removeUploadedReportDocuments() {
  if (existingDocuments.value.length === 0) return

  try {
    await Promise.all(existingDocuments.value.map((doc) => documentsApi.delete(doc.id)))
  } catch (err) {
    console.error('Error removing existing NIS vetting report documents:', err)
    throw err
  }

  existingDocuments.value = []
  validFiles.value = []
  fileErrors.value = []
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

async function handleRecommendationChange(recommendationId: number | null) {
  const recommendation = recommendations.value.find((item) => item.id === recommendationId)

  if (recommendation?.code === 'reject') {
    if (existingDocuments.value.length > 0) {
      try {
        await removeUploadedReportDocuments()
        toast.success('Uploaded vetting report documents removed')
      } catch (err) {
        toast.error('Failed to remove uploaded vetting report documents')
        return
      }
    }

    rejectReasonDraft.value = form.return_reason || ''
    showRejectDialog.value = true
    return
  }

  if (recommendation?.code === 'approve') {
    showRejectDialog.value = false
    rejectReasonDraft.value = ''
    form.return_reason = ''
    return
  }

  showRejectDialog.value = false
  rejectReasonDraft.value = ''
  form.return_reason = ''
}

function confirmRejectReason() {
  const reason = rejectReasonDraft.value.trim()
  if (!reason) {
    toast.error('Please provide a rejection reason')
    return
  }

  form.return_reason = reason
  showRejectDialog.value = false
}

function cancelRejectReason() {
  showRejectDialog.value = false
  rejectReasonDraft.value = ''
  form.return_reason = ''
  form.recommendation_id = undefined
}

async function loadExistingVetting() {
  try {
    const response = await vettingApi.getNisVetting(applicationId)
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
      form.recommendation_id = vetting.recommendation?.id
      form.return_reason = vetting.return_reason || ''
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
      // Filter for NIS vetting report documents
      const nisReportDocs = allDocuments.filter((doc: any) => 
        doc.document_type?.code === 'nis_vetting_report'
      )
      existingDocuments.value = nisReportDocs.map((doc: any) => ({
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

async function resolveReportDocumentTypeId(): Promise<number | null> {
  try {
    const response = await adminApi.getDocumentTypes()
    if (response.data.success) {
      const reportType = response.data.data.find((dt: any) => dt.code === 'nis_vetting_report')
      return reportType?.id ?? null
    }
  } catch (err) {
    console.error('Error resolving NIS report document type:', err)
  }
  return null
}

async function uploadReportDocuments() {
  if (validFiles.value.length === 0) {
    toast.error('Please select at least one document to upload')
    return
  }

  uploadingDocuments.value = true

  try {
    const documentTypeId = await resolveReportDocumentTypeId()
    if (!documentTypeId) {
      toast.error('Unable to resolve the NIS vetting report document type')
      return
    }

    for (const file of validFiles.value) {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('document_type_id', String(documentTypeId))
      formData.append('description', 'NIS vetting report document')
      await documentsApi.upload(applicationId, formData)
    }

    validFiles.value = []
    fileErrors.value = []
    fileInputRef.value && (fileInputRef.value.value = '')
    toast.success('Vetting report documents uploaded successfully')
    await loadExistingDocuments()
  } catch (err: any) {
    const errorMessage = err.response?.data?.error?.message ||
      err.response?.data?.message ||
      'Failed to upload vetting report documents'
    toast.error(errorMessage)
  } finally {
    uploadingDocuments.value = false
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
      recommendation_id: form.recommendation_id,
      return_reason: form.return_reason,
      vetting_date: form.vetting_date
    }

    const response = await vettingApi.saveNisVettingDraft(applicationId, submitData)

    if (response.data.success) {
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

  const returnReason = (form.return_reason || '').trim()

  if (selectedRecommendation.value?.code === 'reject' && !returnReason) {
    toast.error('Please save a rejection reason before completing')
    return
  }

  if (selectedRecommendation.value?.code === 'approve' && !hasUploadedAttachment.value) {
    toast.error('Please upload vetting report documents before completing')
    return
  }

  loading.value = true
  error.value = ''

  try {
    const submitData: SubmitVettingRequest = {
      recommendation_id: form.recommendation_id,
      return_reason: returnReason || undefined,
      vetting_date: form.vetting_date
    }

    const response = await vettingApi.completeNisVetting(applicationId, submitData)

    if (response.data.success) {
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

.recommendation-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 12px 20px;
}

.recommendation-grid__item {
  display: inline-flex;
  align-items: center;
  width: fit-content;
}
</style>
