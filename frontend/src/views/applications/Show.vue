<template>
  <div v-if="application" class="application-detail-page">
    <v-sheet class="detail-hero mb-6" rounded="xl">
      <div class="detail-hero__inner">
        <div class="detail-hero__copy">
          <div class="detail-eyebrow">Application case file</div>
          <div class="d-flex flex-wrap align-center ga-3">
            <h1 class="text-h4 text-md-h3 font-weight-bold mb-0">
              {{ application.application_number }}
            </h1>
            <v-chip
              :color="getStatusColor(application.status?.code)"
              variant="flat"
              size="small"
            >
              {{ application.status?.name }}
            </v-chip>
          </div>
          <div class="detail-hero__meta">
            <v-chip variant="tonal" size="small" prepend-icon="mdi-account">
              {{ application.created_by?.username }}
            </v-chip>
            <v-chip variant="tonal" size="small" prepend-icon="mdi-calendar-outline">
              {{ formatDate(application.created_at) }}
            </v-chip>
            <v-chip variant="tonal" size="small" prepend-icon="mdi-map-marker">
              {{ application.district }} · {{ application.traditional_authority }} · {{ application.village }}
            </v-chip>
          </div>
        </div>

        <div class="detail-hero__actions">
          <v-btn
            class="detail-hero__button"
            variant="tonal"
            prepend-icon="mdi-arrow-left"
            @click="goBack"
          >
            Back to List
          </v-btn>
        </div>
      </div>
    </v-sheet>

    <v-row>
      <!-- Application Details -->
      <v-col cols="12" md="8">
        <v-card class="detail-card mb-4" elevation="2">
          <v-card-title class="detail-card__title">
            <div>
              <div class="text-h6">Application Information</div>
              <div class="text-caption text-medium-emphasis">Identity and location details for this file</div>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">Current Full Name</div>
                <div class="info-value">{{ application.full_name }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">National ID</div>
                <div class="info-value">{{ application.national_id }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Date of Birth</div>
                <div class="info-value">{{ formatDateOnly(application.date_of_birth) }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Phone Number</div>
                <div class="info-value">{{ application.phone_number || 'Not provided' }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">District</div>
                <div class="info-value">{{ application.district }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">T/A</div>
                <div class="info-value">{{ application.traditional_authority }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Village</div>
                <div class="info-value">{{ application.village }}</div>
              </div>
              <div class="info-item info-item--highlight">
                <div class="info-label">Requested Full Name</div>
                <div class="info-value">{{ application.requested_name }}</div>
              </div>
              <div class="info-item info-item--wide">
                <div class="info-label">Reason for Change</div>
                <div class="info-value info-value--rich">
                  {{ application.name_change_reason?.name || application.reason || 'Not provided' }}
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <!-- Documents -->
        <v-card class="detail-card mb-4" elevation="2">
          <v-card-title class="detail-card__title d-flex justify-space-between align-center">
            <div>
              <div class="text-h6">Supporting Documents</div>
              <div class="text-caption text-medium-emphasis">Unlimited uploads, 50MB per file</div>
            </div>
            <v-btn
              v-if="canUploadDocuments"
              size="small"
              color="primary"
              variant="tonal"
              prepend-icon="mdi-upload"
              @click="showUploadDialog = true"
            >
              Upload
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-list v-if="documents.length > 0" class="document-list" lines="two">
              <v-list-item
                v-for="doc in documents"
                :key="doc.id"
                :prepend-icon="getDocumentIcon(doc.mime_type)"
                :title="doc.file_name"
                :subtitle="formatFileSize(doc.file_size)"
              >
                <template v-slot:append>
                  <div class="d-flex align-center ga-1">
                    <v-btn
                      icon="mdi-eye"
                      size="small"
                      variant="text"
                      @click="previewDocument(doc)"
                    />
                    <v-btn
                      v-if="canDownloadDocuments"
                      icon="mdi-download"
                      size="small"
                      variant="text"
                      @click="downloadDocument(doc.id)"
                    />
                    <v-btn
                      v-if="canDeleteDocument(doc)"
                      icon="mdi-delete"
                      size="small"
                      variant="text"
                      color="error"
                      :loading="deletingDocumentId === doc.id"
                      @click="deleteDocument(doc.id)"
                    />
                  </div>
                </template>
              </v-list-item>
            </v-list>
            <v-sheet v-else class="empty-state empty-state--neutral" rounded="lg">
              <v-icon size="32" class="mb-2">mdi-file-document-outline</v-icon>
              <div class="text-subtitle-2 mb-1">No documents uploaded yet</div>
              <div class="text-caption text-medium-emphasis">
                Upload supporting evidence to keep the file complete and audit-ready.
              </div>
            </v-sheet>
          </v-card-text>
        </v-card>

        <!-- Vetting Records -->
        <v-card
          v-if="authStore.hasAnyPermission(['view vetting records', 'conduct police vetting', 'conduct nis vetting', 'send back vetting'])"
          class="detail-card mb-4"
          elevation="2"
        >
          <v-card-title class="detail-card__title">
            <div>
              <div class="text-h6">Vetting Records</div>
              <div class="text-caption text-medium-emphasis">Track police and NIS progress in one place</div>
            </div>
          </v-card-title>
          <v-card-text>
            <v-tabs v-model="vettingTab" class="vetting-tabs">
              <v-tab v-if="authStore.hasAnyPermission(['view vetting records', 'conduct police vetting', 'send back vetting'])" value="police">
                Police Vetting
              </v-tab>
              <v-tab v-if="authStore.hasAnyPermission(['view vetting records', 'conduct nis vetting', 'send back vetting'])" value="nis">
                NIS Vetting
              </v-tab>
            </v-tabs>

            <v-window v-model="vettingTab">
              <v-window-item
                v-if="authStore.hasAnyPermission(['view vetting records', 'conduct police vetting', 'send back vetting'])"
                value="police"
              >
                <PoliceVettingCard
                  :application-id="application.id"
                  :can-edit="canDoPoliceVetting"
                />
              </v-window-item>
              <v-window-item
                v-if="authStore.hasAnyPermission(['view vetting records', 'conduct nis vetting', 'send back vetting'])"
                value="nis"
              >
                <NisVettingCard
                  :application-id="application.id"
                  :can-edit="canDoNisVetting"
                />
              </v-window-item>
            </v-window>
          </v-card-text>
        </v-card>

      </v-col>

      <!-- Sidebar Actions -->
      <v-col cols="12" md="4">
        <v-card v-if="hasSidebarActions" class="detail-card action-card mb-4" elevation="2">
          <v-card-title class="detail-card__title">
            <div>
              <div class="text-h6">Actions</div>
              <div class="text-caption text-medium-emphasis">Only actions allowed for your role and this status appear here</div>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="action-section" v-if="canEditApplication || canSendBackToDataEntry">
              <div class="action-section__label">Corrections</div>
              <v-btn
                v-if="canEditApplication"
                block
                color="primary"
                size="large"
                prepend-icon="mdi-pencil"
                class="action-button mb-2"
                @click="editApplication"
              >
                Edit Application
              </v-btn>

              <v-btn
                v-if="canSendBackToDataEntry"
                block
                color="warning"
                size="large"
                prepend-icon="mdi-arrow-u-left-bottom"
                class="action-button mb-2"
                @click="showSendBackToDataEntryDialog = true"
              >
                Send Back to Data Entry
              </v-btn>
            </div>

            <div class="action-section" v-if="canDoPoliceVetting || canDoNisVetting">
              <div class="action-section__label">Vetting</div>
              <v-btn
                v-if="canDoPoliceVetting"
                block
                color="primary"
                variant="tonal"
                size="large"
                prepend-icon="mdi-shield-check"
                class="action-button mb-2"
                @click="$router.push({ name: 'PoliceVetting', params: { id: application.id } })"
              >
                Police Vetting
              </v-btn>

              <v-btn
                v-if="canDoNisVetting"
                block
                color="primary"
                variant="tonal"
                size="large"
                prepend-icon="mdi-shield-account"
                class="action-button mb-2"
                @click="$router.push({ name: 'NisVetting', params: { id: application.id } })"
              >
                NIS Vetting
              </v-btn>
            </div>

            <div class="action-section" v-if="canAssignPolice || canAssignNis">
              <div class="action-section__label">Assignments</div>
              <v-btn
                v-if="canAssignPolice"
                block
                color="info"
                variant="tonal"
                size="large"
                prepend-icon="mdi-account-plus"
                class="action-button mb-2"
                @click="showAssignPoliceDialog = true"
              >
                {{ application.assigned_police_officer ? 'Reassign Police Officer' : 'Assign Police Officer' }}
              </v-btn>

              <v-btn
                v-if="canAssignNis"
                block
                color="info"
                variant="tonal"
                size="large"
                prepend-icon="mdi-account-plus"
                class="action-button mb-2"
                @click="showAssignNisDialog = true"
              >
                {{ application.assigned_nis_officer ? 'Reassign NIS Officer' : 'Assign NIS Officer' }}
              </v-btn>
            </div>

            <div class="action-section" v-if="canForwardToApproval || canApprove || canDeny || canSendBackToAdmin">
              <div class="action-section__label">Approval</div>
              <v-btn
                v-if="canForwardToApproval"
                block
                color="info"
                size="large"
                prepend-icon="mdi-arrow-forward"
                class="action-button mb-2"
                :loading="forwarding"
                @click="forwardToApproval"
              >
                Forward to Approval
              </v-btn>

              <v-btn
                v-if="canApprove"
                block
                color="success"
                size="large"
                prepend-icon="mdi-check"
                class="action-button mb-2"
                @click="showApproveDialog = true"
              >
                Approve
              </v-btn>

              <v-btn
                v-if="canDeny"
                block
                color="error"
                size="large"
                prepend-icon="mdi-close"
                class="action-button mb-2"
                @click="showDenyDialog = true"
              >
                Deny
              </v-btn>

              <v-btn
                v-if="canSendBackToAdmin"
                block
                color="info"
                variant="tonal"
                size="large"
                prepend-icon="mdi-arrow-left"
                class="action-button mb-2"
                @click="showSendBackToAdminDialog = true"
              >
                Send Back to Admin
              </v-btn>
            </div>

            <div class="action-section" v-if="canHandleApproverSendBack">
              <div class="action-section__label">Approver Return</div>
              <v-alert type="warning" variant="tonal" density="compact" class="mb-3">
                <div class="font-weight-medium mb-1">Returned from approver review</div>
                <div class="text-body-2">
                  {{ application?.approver_send_back_reason || 'The approver returned this file for further action.' }}
                </div>
              </v-alert>

              <v-btn
                v-if="application?.assigned_police_officer"
                block
                color="primary"
                variant="tonal"
                size="large"
                prepend-icon="mdi-shield-account"
                class="action-button mb-2"
                @click="openHandleSendBackDialog('send_to_police')"
              >
                Return to Police Vetting
              </v-btn>

              <v-btn
                v-if="application?.assigned_nis_officer"
                block
                color="primary"
                variant="tonal"
                size="large"
                prepend-icon="mdi-shield-check"
                class="action-button mb-2"
                @click="openHandleSendBackDialog('send_to_nis')"
              >
                Return to NIS Vetting
              </v-btn>
            </div>

            <div
              class="action-section"
              v-if="(canSendBackPolice || canSendBackNis) && !canHandleApproverSendBack"
            >
              <div class="action-section__label">Vetting Feedback</div>
              <v-btn
                v-if="canSendBackPolice"
                block
                color="info"
                variant="tonal"
                size="large"
                prepend-icon="mdi-send"
                class="action-button mb-2"
                @click="showSendBackPoliceDialog = true"
              >
                Send Back Police Vetting
              </v-btn>

              <v-btn
                v-if="canSendBackNis"
                block
                color="secondary"
                variant="tonal"
                size="large"
                prepend-icon="mdi-send"
                class="action-button mb-2"
                @click="showSendBackNisDialog = true"
              >
                Send Back NIS Vetting
              </v-btn>
            </div>
          </v-card-text>
        </v-card>

        <!-- Application Info -->
        <v-card class="detail-card mb-4" elevation="2">
          <v-card-title class="detail-card__title">
            <div>
              <div class="text-h6">Application Info</div>
              <div class="text-caption text-medium-emphasis">Ownership and assignment metadata</div>
            </div>
          </v-card-title>
          <v-card-text>
            <div class="side-meta">
              <div class="side-meta__item">
                <div class="info-label">Created By</div>
                <div class="info-value">{{ application.created_by?.username }}</div>
              </div>
              <div class="side-meta__item">
                <div class="info-label">Created At</div>
                <div class="info-value">{{ formatDate(application.created_at) }}</div>
              </div>
              <div v-if="application.data_entry_return_reason" class="side-meta__item">
                <div class="info-label">Returned to Data Entry</div>
                <div class="info-value info-value--rich">{{ application.data_entry_return_reason }}</div>
              </div>
              <div v-if="application.assigned_opc_approver" class="side-meta__item">
                <div class="info-label">OPC Approver</div>
                <div class="info-value">{{ application.assigned_opc_approver?.username }}</div>
              </div>
              <div v-if="application.approver_send_back_reason" class="side-meta__item">
                <div class="info-label">Returned from Approver</div>
                <div class="info-value info-value--rich">{{ application.approver_send_back_reason }}</div>
              </div>
              <div v-if="application.assigned_police_officer" class="side-meta__item">
                <div class="info-label">Police Officer</div>
                <div class="info-value">{{ application.assigned_police_officer?.username }}</div>
              </div>
              <div v-if="application.assigned_nis_officer" class="side-meta__item">
                <div class="info-label">NIS Officer</div>
                <div class="info-value">{{ application.assigned_nis_officer?.username }}</div>
              </div>
            </div>
          </v-card-text>

        </v-card>

        <!-- Decisions -->
        <v-card v-if="decisions.length > 0" class="detail-card mb-4" elevation="2">
          <v-card-title class="detail-card__title">
            <div>
              <div class="text-h6">Decisions</div>
              <div class="text-caption text-medium-emphasis">History of approvals and denials</div>
            </div>
          </v-card-title>
          <v-card-text>
            <v-timeline side="end" density="compact">
              <v-timeline-item
                v-for="decision in decisions"
                :key="decision.id"
                :color="decision.decision_value?.code === 'approved' ? 'success' : 'error'"
                dot-color="surface"
                size="small"
              >
                <template v-slot:icon>
                  <v-icon size="18">
                    {{ decision.decision_value?.code === 'approved' ? 'mdi-check' : 'mdi-close' }}
                  </v-icon>
                </template>
                <div class="decision-item">
                  <div class="font-weight-bold">
                    {{ decision.decision_value?.name }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatDate(decision.decided_at) }} by {{ decision.decided_by?.username }}
                  </div>
                  <div v-if="decision.reason" class="mt-2 text-body-2">
                    {{ decision.reason }}
                  </div>
                </div>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Upload Document Dialog -->
    <v-dialog v-model="showUploadDialog" max-width="600" @update:model-value="handleUploadDialogOpen">
      <v-card>
        <v-card-title>Upload Documents</v-card-title>
        <v-card-text>
          <div class="mb-4">
            <label class="text-body-2 text-medium-emphasis mb-1 d-block">
              Select Files
              <span class="text-caption ml-1">Unlimited files, 50MB each (PDF, JPG, PNG)</span>
            </label>
            <input
              ref="uploadFileInputRef"
              type="file"
              multiple
              accept=".pdf,.jpg,.jpeg,.png"
              class="d-none"
              @change="handleUploadFileSelection"
            />
            <v-btn
              color="primary"
              variant="outlined"
              prepend-icon="mdi-paperclip"
              :disabled="false"
              @click="triggerUploadFileInput"
            >
              Select Files
            </v-btn>
            <span v-if="uploadFiles.length > 0" class="ml-2 text-caption">
              {{ uploadFiles.length }} files selected
            </span>
          </div>

          <v-alert
            v-if="uploadFileErrors.length > 0"
            type="error"
            density="compact"
            class="mb-4"
          >
            <ul class="mb-0 pl-4">
              <li v-for="error in uploadFileErrors" :key="error">{{ error }}</li>
            </ul>
          </v-alert>

          <v-list v-if="uploadFiles.length > 0" density="compact">
            <v-list-subheader>Selected Files ({{ uploadFiles.length }})</v-list-subheader>
            <v-list-item
              v-for="(file, index) in uploadFiles"
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
                  @click="removeUploadFile(index)"
                />
              </template>
            </v-list-item>
          </v-list>

          <v-select
            v-model="selectedDocumentType"
            :items="documentTypes"
            item-title="name"
            item-value="id"
            label="Document Type *"
            variant="outlined"
            class="mt-4"
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeUploadDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            @click="uploadDocument"
            :disabled="uploadFiles.length === 0 || !selectedDocumentType || uploading"
            :loading="uploading"
          >
            Upload {{ uploadFiles.length > 1 ? `${uploadFiles.length} Files` : 'File' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Document Preview Dialog -->
    <v-dialog v-model="showPreviewDialog" max-width="90%" max-height="90vh" scrollable>
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>{{ previewDocumentData?.file_name || 'Document Preview' }}</span>
          <div>
            <v-btn
              icon="mdi-download"
              size="small"
              variant="text"
              @click="downloadDocument(previewDocumentData?.id)"
            />
            <v-btn
              icon="mdi-close"
              size="small"
              variant="text"
              @click="closePreview"
            />
          </div>
        </v-card-title>
        <v-card-text>
          <v-progress-linear
            v-if="previewLoading"
            indeterminate
            color="primary"
            class="mb-4"
          />
          <div v-else-if="previewUrl && previewDocumentData" class="preview-container">
            <!-- PDF Preview -->
            <iframe
              v-if="isPdfFile(previewDocumentData.mime_type)"
              :src="previewUrl"
              class="preview-iframe"
              frameborder="0"
              @load="console.log('PDF iframe loaded')"
              @error="console.error('PDF iframe error')"
            />
            <!-- Image Preview -->
            <img
              v-else-if="isImageFile(previewDocumentData.mime_type)"
              :src="previewUrl"
              alt="Document preview"
              class="preview-image"
              @load="console.log('Image loaded')"
              @error="console.error('Image load error')"
            />
            <!-- Unsupported file type -->
            <v-alert
              v-else
              type="info"
              variant="tonal"
            >
              <div class="text-center pa-4">
                <v-icon size="64" class="mb-4">mdi-file-document</v-icon>
                <div class="text-h6 mb-2">Preview not available</div>
                <div class="text-body-2 mb-4">
                  File type: {{ previewDocumentData.mime_type || 'Unknown' }}
                  <br />
                  This file type cannot be previewed in the browser.
                </div>
                <v-btn
                  color="primary"
                  prepend-icon="mdi-download"
                  @click="downloadDocument(previewDocumentData.id)"
                >
                  Download File
                </v-btn>
              </div>
            </v-alert>
          </div>
          <div v-else class="text-center pa-4">
            <v-progress-circular indeterminate color="primary" />
            <div class="mt-4">Preparing preview...</div>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Assign Police Officer Dialog -->
    <v-dialog v-model="showAssignPoliceDialog" max-width="500">
      <v-card>
        <v-card-title>Assign Police Officer</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedPoliceOfficer"
            :items="policeOfficers"
            item-title="username"
            item-value="id"
            label="Select Police Officer *"
            variant="outlined"
            :loading="loadingOfficers"
            :disabled="assigning"
            required
          >
            <template v-slot:item="{ props, item }">
              <v-list-item v-bind="props">
                <template v-slot:prepend>
                  <v-icon>mdi-shield-account</v-icon>
                </template>
                <template v-slot:subtitle>
                  {{ item.raw.email || 'No email' }}
                </template>
              </v-list-item>
            </template>
          </v-select>
          <v-alert
            v-if="application?.assigned_police_officer"
            type="info"
            variant="tonal"
            density="compact"
            class="mt-3"
          >
            Currently assigned to: <strong>{{ application.assigned_police_officer.username }}</strong>
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showAssignPoliceDialog = false" :disabled="assigning">
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            @click="assignPoliceOfficer"
            :disabled="!selectedPoliceOfficer || assigning"
            :loading="assigning"
          >
            Assign
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Assign NIS Officer Dialog -->
    <v-dialog v-model="showAssignNisDialog" max-width="500">
      <v-card>
        <v-card-title>Assign NIS Officer</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedNisOfficer"
            :items="nisOfficers"
            item-title="username"
            item-value="id"
            label="Select NIS Officer *"
            variant="outlined"
            :loading="loadingOfficers"
            :disabled="assigning"
            required
          >
            <template v-slot:item="{ props, item }">
              <v-list-item v-bind="props">
                <template v-slot:prepend>
                  <v-icon>mdi-shield-account</v-icon>
                </template>
                <template v-slot:subtitle>
                  {{ item.raw.email || 'No email' }}
                </template>
              </v-list-item>
            </template>
          </v-select>
          <v-alert
            v-if="application?.assigned_nis_officer"
            type="info"
            variant="tonal"
            density="compact"
            class="mt-3"
          >
            Currently assigned to: <strong>{{ application.assigned_nis_officer.username }}</strong>
          </v-alert>
          <v-alert
            v-if="application?.status?.code !== 'police_completed' && application?.status?.code !== 'opc_review' && application?.status?.code !== 'nis_vetting'"
            type="info"
            variant="tonal"
            density="compact"
            class="mt-3"
          >
            Police vetting must be completed before assigning to NIS.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showAssignNisDialog = false" :disabled="assigning">
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            @click="assignNisOfficer"
            :disabled="!selectedNisOfficer || assigning || (application?.status?.code !== 'police_completed' && application?.status?.code !== 'opc_review' && application?.status?.code !== 'nis_vetting')"
            :loading="assigning"
          >
            Assign
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Approve Dialog -->
    <v-dialog v-model="showApproveDialog" max-width="500">
      <v-card>
        <v-card-title>Approve Application</v-card-title>
        <v-card-text>
          <v-alert color="success" variant="tonal">
            Confirming this will approve the application.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showApproveDialog = false">Cancel</v-btn>
          <v-btn color="success" @click="approveApplication">Approve</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Deny Dialog -->
    <v-dialog v-model="showDenyDialog" max-width="500">
      <v-card>
        <v-card-title>Deny Application</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="denyReason"
            label="Reason *"
            variant="outlined"
            rows="3"
            :rules="denyReasonRules"
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showDenyDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="denyApplication">Deny</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Send Back Police Vetting Dialog -->
    <v-dialog v-model="showSendBackPoliceDialog" max-width="600">
      <v-card>
        <v-card-title>Send Back Police Vetting for Clarifications</v-card-title>
        <v-card-text>
          <v-alert type="info" variant="tonal" class="mb-4">
            This will send the police vetting back to the assigned officer for clarifications. The application status will be changed back to "Police Vetting".
          </v-alert>
          <v-textarea
            v-model="sendBackReason"
            label="Reason for Sending Back *"
            variant="outlined"
            rows="5"
            :rules="sendBackReasonRules"
            hint="Please provide a clear reason why this vetting needs to be sent back for clarifications (minimum 10 characters)"
            persistent-hint
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showSendBackPoliceDialog = false" :disabled="sendingBack">
            Cancel
          </v-btn>
          <v-btn
            color="info"
            @click="sendBackPoliceVetting"
            :disabled="!sendBackReason || sendBackReason.length < 10 || sendingBack"
            :loading="sendingBack"
          >
            Send Back
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Send Back to Admin Dialog (Approver) -->
    <v-dialog v-model="showSendBackToAdminDialog" max-width="600">
      <v-card>
        <v-card-title>Send Back to Admin for Review</v-card-title>
        <v-card-text>
          <v-alert type="info" variant="tonal" class="mb-4">
            This will send the application back to admin for review. Admin can then send it back to police/NIS or make internal changes.
          </v-alert>
          <v-textarea
            v-model="sendBackToAdminReason"
            label="Reason for Sending Back *"
            variant="outlined"
            rows="5"
            :rules="sendBackToAdminReasonRules"
            hint="Please provide a clear reason why this application needs to be sent back to admin (minimum 10 characters)"
            persistent-hint
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showSendBackToAdminDialog = false" :disabled="sendingBackToAdmin">
            Cancel
          </v-btn>
          <v-btn
            color="info"
            @click="handleSendBackToAdmin"
            :disabled="!sendBackToAdminReason || sendBackToAdminReason.length < 10 || sendingBackToAdmin"
            :loading="sendingBackToAdmin"
          >
            Send Back to Admin
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Admin Handle Send-Back Dialog -->
    <v-dialog v-model="showHandleSendBackDialog" max-width="600">
      <v-card>
        <v-card-title>
          {{ handleSendBackAction === 'send_to_police' ? 'Send Back to Police' : 'Send Back to NIS' }}
        </v-card-title>
        <v-card-text>
          <v-alert type="info" variant="tonal" class="mb-4">
            This will send the application back to {{ handleSendBackAction === 'send_to_police' ? 'police' : 'NIS' }} vetting for review.
          </v-alert>
          <v-textarea
            v-model="handleSendBackReason"
            label="Additional Reason (Optional)"
            variant="outlined"
            rows="3"
            :rules="handleSendBackReasonRules"
            hint="Optional: Provide additional context for the officer"
            persistent-hint
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showHandleSendBackDialog = false" :disabled="handlingSendBack">
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            @click="handleApproverSendBack"
            :loading="handlingSendBack"
          >
            Confirm
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Send Back to Data Entry Dialog -->
    <v-dialog v-model="showSendBackToDataEntryDialog" max-width="600">
      <v-card>
        <v-card-title>Send Back to Data Entry</v-card-title>
        <v-card-text>
          <v-alert type="warning" variant="tonal" class="mb-4">
            This will return the application to data entry for correction. The record will remain editable until it is resubmitted.
          </v-alert>
          <v-textarea
            v-model="sendBackToDataEntryReason"
            label="Reason for Correction *"
            variant="outlined"
            rows="5"
            :rules="sendBackToDataEntryReasonRules"
            hint="Please provide a clear reason for the correction request (minimum 10 characters)"
            persistent-hint
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showSendBackToDataEntryDialog = false" :disabled="sendingBackToDataEntry">
            Cancel
          </v-btn>
          <v-btn
            color="warning"
            @click="sendBackToDataEntry"
            :disabled="!sendBackToDataEntryReason || sendBackToDataEntryReason.length < 10 || sendingBackToDataEntry"
            :loading="sendingBackToDataEntry"
          >
            Send Back
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Send Back NIS Vetting Dialog -->
    <v-dialog v-model="showSendBackNisDialog" max-width="600">
      <v-card>
        <v-card-title>Send Back NIS Vetting for Clarifications</v-card-title>
        <v-card-text>
          <v-alert type="info" variant="tonal" class="mb-4">
            This will send the NIS vetting back to the assigned officer for clarifications. The application status will be changed back to "NIS Vetting".
          </v-alert>
          <v-textarea
            v-model="sendBackReason"
            label="Reason for Sending Back *"
            variant="outlined"
            rows="5"
            :rules="sendBackReasonRules"
            hint="Please provide a clear reason why this vetting needs to be sent back for clarifications (minimum 10 characters)"
            persistent-hint
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showSendBackNisDialog = false" :disabled="sendingBack">
            Cancel
          </v-btn>
          <v-btn
            color="info"
            @click="sendBackNisVetting"
            :disabled="!sendBackReason || sendBackReason.length < 10 || sendingBack"
            :loading="sendingBack"
          >
            Send Back
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>

  <v-container v-else>
    <v-row justify="center">
      <v-col cols="12" sm="8" md="6">
        <v-card>
          <v-card-text class="text-center">
            <v-progress-circular indeterminate color="primary" />
            <div class="mt-4">Loading application...</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { applicationsApi, type Application } from '@/api/applications'
import { documentsApi } from '@/api/documents'
import { adminApi } from '@/api/admin'
import { decisionsApi } from '@/api/decisions'
import { usersApi } from '@/api/users'
import { useToast } from 'vue-toastification'
import { format, parseISO } from 'date-fns'
import PoliceVettingCard from '@/components/PoliceVettingCard.vue'
import NisVettingCard from '@/components/NisVettingCard.vue'
import { vettingApi, type VettingRecord } from '@/api/vetting'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const application = ref<Application | null>(null)
const documents = ref<any[]>([])
const decisions = ref<any[]>([])
const loading = ref(false)
const showUploadDialog = ref(false)
const showApproveDialog = ref(false)
const showDenyDialog = ref(false)
const uploadFileInputRef = ref<HTMLInputElement | null>(null)
const uploadFiles = ref<File[]>([])
const uploadFileErrors = ref<string[]>([])
const selectedDocumentType = ref<number | null>(null)
const documentTypes = ref<Array<{ id: number; name: string }>>([])
const uploading = ref(false)
const deletingDocumentId = ref<number | null>(null)
const showPreviewDialog = ref(false)
const previewDocumentData = ref<any>(null)
const previewUrl = ref<string | null>(null)
const previewLoading = ref(false)
const denyReason = ref('')
const vettingTab = ref('police')

// Send back state
const showSendBackPoliceDialog = ref(false)
const showSendBackNisDialog = ref(false)
const showSendBackToDataEntryDialog = ref(false)
const sendBackReason = ref('')
const sendBackToDataEntryReason = ref('')
const sendingBack = ref(false)
const sendingBackToDataEntry = ref(false)
const sendingBackToAdmin = ref(false)
const handlingSendBack = ref(false)
const forwarding = ref(false)
const policeVettingRecord = ref<any>(null)
const nisVettingRecord = ref<any>(null)
const showSendBackToAdminDialog = ref(false)
const sendBackToAdminReason = ref('')
const showHandleSendBackDialog = ref(false)
const handleSendBackAction = ref<'send_to_police' | 'send_to_nis' | null>(null)
const handleSendBackReason = ref('')

// UI validation rules (keep these in script; Vue templates cannot contain TS type annotations)
const denyReasonRules = [(v: string) => !!v || 'Reason is required']
const sendBackReasonRules = [
  (v: string) => !!v || 'Reason is required',
  (v: string) => (v && v.length >= 10) || 'Reason must be at least 10 characters',
  (v: string) => (v && v.length <= 2000) || 'Reason must not exceed 2000 characters'
]
const sendBackToAdminReasonRules = [
  (v: string) => !!v || 'Reason is required',
  (v: string) => (v && v.length >= 10) || 'Reason must be at least 10 characters',
  (v: string) => (v && v.length <= 5000) || 'Reason must not exceed 5000 characters'
]
const sendBackToDataEntryReasonRules = [
  (v: string) => !!v || 'Reason is required',
  (v: string) => (v && v.length >= 10) || 'Reason must be at least 10 characters',
  (v: string) => (v && v.length <= 5000) || 'Reason must not exceed 5000 characters'
]
const handleSendBackReasonRules = [(v: string) => !v || v.length <= 5000 || 'Reason must not exceed 5000 characters']

// Assignment state
const showAssignPoliceDialog = ref(false)
const showAssignNisDialog = ref(false)
const selectedPoliceOfficer = ref<number | null>(null)
const selectedNisOfficer = ref<number | null>(null)
const policeOfficers = ref<Array<{ id: number; username: string; email?: string }>>([])
const nisOfficers = ref<Array<{ id: number; username: string; email?: string }>>([])
const loadingOfficers = ref(false)
const assigning = ref(false)

const allowedActions = computed(() => application.value?.allowed_actions ?? [])

function hasAllowedAction(action: string): boolean {
  return allowedActions.value.includes(action)
}

const canEditApplication = computed(() => hasAllowedAction('edit_application'))
const canDoPoliceVetting = computed(() => hasAllowedAction('conduct_police_vetting'))
const canDoNisVetting = computed(() => hasAllowedAction('conduct_nis_vetting'))
const canSendBackPolice = computed(() => hasAllowedAction('send_back_police_vetting'))
const canSendBackNis = computed(() => hasAllowedAction('send_back_nis_vetting'))
const canSendBackToDataEntry = computed(() => hasAllowedAction('send_back_to_data_entry'))
const canForwardToApproval = computed(() => hasAllowedAction('forward_to_approval'))
const canApprove = computed(() => hasAllowedAction('approve_application'))
const canDeny = computed(() => hasAllowedAction('deny_application'))
const canSendBackToAdmin = computed(() => hasAllowedAction('send_back_to_admin'))
const canHandleApproverSendBack = computed(() => hasAllowedAction('handle_approver_send_back'))
const canAssignPolice = computed(() => hasAllowedAction('assign_police_officer'))
const canAssignNis = computed(() => hasAllowedAction('assign_nis_officer'))
const canUploadDocuments = computed(() =>
  hasAllowedAction('upload_documents') && authStore.hasAnyRole(['admin', 'opc_data_entry'])
)
const canDownloadDocuments = computed(() => hasAllowedAction('download_documents'))
const canDeleteDocuments = computed(() => hasAllowedAction('delete_documents'))
const currentApplicationId = computed(() => Number(route.params.id))
const hasSidebarActions = computed(() => [
  canEditApplication.value,
  canSendBackToDataEntry.value,
  canDoPoliceVetting.value,
  canDoNisVetting.value,
  canAssignPolice.value,
  canAssignNis.value,
  canForwardToApproval.value,
  canApprove.value,
  canDeny.value,
  canSendBackToAdmin.value,
  canSendBackPolice.value,
  canSendBackNis.value,
  canHandleApproverSendBack.value
].some(Boolean))

const canDeleteDocument = (doc: any) => {
  if (!canDeleteDocuments.value) return false
  if (doc.uploaded_by?.id === authStore.user?.id) return true
  if (authStore.hasPermission('delete documents') && application.value?.created_by?.id === authStore.user?.id) {
    return true
  }
  return authStore.isAdmin
}

function getStatusColor(statusCode: string) {
  const colors: Record<string, string> = {
    'pending': 'info',
    'returned_to_data_entry': 'warning',
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
  return format(new Date(date), 'MMM dd, yyyy HH:mm')
}

function formatDateOnly(date?: string | null) {
  if (!date) return 'Not provided'
  return format(parseISO(date), 'MMM dd, yyyy')
}

function formatFileSize(bytes: number) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

function getDocumentIcon(mimeType: string) {
  if (mimeType?.includes('pdf')) return 'mdi-file-pdf-box'
  if (mimeType?.includes('image')) return 'mdi-file-image'
  return 'mdi-file-document'
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

function validateUploadFile(file: File): string | null {
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

function triggerUploadFileInput() {
  uploadFileInputRef.value?.click()
}

function handleUploadFileSelection(event: Event) {
  const target = event.target as HTMLInputElement
  const files = target.files

  if (!files || files.length === 0) {
    return
  }

  uploadFileErrors.value = []

  // Convert FileList to Array
  const newFiles = Array.from(files)

  // Validate and add new files
  newFiles.forEach((file) => {
    // Check for duplicates
    const isDuplicate = uploadFiles.value.some(existingFile =>
      existingFile.name === file.name && existingFile.size === file.size
    )

    if (isDuplicate) {
      uploadFileErrors.value.push(`${file.name}: This file is already selected.`)
      return
    }

    const error = validateUploadFile(file)
    if (error) {
      uploadFileErrors.value.push(error)
    } else {
      uploadFiles.value.push(file)
    }
  })

  // Reset input to allow selecting the same files again if needed
  if (uploadFileInputRef.value) {
    uploadFileInputRef.value.value = ''
  }
}

function removeUploadFile(index: number) {
  uploadFiles.value.splice(index, 1)
  uploadFileErrors.value = []
}

function handleUploadDialogOpen(value: boolean) {
  if (value && documentTypes.value.length === 0) {
    // Fetch document types when dialog opens if not already loaded
    fetchDocumentTypes()
  }
}

function closeUploadDialog() {
  showUploadDialog.value = false
  uploadFiles.value = []
  uploadFileErrors.value = []
  selectedDocumentType.value = null
  if (uploadFileInputRef.value) {
    uploadFileInputRef.value.value = ''
  }
}

async function fetchDocumentTypes() {
  try {
    const response = await adminApi.getDocumentTypes()
    console.log('Document types response:', response.data)
    if (response.data.success) {
      // Filter for active document types and map to select format
      documentTypes.value = response.data.data
        .filter((type: any) => type.is_active !== false) // Include if is_active is true or undefined
        .map((type: any) => ({
          id: type.id,
          name: type.name
        }))
      console.log('Document types loaded:', documentTypes.value)
    } else {
      console.error('Failed to fetch document types:', response.data)
      toast.error('Failed to load document types')
    }
  } catch (error: any) {
    console.error('Error fetching document types:', error)
    console.error('Error response:', error.response)
    toast.error('Failed to load document types. Please refresh the page.')
  }
}

async function fetchApplication() {
  loading.value = true
  try {
    const response = await applicationsApi.get(Number(route.params.id))
    if (response.data.success) {
      application.value = response.data.data
      // Fetch vetting records if user can view vetting records, or if user is assigned officer who can vet
      if (
        application.value &&
        (authStore.hasPermission('view vetting records') ||
          (authStore.hasPermission('conduct police vetting') &&
            application.value.assigned_police_officer?.id === authStore.user?.id) ||
          (authStore.hasPermission('conduct nis vetting') &&
            application.value.assigned_nis_officer?.id === authStore.user?.id))
      ) {
        await Promise.all([
          fetchPoliceVetting(),
          fetchNisVetting()
        ])
      }
    }
  } catch (error) {
    toast.error('Failed to fetch application')
    router.push({ name: 'Dashboard' })
  } finally {
    loading.value = false
  }
}

async function fetchPoliceVetting() {
  try {
    const response = await vettingApi.getPoliceVetting(Number(route.params.id))
    if (response.data.success) {
      policeVettingRecord.value = response.data.data
    }
  } catch (error) {
    // Vetting might not exist yet, that's okay
    policeVettingRecord.value = null
  }
}

async function fetchNisVetting() {
  try {
    const response = await vettingApi.getNisVetting(Number(route.params.id))
    if (response.data.success) {
      nisVettingRecord.value = response.data.data
    }
  } catch (error) {
    // Vetting might not exist yet, that's okay
    nisVettingRecord.value = null
  }
}

async function fetchDocuments() {
  try {
    const response = await documentsApi.list(Number(route.params.id))
    if (response.data.success) {
      // Filter out vetting report documents (they should only appear in Vetting Records section)
      documents.value = response.data.data.filter((doc: any) =>
        doc.document_type?.code !== 'police_vetting_report' &&
        doc.document_type?.code !== 'nis_vetting_report'
      )
    }
  } catch (error) {
    console.error('Error fetching documents:', error)
  }
}

async function fetchDecisions() {
  try {
    const response = await decisionsApi.history(Number(route.params.id))
    if (response.data.success) {
      decisions.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching decisions:', error)
  }
}

async function uploadDocument() {
  if (uploadFiles.value.length === 0) {
    toast.error('Please select at least one file')
    return
  }

  if (!selectedDocumentType.value) {
    toast.error('Please select a document type')
    return
  }

  uploading.value = true

  try {
    // Upload files one by one
    const uploadPromises = uploadFiles.value.map(async (file) => {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('document_type_id', selectedDocumentType.value!.toString())

      return documentsApi.upload(Number(route.params.id), formData)
    })

    await Promise.all(uploadPromises)

    toast.success(`Successfully uploaded ${uploadFiles.value.length} file(s)`)
    closeUploadDialog()
    fetchDocuments()
  } catch (error: any) {
    console.error('Upload error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to upload document(s)'
    toast.error(errorMessage)
  } finally {
    uploading.value = false
  }
}

function isPdfFile(mimeType?: string | null): boolean {
  if (!mimeType) return false
  const lowerMime = mimeType.toLowerCase()
  return lowerMime.includes('pdf') || lowerMime === 'application/pdf'
}

function isImageFile(mimeType?: string | null): boolean {
  if (!mimeType) return false
  const lowerMime = mimeType.toLowerCase()
  return lowerMime.includes('image') || ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'].includes(lowerMime)
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
      console.log('Creating blob URL with content type:', contentType)
      const blob = new Blob([response.data], { type: contentType })
      previewUrl.value = window.URL.createObjectURL(blob)
      previewUrl.value = window.URL.createObjectURL(blob)
      console.log('Preview URL created:', previewUrl.value)
      console.log('Document MIME type:', doc.mime_type)
      console.log('Content-Type header:', response.headers['content-type'])
      console.log('Is PDF?', isPdfFile(doc.mime_type))
      console.log('Is Image?', isImageFile(doc.mime_type))
      console.log('Preview document data:', previewDocumentData.value)
      console.log('Preview URL value:', previewUrl.value)
      console.log('Show preview dialog:', showPreviewDialog.value)
    } else {
      // If it's not a blob, try to create one from the data
      const blob = new Blob([response.data], { type: doc.mime_type || 'application/octet-stream' })
      previewUrl.value = window.URL.createObjectURL(blob)
      console.log('Preview URL created (non-blob):', previewUrl.value)
    }
  } catch (error: any) {
    console.error('Preview error:', error)
    console.error('Error response:', error.response)

    let errorMessage = 'Failed to preview document'

    // Handle blob error responses (when responseType is 'blob' but server returns JSON)
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        console.log('Error response text:', text)
        const errorData = JSON.parse(text)
        console.log('Parsed error data:', errorData)
        errorMessage = errorData.error?.message || errorData.message || errorMessage
      } catch (parseError) {
        console.error('Failed to parse error blob:', parseError)
        // If parsing fails, use default message based on status
        errorMessage = error.response?.status === 403 ? 'You do not have permission to preview this document' :
                      error.response?.status === 404 ? 'Document not found' :
                      error.response?.status === 500 ? 'Server error occurred. Please check the console for details.' :
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
    fetchDocuments()
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

async function approveApplication() {
  try {
    await decisionsApi.approve(Number(route.params.id), {})
    toast.success('Application approved successfully')
    showApproveDialog.value = false
    fetchApplication()
    fetchDecisions()
  } catch (error: any) {
    toast.error('Failed to approve application')
  }
}

async function denyApplication() {
  if (!denyReason.value) {
    toast.error('Please provide a reason')
    return
  }

  try {
    await decisionsApi.deny(Number(route.params.id), { reason: denyReason.value })
    toast.success('Application denied')
    showDenyDialog.value = false
    denyReason.value = ''
    fetchApplication()
    fetchDecisions()
  } catch (error: any) {
    toast.error('Failed to deny application')
  }
}

function editApplication() {
  if (!Number.isFinite(currentApplicationId.value)) {
    toast.error('Application not found')
    return
  }

  router.push({
    name: 'EditApplication',
    params: { id: currentApplicationId.value },
    query: { returnTo: route.fullPath }
  })
}

async function fetchOfficers() {
  loadingOfficers.value = true
  try {
    // Fetch police officers
    const policeResponse = await usersApi.list({ role: 'police_officer', is_active: true, per_page: 100 })
    console.log('Police officers response:', policeResponse.data)
    if (policeResponse.data.success) {
      // API returns: { success: true, data: { data: [...] } }
      const users = policeResponse.data.data?.data || []
      policeOfficers.value = users.map((user: any) => ({
        id: user.id,
        username: user.username,
        email: user.email || ''
      }))
      console.log('Police officers loaded:', policeOfficers.value)
    } else {
      console.error('Police officers API returned success: false')
      toast.error('Failed to load police officers')
    }

    // Fetch NIS officers
    const nisResponse = await usersApi.list({ role: 'nis_officer', is_active: true, per_page: 100 })
    console.log('NIS officers response:', nisResponse.data)
    if (nisResponse.data.success) {
      // API returns: { success: true, data: { data: [...] } }
      const users = nisResponse.data.data?.data || []
      nisOfficers.value = users.map((user: any) => ({
        id: user.id,
        username: user.username,
        email: user.email || ''
      }))
      console.log('NIS officers loaded:', nisOfficers.value)
    } else {
      console.error('NIS officers API returned success: false')
      toast.error('Failed to load NIS officers')
    }
  } catch (error: any) {
    console.error('Error fetching officers:', error)
    console.error('Error response:', error.response)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to load officers'
    toast.error(errorMessage)
  } finally {
    loadingOfficers.value = false
  }
}

async function assignPoliceOfficer() {
  if (!selectedPoliceOfficer.value || !Number.isFinite(currentApplicationId.value)) return

  assigning.value = true
  try {
    await applicationsApi.assignPolice(currentApplicationId.value, {
      police_officer_id: selectedPoliceOfficer.value
    })
    toast.success('Application assigned to police officer successfully')
    showAssignPoliceDialog.value = false
    selectedPoliceOfficer.value = null
    try {
      await fetchApplication()
    } catch (refreshError) {
      console.warn('Assigned police officer, but failed to refresh application:', refreshError)
      toast.info('Assignment saved. Refresh the page to load the latest application state.')
    }
  } catch (error: any) {
    console.error('Assign police error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to assign police officer'
    toast.error(errorMessage)
  } finally {
    assigning.value = false
  }
}

async function assignNisOfficer() {
  if (!selectedNisOfficer.value || !Number.isFinite(currentApplicationId.value)) return

  assigning.value = true
  try {
    await applicationsApi.assignNis(currentApplicationId.value, {
      nis_officer_id: selectedNisOfficer.value
    })
    toast.success('Application assigned to NIS officer successfully')
    showAssignNisDialog.value = false
    selectedNisOfficer.value = null
    try {
      await fetchApplication()
    } catch (refreshError) {
      console.warn('Assigned NIS officer, but failed to refresh application:', refreshError)
      toast.info('Assignment saved. Refresh the page to load the latest application state.')
    }
  } catch (error: any) {
    console.error('Assign NIS error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to assign NIS officer'
    toast.error(errorMessage)
  } finally {
    assigning.value = false
  }
}

// Watch for dialog open to fetch officers
watch(showAssignPoliceDialog, (isOpen) => {
  if (isOpen && policeOfficers.value.length === 0) {
    fetchOfficers()
  }
  if (isOpen && application.value?.assigned_police_officer) {
    selectedPoliceOfficer.value = application.value.assigned_police_officer.id
  } else if (!isOpen) {
    selectedPoliceOfficer.value = null
  }
})

watch(showAssignNisDialog, (isOpen) => {
  if (isOpen && nisOfficers.value.length === 0) {
    fetchOfficers()
  }
  if (isOpen && application.value?.assigned_nis_officer) {
    selectedNisOfficer.value = application.value.assigned_nis_officer.id
  } else if (!isOpen) {
    selectedNisOfficer.value = null
  }
})

async function sendBackPoliceVetting() {
  if (!sendBackReason.value || sendBackReason.value.length < 10) {
    toast.error('Please provide a reason (minimum 10 characters)')
    return
  }

  if (!policeVettingRecord.value) {
    toast.error('Police vetting record not found')
    return
  }

  sendingBack.value = true
  try {
    const response = await vettingApi.sendBack(policeVettingRecord.value.id, sendBackReason.value)
    if (response.data.success) {
      toast.success('Police vetting sent back for clarifications')
      showSendBackPoliceDialog.value = false
      sendBackReason.value = ''
      await fetchApplication()
      await fetchPoliceVetting()
    }
  } catch (error: any) {
    console.error('Send back police error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to send back police vetting'
    toast.error(errorMessage)
  } finally {
    sendingBack.value = false
  }
}

async function sendBackNisVetting() {
  if (!sendBackReason.value || sendBackReason.value.length < 10) {
    toast.error('Please provide a reason (minimum 10 characters)')
    return
  }

  if (!nisVettingRecord.value) {
    toast.error('NIS vetting record not found')
    return
  }

  sendingBack.value = true
  try {
    const response = await vettingApi.sendBack(nisVettingRecord.value.id, sendBackReason.value)
    if (response.data.success) {
      toast.success('NIS vetting sent back for clarifications')
      showSendBackNisDialog.value = false
      sendBackReason.value = ''
      await fetchApplication()
      await fetchNisVetting()
    }
  } catch (error: any) {
    console.error('Send back NIS error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to send back NIS vetting'
    toast.error(errorMessage)
  } finally {
    sendingBack.value = false
  }
}

async function forwardToApproval() {
  if (!Number.isFinite(currentApplicationId.value)) return

  forwarding.value = true
  try {
    const response = await applicationsApi.forwardToApproval(currentApplicationId.value)
    if (response.data.success) {
      toast.success('Application forwarded to approval successfully')
      await fetchApplication()
      await Promise.all([
        fetchPoliceVetting(),
        fetchNisVetting()
      ])
    }
  } catch (error: any) {
    console.error('Forward to approval error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to forward application to approval'
    toast.error(errorMessage)
  } finally {
    forwarding.value = false
  }
}

async function handleSendBackToAdmin() {
  if (!sendBackToAdminReason.value || sendBackToAdminReason.value.length < 10) {
    toast.error('Please provide a reason (minimum 10 characters)')
    return
  }

  if (!Number.isFinite(currentApplicationId.value)) {
    toast.error('Application not found')
    return
  }

  sendingBackToAdmin.value = true
  try {
    const response = await applicationsApi.sendBackToAdmin(currentApplicationId.value, sendBackToAdminReason.value)
    if (response.data.success) {
      toast.success('Application sent back to admin successfully')
      showSendBackToAdminDialog.value = false
      sendBackToAdminReason.value = ''
      await fetchApplication()
    }
  } catch (error: any) {
    console.error('Send back to admin error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to send back application to admin'
    toast.error(errorMessage)
  } finally {
    sendingBackToAdmin.value = false
  }
}

async function sendBackToDataEntry() {
  if (!sendBackToDataEntryReason.value || sendBackToDataEntryReason.value.length < 10) {
    toast.error('Please provide a reason (minimum 10 characters)')
    return
  }

  if (!Number.isFinite(currentApplicationId.value)) {
    toast.error('Application not found')
    return
  }

  sendingBackToDataEntry.value = true
  try {
    const response = await applicationsApi.sendBackToDataEntry(currentApplicationId.value, sendBackToDataEntryReason.value)
    if (response.data.success) {
      toast.success('Application sent back to data entry successfully')
      showSendBackToDataEntryDialog.value = false
      sendBackToDataEntryReason.value = ''
      await fetchApplication()
    }
  } catch (error: any) {
    console.error('Send back to data entry error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to send back application to data entry'
    toast.error(errorMessage)
  } finally {
    sendingBackToDataEntry.value = false
  }
}

async function handleApproverSendBack() {
  if (!handleSendBackAction.value || !Number.isFinite(currentApplicationId.value)) return

  handlingSendBack.value = true
  try {
    const response = await applicationsApi.handleApproverSendBack(
      currentApplicationId.value,
      handleSendBackAction.value,
      handleSendBackReason.value || undefined
    )
    if (response.data.success) {
      toast.success(response.data.message || 'Action completed successfully')
      showHandleSendBackDialog.value = false
      handleSendBackAction.value = null
      handleSendBackReason.value = ''
      await fetchApplication()
      await Promise.all([
        fetchPoliceVetting(),
        fetchNisVetting()
      ])
    }
  } catch (error: any) {
    console.error('Handle approver send-back error:', error)
    const errorMessage = error.response?.data?.error?.message ||
                        error.response?.data?.message ||
                        'Failed to handle send-back'
    toast.error(errorMessage)
  } finally {
    handlingSendBack.value = false
  }
}

function openHandleSendBackDialog(action: 'send_to_police' | 'send_to_nis') {
  handleSendBackAction.value = action
  handleSendBackReason.value = ''
  showHandleSendBackDialog.value = true
}

function goBack() {
  const returnTo = route.query.returnTo
  if (typeof returnTo === 'string' && returnTo) {
    router.push(returnTo)
    return
  }

  if (window.history.length > 1) {
    router.back()
    return
  }

  router.push({ name: 'Dashboard' })
}

onMounted(() => {
  fetchApplication()
  fetchDocuments()
  fetchDecisions()
  fetchDocumentTypes()
})

// Watch for route changes to refresh data when navigating back from edit
watch(() => route.params.id, (newId, oldId) => {
  if (newId && newId !== oldId) {
    fetchApplication()
    fetchDocuments()
    fetchDecisions()
  }
}, { immediate: false })

// Also watch for when component becomes active (if using keep-alive)
watch(() => route.fullPath, () => {
  if (route.name === 'ApplicationDetail') {
    fetchDocuments()
  }
})

// Cleanup preview URL on unmount
onUnmounted(() => {
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
  }
})
</script>

<style scoped>
.application-detail-page {
  padding-bottom: 16px;
}

.detail-hero {
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(circle at top right, rgba(18, 56, 95, 0.08), transparent 32%),
    linear-gradient(135deg, #ffffff 0%, #f8fafc 58%, #edf4f9 100%);
  border: 1px solid rgba(18, 56, 95, 0.12);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
}

.detail-hero__inner {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  align-items: flex-start;
  padding: 28px;
}

.detail-hero__copy {
  min-width: 0;
}

.detail-eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-size: 0.72rem;
  font-weight: 700;
  color: rgba(18, 56, 95, 0.62);
  margin-bottom: 10px;
}

.detail-hero__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}

.detail-hero__actions {
  flex-shrink: 0;
}

.detail-hero__button {
  min-width: 160px;
}

.detail-card {
  border: 1px solid rgba(18, 56, 95, 0.1);
  border-radius: 20px;
  overflow: hidden;
}

.detail-card__title {
  padding-bottom: 8px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px 20px;
}

.info-item {
  min-width: 0;
  padding: 14px 16px;
  border-radius: 16px;
  background: linear-gradient(180deg, rgba(18, 56, 95, 0.03), rgba(18, 56, 95, 0.05));
  border: 1px solid rgba(18, 56, 95, 0.08);
}

.info-item--highlight {
  background: linear-gradient(180deg, rgba(15, 118, 110, 0.08), rgba(15, 118, 110, 0.04));
  border-color: rgba(15, 118, 110, 0.16);
}

.info-item--wide {
  grid-column: 1 / -1;
}

.info-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(71, 85, 105, 0.82);
  margin-bottom: 6px;
}

.info-value {
  font-size: 1rem;
  line-height: 1.55;
  color: rgba(15, 23, 42, 0.94);
  word-break: break-word;
}

.info-value--rich {
  white-space: pre-wrap;
}

.document-list {
  border: 1px solid rgba(18, 56, 95, 0.1);
  border-radius: 16px;
  overflow: hidden;
}

.empty-state {
  display: flex;
  min-height: 160px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 24px;
}

.empty-state--neutral {
  border: 1px dashed rgba(18, 56, 95, 0.18);
  background: linear-gradient(180deg, rgba(18, 56, 95, 0.02), rgba(18, 56, 95, 0.04));
}

.vetting-tabs {
  margin-bottom: 12px;
}

.action-card {
  position: sticky;
  top: 96px;
}

.action-section {
  margin-bottom: 20px;
}

.action-section:last-child {
  margin-bottom: 0;
}

.action-section__label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: rgba(18, 56, 95, 0.62);
  margin-bottom: 10px;
}

.action-button {
  border-radius: 14px;
}

.side-meta {
  display: grid;
  gap: 14px;
}

.side-meta__item {
  padding-bottom: 14px;
  border-bottom: 1px solid rgba(18, 56, 95, 0.08);
}

.side-meta__item:last-child {
  padding-bottom: 0;
  border-bottom: 0;
}

.decision-item {
  padding: 6px 0 18px;
}

.preview-container {
  width: 100%;
  height: 70vh;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: auto;
}

.preview-iframe {
  width: 100%;
  height: 100%;
  min-height: 600px;
}

.preview-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
}

@media (max-width: 960px) {
  .detail-hero__inner {
    flex-direction: column;
  }

  .action-card {
    position: static;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .info-item--wide {
    grid-column: auto;
  }
}

@media (max-width: 600px) {
  .application-detail-page {
    padding-bottom: 8px;
  }

  .detail-hero__inner {
    padding: 16px;
  }

  .detail-hero__actions,
  .detail-hero__button {
    width: 100%;
  }

  .detail-hero__meta {
    gap: 8px;
  }

  .action-section {
    margin-bottom: 16px;
  }
}
</style>
