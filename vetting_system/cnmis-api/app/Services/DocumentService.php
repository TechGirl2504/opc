<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DocumentService
{
    protected AuditService $auditService;
    protected NotificationService $notificationService;

    public function __construct(AuditService $auditService, NotificationService $notificationService)
    {
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
    }
    /**
     * Upload a document for an application
     */
    public function uploadDocument(Application $application, UploadedFile $file, int $documentTypeId, User $user, ?string $description = null): Document
    {
        try {
            $documentType = DocumentType::findOrFail($documentTypeId);

            // Validate file size
            if ($file->getSize() > $documentType->max_file_size) {
                throw new \Exception('File size exceeds maximum allowed size of ' . ($documentType->max_file_size / 1024 / 1024) . 'MB');
            }

            // Validate MIME type
            if ($documentType->allowed_mime_types && !in_array($file->getMimeType(), $documentType->allowed_mime_types)) {
                throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $documentType->allowed_mime_types));
            }

            // Generate file path: documents/{year}/{month}/{application_id}/
            $year = date('Y');
            $month = date('m');
            $path = "{$year}/{$month}/{$application->id}";

            // Generate unique filename: {timestamp}_{sanitized_filename}_{random}.{ext}
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $sanitized = Str::slug($originalName);
            $random = Str::random(8);
            $filename = time() . '_' . $sanitized . '_' . $random . '.' . $extension;

            // Store file using documents disk
            $filePath = $file->storeAs($path, $filename, 'documents');

            // Create document record
            $document = Document::create([
                'application_id' => $application->id,
                'document_type_id' => $documentTypeId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => $user->id,
                'description' => $description,
            ]);

            // Log audit
            $this->auditService->logCreate($user, Document::class, $document->id, $document->toArray());

            // Send notification
            $this->notificationService->notifyDocumentUploaded($application, $user, $documentType->name);

            return $document->load(['documentType', 'uploadedBy']);
        } catch (\Exception $e) {
            Log::error('Failed to upload document: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get document download path (with access control)
     */
    public function getDownloadPath(Document $document, User $user): string
    {
        // Verify user has access to the application
        $application = $document->application;
        
        // Check access based on role
        $hasAccess = false;
        
        if ($user->hasRole('admin')) {
            $hasAccess = true;
        } elseif ($user->hasRole('opc_data_entry') || $user->hasRole('opc_approver')) {
            $hasAccess = true; // OPC users can access all applications
        } elseif ($user->hasRole('police_officer')) {
            $hasAccess = $application->assigned_police_officer_id === $user->id;
        } elseif ($user->hasRole('nis_officer')) {
            $hasAccess = $application->assigned_nis_officer_id === $user->id;
        }

        if (!$hasAccess) {
            throw new \Exception('You do not have permission to access this document');
        }

        // Log download
        $this->auditService->log($user, 'document_downloaded', Document::class, $document->id);

        return Storage::disk('documents')->path($document->file_path);
    }

    /**
     * Log document preview
     */
    public function logPreview(Document $document, User $user): void
    {
        $this->auditService->log($user, 'document_previewed', Document::class, $document->id);
    }

    /**
     * Delete a document
     */
    public function deleteDocument(Document $document, User $user): bool
    {
        try {
            // Verify user has permission
            if (!$user->hasRole('admin') && $document->uploaded_by !== $user->id) {
                throw new \Exception('You do not have permission to delete this document');
            }

            // Delete file from storage
            if (Storage::disk('documents')->exists($document->file_path)) {
                Storage::disk('documents')->delete($document->file_path);
            }

            // Log audit
            $this->auditService->logDelete($user, Document::class, $document->id, $document->toArray());

            // Delete record
            $document->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete document: ' . $e->getMessage());
            throw $e;
        }
    }

}

