<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Application;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * List documents for an application
     */
    public function index(string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            // Permission is handled by route middleware; enforce application-level access here
            $user = request()->user();
            if ($user) {
                $this->documentService->assertCanAccessApplication($application, $user);
            }
            $documents = Document::where('application_id', $application->id)
                ->with(['documentType', 'uploadedBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Application not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve documents',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Upload a document
     */
    public function store(StoreDocumentRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $this->documentService->assertCanAccessApplication($application, $request->user());
            $file = $request->file('file');

            $document = $this->documentService->uploadDocument(
                $application,
                $file,
                $request->document_type_id,
                $request->user(),
                $request->description
            );

            return response()->json([
                'success' => true,
                'data' => $document,
                'message' => 'Document uploaded successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Application not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to upload document', [
                'application_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPLOAD_FAILED',
                    'message' => 'Failed to upload document',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Get document details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $document = Document::with(['documentType', 'uploadedBy', 'application'])
                ->findOrFail($id);
            // Permission is handled by route middleware; enforce application-level access here
            $user = request()->user();
            if ($user) {
                $this->documentService->assertCanAccessDocument($document, $user);
            }

            return response()->json([
                'success' => true,
                'data' => $document,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Document not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve document',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Delete a document
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            $this->documentService->deleteDocument($document, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Document not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete document', [
                'document_id' => $id,
                'user_id' => request()->user()?->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'message' => 'Failed to delete document',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Download a document
     */
public function download(string $id): StreamedResponse|JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            $filePath = $this->documentService->getDownloadPath($document, request()->user());

            return Storage::disk('documents')->download($document->file_path, $document->file_name);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Document not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to download document', [
                'document_id' => $id,
                'user_id' => request()->user()?->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DOWNLOAD_FAILED',
                    'message' => 'Failed to download document',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 403);
        }
    }

    /**
     * Preview a document (returns file with inline content-disposition)
     */
    public function preview(string $id): Response|JsonResponse
    {
        try {
            $document = Document::with('application')->findOrFail($id);
            $user = request()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Authentication required',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 401);
            }

            // Permission check handled by route middleware; application-level access enforced via service
            $this->documentService->assertCanAccessDocument($document, $user);

            // Check if file exists
            if (!Storage::disk('documents')->exists($document->file_path)) {
                \Log::warning('Document file not found in storage', [
                    'document_id' => $document->id,
                    'file_path' => $document->file_path
                ]);

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'FILE_NOT_FOUND',
                        'message' => 'Document file not found in storage',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 404);
            }

            try {
                $file = Storage::disk('documents')->get($document->file_path);
            } catch (\Exception $fileError) {
                \Log::error('Failed to read document file: ' . $fileError->getMessage(), [
                    'document_id' => $document->id,
                    'file_path' => $document->file_path,
                    'error' => $fileError->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'FILE_READ_ERROR',
                        'message' => 'Failed to read document file',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 500);
            }

            if ($file === false || $file === null) {
                \Log::error('Document file returned null or false', [
                    'document_id' => $document->id,
                    'file_path' => $document->file_path
                ]);

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'FILE_READ_ERROR',
                        'message' => 'Failed to read document file',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 500);
            }

            try {
                $mimeType = $document->mime_type ?: Storage::disk('documents')->mimeType($document->file_path) ?: 'application/octet-stream';
            } catch (\Exception $mimeError) {
                \Log::warning('Failed to get MIME type, using default', [
                    'document_id' => $document->id,
                    'error' => $mimeError->getMessage()
                ]);
                $mimeType = $document->mime_type ?: 'application/octet-stream';
            }

            // Log preview (not download) - wrap in try-catch to prevent logging errors from breaking preview
            try {
                $this->documentService->logPreview($document, $user);
            } catch (\Exception $logError) {
                \Log::warning('Failed to log document preview', [
                    'document_id' => $document->id,
                    'error' => $logError->getMessage()
                ]);
                // Continue with preview even if logging fails
            }

            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $document->file_name . '"')
                ->header('Cache-Control', 'private, max-age=3600');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Document not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Preview document error', [
                'document_id' => $id,
                'user_id' => request()->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PREVIEW_FAILED',
                    'message' => 'Failed to preview document',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }
}
