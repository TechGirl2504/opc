<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVettingRequest;
use App\Http\Requests\UpdateVettingRequest;
use App\Models\Application;
use App\Services\VettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VettingController extends Controller
{
    protected VettingService $vettingService;

    public function __construct(VettingService $vettingService)
    {
        $this->vettingService = $vettingService;
    }

    /**
     * Get police vetting record for application
     */
    public function getPoliceVetting(string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $vetting = $this->vettingService->getPoliceVetting($application);

            if (!$vetting) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Police vetting record not found',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $vetting,
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
                    'message' => 'Failed to retrieve police vetting',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Submit police vetting
     */
    public function submitPoliceVetting(StoreVettingRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $file = $request->hasFile('document') ? $request->file('document') : null;

            $vetting = $this->vettingService->submitPoliceVetting(
                $application,
                $request->validated(),
                $request->user(),
                $file
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'Police vetting submitted successfully',
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
                    'code' => 'SUBMISSION_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Update police vetting
     */
    public function updatePoliceVetting(UpdateVettingRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $vetting = $this->vettingService->getPoliceVetting($application);

            if (!$vetting) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Police vetting record not found',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 404);
            }

            $file = $request->hasFile('document') ? $request->file('document') : null;
            $vetting = $this->vettingService->updateVetting(
                $vetting,
                $request->validated(),
                $request->user(),
                $file
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'Police vetting updated successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Application or vetting record not found',
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
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Get NIS vetting record for application
     */
    public function getNisVetting(string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $vetting = $this->vettingService->getNisVetting($application);

            if (!$vetting) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'NIS vetting record not found',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $vetting,
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
                    'message' => 'Failed to retrieve NIS vetting',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Submit NIS vetting
     */
    public function submitNisVetting(StoreVettingRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $file = $request->hasFile('document') ? $request->file('document') : null;

            $vetting = $this->vettingService->submitNisVetting(
                $application,
                $request->validated(),
                $request->user(),
                $file
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'NIS vetting submitted successfully',
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
                    'code' => 'SUBMISSION_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Update NIS vetting
     */
    public function updateNisVetting(UpdateVettingRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $vetting = $this->vettingService->getNisVetting($application);

            if (!$vetting) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'NIS vetting record not found',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 404);
            }

            $file = $request->hasFile('document') ? $request->file('document') : null;
            $vetting = $this->vettingService->updateVetting(
                $vetting,
                $request->validated(),
                $request->user(),
                $file
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'NIS vetting updated successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Application or vetting record not found',
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
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Save police vetting as draft
     */
    public function savePoliceVettingDraft(StoreVettingRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $file = $request->hasFile('document') ? $request->file('document') : null;

            $vetting = $this->vettingService->savePoliceVettingDraft(
                $application,
                $request->validated(),
                $request->user(),
                $file
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'Police vetting saved as draft successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
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
                    'code' => 'DRAFT_SAVE_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Complete police vetting
     */
    public function completePoliceVetting(StoreVettingRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $file = $request->hasFile('document') ? $request->file('document') : null;

            $vetting = $this->vettingService->completePoliceVetting(
                $application,
                $request->validated(),
                $request->user(),
                $file
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'Police vetting completed successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
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
                    'code' => 'COMPLETION_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Save NIS vetting as draft
     */
    public function saveNisVettingDraft(StoreVettingRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $file = $request->hasFile('document') ? $request->file('document') : null;

            $vetting = $this->vettingService->saveNisVettingDraft(
                $application,
                $request->validated(),
                $request->user(),
                $file
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'NIS vetting saved as draft successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
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
                    'code' => 'DRAFT_SAVE_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Complete NIS vetting
     */
    public function completeNisVetting(StoreVettingRequest $request, string $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $file = $request->hasFile('document') ? $request->file('document') : null;

            $vetting = $this->vettingService->completeNisVetting(
                $application,
                $request->validated(),
                $request->user(),
                $file
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'NIS vetting completed successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
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
                    'code' => 'COMPLETION_FAILED',
                    'message' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }
    }

    /**
     * Send back vetting for clarifications (OPC only)
     */
    public function sendBack(\App\Http\Requests\SendBackVettingRequest $request, string $id): JsonResponse
    {
        try {
            $vettingRecord = \App\Models\VettingRecord::findOrFail($id);
            $vetting = $this->vettingService->sendBackVetting(
                $vettingRecord,
                $request->validated()['reason'],
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $vetting,
                'message' => 'Vetting sent back for clarifications successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Vetting record not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
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
}
