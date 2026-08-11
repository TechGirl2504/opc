<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveApplicationRequest;
use App\Http\Requests\DenyApplicationRequest;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Services\DecisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DecisionController extends Controller
{
    protected DecisionService $decisionService;
    protected ApplicationService $applicationService;

    public function __construct(DecisionService $decisionService, ApplicationService $applicationService)
    {
        $this->decisionService = $decisionService;
        $this->applicationService = $applicationService;
    }

    private function forbiddenApplicationResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'You do not have permission to access this application',
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 403);
    }

    /**
     * Approve an application
     */
    public function approve(ApproveApplicationRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            if (!$this->applicationService->canAccessApplication($application, request()->user())) {
                return $this->forbiddenApplicationResponse();
            }
            $decision = $this->decisionService->approveApplication(
                $application,
                $request->user(),
                $request->conditions
            );

            return response()->json([
                'success' => true,
                'data' => $decision,
                'message' => 'Application approved successfully',
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
            Log::error('Failed to approve application', [
                'application_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'APPROVAL_FAILED',
                    'message' => 'Failed to approve application',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Deny an application
     */
    public function deny(DenyApplicationRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            if (!$this->applicationService->canAccessApplication($application, request()->user())) {
                return $this->forbiddenApplicationResponse();
            }
            $decision = $this->decisionService->denyApplication(
                $application,
                $request->user(),
                $request->denial_reason
            );

            return response()->json([
                'success' => true,
                'data' => $decision,
                'message' => 'Application denied successfully',
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
            Log::error('Failed to deny application', [
                'application_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DENIAL_FAILED',
                    'message' => 'Failed to deny application',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Get decision history for an application
     */
    public function index(string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            if (!$this->applicationService->canAccessApplication($application, request()->user())) {
                return $this->forbiddenApplicationResponse();
            }
            $decisions = $this->decisionService->getDecisionHistory($application);

            return response()->json([
                'success' => true,
                'data' => $decisions,
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
                    'message' => 'Failed to retrieve decision history',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }
}
