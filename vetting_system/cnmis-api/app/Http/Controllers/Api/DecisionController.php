<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveApplicationRequest;
use App\Http\Requests\DenyApplicationRequest;
use App\Models\Application;
use App\Services\DecisionService;
use Illuminate\Http\JsonResponse;

class DecisionController extends Controller
{
    protected DecisionService $decisionService;

    public function __construct(DecisionService $decisionService)
    {
        $this->decisionService = $decisionService;
    }

    /**
     * Approve an application
     */
    public function approve(ApproveApplicationRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
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
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'APPROVAL_FAILED',
                    'message' => $e->getMessage(),
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
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DENIAL_FAILED',
                    'message' => $e->getMessage(),
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
