<template>
  <div class="pa-4">
    <div v-if="vettingRecord">
      <div class="mb-3">
        <v-chip :color="getStatusColor(vettingRecord.status?.code)" size="small">
          {{ vettingRecord.status?.name }}
        </v-chip>
      </div>
      <div v-if="vettingRecord.findings" class="mb-3">
        <div class="text-caption text-grey">Findings</div>
        <div class="text-body-2">{{ vettingRecord.findings }}</div>
      </div>
      <div v-if="vettingRecord.remarks" class="mb-3">
        <div class="text-caption text-grey">Remarks</div>
        <div class="text-body-2">{{ vettingRecord.remarks }}</div>
      </div>
      <v-alert
        v-if="vettingRecord.status?.code === 'sent_back' && vettingRecord.return_reason"
        type="success"
        variant="tonal"
        class="mb-3"
      >
        <div class="font-weight-bold mb-2">Vetting Sent Back for Clarifications</div>
        <div>
          <strong>Reason:</strong> {{ vettingRecord.return_reason }}
        </div>
        <div class="text-caption mt-2">Please review the feedback and update your vetting record accordingly.</div>
      </v-alert>
      <div v-if="vettingDocuments.length > 0" class="mb-3">
        <div class="text-caption text-grey mb-2">Vetting Report Documents</div>
        <v-list density="compact">
          <v-list-item
            v-for="doc in vettingDocuments"
            :key="doc.id"
            :title="doc.file_name"
            :subtitle="formatFileSize(doc.file_size)"
          >
            <template v-slot:prepend>
              <v-icon :color="getFileIconColor(doc.file_name)" size="small">{{ getFileIcon(doc.file_name) }}</v-icon>
            </template>
            <template v-slot:append>
              <v-btn
                icon="mdi-eye"
                size="x-small"
                variant="text"
                class="mr-1"
                :loading="previewLoading && previewDocumentId === doc.id"
                @click="previewDocument(doc.id)"
              />
              <v-btn
                icon="mdi-download"
                size="x-small"
                variant="text"
                @click="downloadDocument(doc.id)"
              />
            </template>
          </v-list-item>
        </v-list>
      </div>
      <div class="text-caption text-grey">
        Conducted by: {{ vettingRecord.conducted_by?.username }} on {{ formatDate(vettingRecord.completed_at || vettingRecord.created_at) }}
      </div>
      <v-btn
        v-if="canEdit"
        color="primary"
        class="mt-3"
        @click="$router.push({ name: 'PoliceVetting', params: { id: applicationId } })"
      >
        {{ vettingRecord.status?.code === 'completed' ? 'Update' : 'Submit' }} Vetting
      </v-btn>
    </div>
    <div v-else>
      <v-alert type="info" variant="tonal">
        Police vetting not yet submitted
      </v-alert>
      <v-btn
        v-if="canEdit"
        color="primary"
        class="mt-3"
        @click="$router.push({ name: 'PoliceVetting', params: { id: applicationId } })"
      >
        Submit Vetting
      </v-btn>
    </div>

    <!-- Preview Dialog -->
    <v-dialog v-model="showPreviewDialog" max-width="90vw" max-height="90vh">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Preview: {{ previewDocumentData?.file_name }}</span>
        <v-btn icon="mdi-close" size="small" variant="text" @click="closePreview" />
      </v-card-title>
      <v-card-text class="d-flex justify-center align-center" style="height: calc(90vh - 64px);">
        <v-progress-circular
          v-if="previewLoading"
          indeterminate
          color="primary"
          size="64"
        ></v-progress-circular>
        <div v-else-if="previewUrl && previewDocumentData" class="d-flex justify-center align-center w-100 h-100">
          <iframe
            v-if="isPdfFile(previewDocumentData.mime_type)"
            :src="previewUrl"
            width="100%"
            height="100%"
            frameborder="0"
          ></iframe>
          <img
            v-else-if="isImageFile(previewDocumentData.mime_type)"
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { vettingApi } from '@/api/vetting'
import { documentsApi } from '@/api/documents'
import { format } from 'date-fns'

const props = defineProps<{
  applicationId: number
  canEdit: boolean
}>()

const vettingRecord = ref<any>(null)
const vettingDocuments = ref<Array<{ id: number; file_name: string; file_size: number; mime_type: string }>>([])
const showPreviewDialog = ref(false)
const previewDocumentId = ref<number | null>(null)
const previewUrl = ref<string | null>(null)
const previewLoading = ref(false)
const previewDocumentData = ref<{ id: number; file_name: string; mime_type: string } | null>(null)

function getStatusColor(statusCode: string) {
  const colors: Record<string, string> = {
    'pending': 'warning',
    'completed': 'success',
    'in_progress': 'info'
  }
  return colors[statusCode] || 'grey'
}

function formatDate(date: string) {
  if (!date) return ''
  return format(new Date(date), 'MMM dd, yyyy')
}

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

function isPdfFile(mimeType: string): boolean {
  return mimeType === 'application/pdf'
}

function isImageFile(mimeType: string): boolean {
  return ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'].includes(mimeType)
}

async function previewDocument(documentId: number) {
  previewDocumentId.value = documentId
  previewLoading.value = true
  showPreviewDialog.value = true
  previewUrl.value = null
  previewDocumentData.value = null

  try {
    const doc = vettingDocuments.value.find(d => d.id === documentId)
    if (!doc) return

    previewDocumentData.value = {
      id: doc.id,
      file_name: doc.file_name,
      mime_type: doc.mime_type
    }

    const response = await documentsApi.preview(documentId)
    const blob = response.data
    const url = window.URL.createObjectURL(blob)
    previewUrl.value = url
  } catch (error) {
    console.error('Error previewing document:', error)
  } finally {
    previewLoading.value = false
  }
}

function closePreview() {
  showPreviewDialog.value = false
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
  }
  previewUrl.value = null
  previewDocumentData.value = null
  previewDocumentId.value = null
}

async function downloadDocument(documentId: number) {
  try {
    const response = await documentsApi.download(documentId)
    const blob = response.data
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = vettingDocuments.value.find(d => d.id === documentId)?.file_name || 'document'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error downloading document:', error)
  }
}

async function fetchVetting() {
  try {
    const response = await vettingApi.getPoliceVetting(props.applicationId)
    if (response.data.success) {
      vettingRecord.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching police vetting:', error)
  }
}

async function fetchDocuments() {
  try {
    const response = await documentsApi.list(props.applicationId)
    if (response.data.success) {
      const allDocuments = response.data.data || []
      // Filter for police vetting report documents
      const policeReportDocs = allDocuments.filter((doc: any) =>
        doc.document_type?.code === 'police_vetting_report'
      )
      vettingDocuments.value = policeReportDocs.map((doc: any) => ({
        id: doc.id,
        file_name: doc.file_name,
        file_size: doc.file_size,
        mime_type: doc.mime_type
      }))
    }
  } catch (error) {
    console.error('Error fetching documents:', error)
  }
}

onMounted(async () => {
  await Promise.all([
    fetchVetting(),
    fetchDocuments()
  ])
})
</script>

