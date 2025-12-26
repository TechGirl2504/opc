<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApplicationController extends Controller
{
    protected ApplicationService $applicationService;
    protected DocumentService $documentService;

    public function __construct(ApplicationService $applicationService, DocumentService $documentService)
    {
        $this->applicationService = $applicationService;
        $this->documentService = $documentService;
    }

    /**
     * Display a listing of applications with filters, search, and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'status_id' => $request->get('status_id'),
                'status' => $request->get('status'),
                'created_by' => $request->get('created_by'),
                'assigned_police_officer_id' => $request->get('assigned_police_officer_id'),
                'assigned_nis_officer_id' => $request->get('assigned_nis_officer_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'order_by' => $request->get('order_by', 'created_at'),
                'order_dir' => $request->get('order_dir', 'desc'),
            ];

            $perPage = min($request->get('per_page', 20), 100); // Max 100 per page

            $applications = $this->applicationService->getApplications($filters, $perPage, $request->user());

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $applications->items(),
                ],
                'meta' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                    'from' => $applications->firstItem(),
                    'to' => $applications->lastItem(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve applications',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Store a newly created application
     */
    public function store(StoreApplicationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $documents = $request->file('documents', []);
            
            // Remove documents from validated data (not part of application creation)
            unset($validated['documents']);
            
            $application = $this->applicationService->createApplication(
                $validated,
                $request->user()
            );

            // Handle document uploads if provided
            if (!empty($documents)) {
                $defaultDocumentType = \App\Models\DocumentType::where('code', 'supporting_document')->first();
                
                foreach ($documents as $file) {
                    if ($file->isValid()) {
                        $this->documentService->uploadDocument(
                            $application,
                            $file,
                            $defaultDocumentType?->id ?? 1, // Fallback to ID 1 if not found
                            $request->user(),
                            'Supporting document uploaded with application'
                        );
                    }
                }
            }

            // Reload application with documents
            $application->load(['documents.documentType', 'documents.uploadedBy']);

            return response()->json([
                'success' => true,
                'data' => $application,
                'message' => 'Application created successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CREATION_FAILED',
                    'message' => 'Failed to create application: ' . $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Display the specified application
     */
    public function show(string $id): JsonResponse
    {
        try {
            $application = Application::with([
                'status',
                'createdBy',
                'assignedPoliceOfficer',
                'assignedNisOfficer',
                'assignedOpcApprover',
                'documents.documentType',
                'documents.uploadedBy',
                'vettingRecords.vettingType',
                'vettingRecords.status',
                'vettingRecords.conductedBy',
                'vettingRecords.recommendation',
                'decisions.decisionType',
                'decisions.decisionValue',
                'decisions.decidedBy',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $application,
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
                    'message' => 'Failed to retrieve application',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Update the specified application
     */
    public function update(UpdateApplicationRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $application = $this->applicationService->updateApplication(
                $application,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $application,
                'message' => 'Application updated successfully',
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
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Failed to update application: ' . $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Remove the specified application (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $application->delete();

            return response()->json([
                'success' => true,
                'message' => 'Application deleted successfully',
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
                    'code' => 'DELETE_FAILED',
                    'message' => 'Failed to delete application',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Assign application to police officer
     */
    public function assignPolice(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'police_officer_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $application = Application::findOrFail($id);
            $application = $this->applicationService->assignToPolice(
                $application,
                $request->police_officer_id,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $application,
                'message' => 'Application assigned to police officer successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Application or user not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ASSIGNMENT_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Assign application to NIS officer
     */
    public function assignNis(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'nis_officer_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $application = Application::findOrFail($id);
            $application = $this->applicationService->assignToNis(
                $application,
                $request->nis_officer_id,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $application,
                'message' => 'Application assigned to NIS officer successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Application or user not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ASSIGNMENT_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Get application status history
     */
    public function statusHistory(string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $history = $this->applicationService->getStatusHistory($application);

            return response()->json([
                'success' => true,
                'data' => $history,
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
                    'message' => 'Failed to retrieve status history',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Send back application from OPC approver to admin
     */
    public function sendBackToAdmin(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|min:10|max:5000',
            ]);

            $application = Application::findOrFail($id);
            $application = $this->applicationService->sendBackToAdmin($application, $request->get('reason'), $request->user());

            return response()->json([
                'success' => true,
                'data' => $application,
                'message' => 'Application sent back to admin successfully',
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SEND_BACK_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Admin handles approver send-back
     */
    public function handleApproverSendBack(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'action' => 'required|string|in:send_to_police,send_to_nis,allow_editing',
                'reason' => 'nullable|string|max:5000',
            ]);

            $application = Application::findOrFail($id);
            $application = $this->applicationService->handleApproverSendBack(
                $application,
                $request->get('action'),
                $request->get('reason'),
                $request->user()
            );

            $actionMessages = [
                'send_to_police' => 'Application sent back to police vetting',
                'send_to_nis' => 'Application sent back to NIS vetting',
                'allow_editing' => 'Application is now available for editing',
            ];

            return response()->json([
                'success' => true,
                'data' => $application,
                'message' => $actionMessages[$request->get('action')] ?? 'Action completed successfully',
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'HANDLE_SEND_BACK_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Forward application from OPC review to pending approval
     */
    public function forwardToApproval(Request $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $application = $this->applicationService->forwardToApproval($application, $request->user());

            return response()->json([
                'success' => true,
                'data' => $application,
                'message' => 'Application forwarded to approval successfully',
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
                    'code' => 'FORWARD_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }
}
