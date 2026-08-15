<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\VettingRecord;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $query = Application::query();

            // Apply date filters if provided
            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            if ($request->filled('assigned_opc_approver_id')) {
                $query->where('assigned_opc_approver_id', $request->get('assigned_opc_approver_id'));
            }

            // Permission-based scoping:
            // - Users with "view all applications" can see all
            // - Otherwise scope to records they created or are assigned to
            if (!$user->hasPermissionTo('view all applications')) {
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('assigned_police_officer_id', $user->id)
                      ->orWhere('assigned_nis_officer_id', $user->id)
                      ->orWhere('assigned_opc_approver_id', $user->id);
                });
            }

            $total = $query->count();
            $pending = (clone $query)->whereHas('status', function($q) {
                $q->where('code', 'pending');
            })->count();
            $approved = (clone $query)->whereHas('status', function($q) {
                $q->where('code', 'approved');
            })->count();
            $denied = (clone $query)->whereHas('status', function($q) {
                $q->where('code', 'denied');
            })->count();

            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->first();
            $policeVettingStatus = ApplicationStatus::where('code', 'police_vetting')->first();
            $nisVettingStatus = ApplicationStatus::where('code', 'nis_vetting')->first();

            $returnedToAdmin = $opcReviewStatus
                ? (clone $query)->where('status_id', $opcReviewStatus->id)->count()
                : 0;

            $policeVettingTotal = $policeVettingStatus
                ? (clone $query)->where('status_id', $policeVettingStatus->id)->count()
                : 0;
            $policeVettingReturned = $policeVettingStatus
                ? (clone $query)
                    ->where('status_id', $policeVettingStatus->id)
                    ->whereHas('policeVetting.status', function ($q) {
                        $q->where('code', 'sent_back');
                    })
                    ->count()
                : 0;
            $policeVettingActive = max(0, $policeVettingTotal - $policeVettingReturned);

            $nisVettingTotal = $nisVettingStatus
                ? (clone $query)->where('status_id', $nisVettingStatus->id)->count()
                : 0;
            $nisVettingReturned = $nisVettingStatus
                ? (clone $query)
                    ->where('status_id', $nisVettingStatus->id)
                    ->whereHas('nisVetting.status', function ($q) {
                        $q->where('code', 'sent_back');
                    })
                    ->count()
                : 0;
            $nisVettingActive = max(0, $nisVettingTotal - $nisVettingReturned);

            // Get status breakdown with same role-based filtering
            $statusBreakdownQuery = Application::select('application_statuses.name', 'application_statuses.code', DB::raw('count(*) as count'))
                ->join('application_statuses', 'applications.status_id', '=', 'application_statuses.id');
            
            // Apply same permission-based scoping
            if (!$user->hasPermissionTo('view all applications')) {
                $statusBreakdownQuery->where(function ($q) use ($user) {
                    $q->where('applications.created_by', $user->id)
                      ->orWhere('applications.assigned_police_officer_id', $user->id)
                      ->orWhere('applications.assigned_nis_officer_id', $user->id)
                      ->orWhere('applications.assigned_opc_approver_id', $user->id);
                });
            }
            
            // Apply date filters if provided
            if ($dateFrom) {
                $statusBreakdownQuery->whereDate('applications.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $statusBreakdownQuery->whereDate('applications.created_at', '<=', $dateTo);
            }

            if ($request->filled('assigned_opc_approver_id')) {
                $statusBreakdownQuery->where('applications.assigned_opc_approver_id', $request->get('assigned_opc_approver_id'));
            }
            
            $statusBreakdown = $statusBreakdownQuery
                ->groupBy('application_statuses.id', 'application_statuses.name', 'application_statuses.code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total' => $total,
                        'pending' => $pending,
                        'approved' => $approved,
                        'denied' => $denied,
                        'returned_to_admin' => $returnedToAdmin,
                        'returned_to_police' => $policeVettingReturned,
                        'returned_to_nis' => $nisVettingReturned,
                        'police_vetting_total' => $policeVettingTotal,
                        'police_vetting_active' => $policeVettingActive,
                        'nis_vetting_total' => $nisVettingTotal,
                        'nis_vetting_active' => $nisVettingActive,
                    ],
                    // Also provide flat structure for frontend compatibility
                    'total_applications' => $total,
                    'pending_applications' => $pending,
                    'approved_applications' => $approved,
                    'denied_applications' => $denied,
                    'returned_to_admin_applications' => $returnedToAdmin,
                    'returned_to_police_applications' => $policeVettingReturned,
                    'returned_to_nis_applications' => $nisVettingReturned,
                    'police_vetting_active_applications' => $policeVettingActive,
                    'nis_vetting_active_applications' => $nisVettingActive,
                    'workflow_counts' => [
                        'returned_to_admin' => $returnedToAdmin,
                        'returned_to_police' => $policeVettingReturned,
                        'returned_to_nis' => $nisVettingReturned,
                        'police_vetting_active' => $policeVettingActive,
                        'nis_vetting_active' => $nisVettingActive,
                    ],
                    'status_breakdown' => $statusBreakdown,
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve dashboard statistics',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Get application reports
     */
    public function applications(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $filters = [
                'status_id' => $request->get('status_id'),
                'status' => $request->get('status'),
                'created_by' => $request->get('created_by'),
                'assigned_opc_approver_id' => $request->get('assigned_opc_approver_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'institution_id' => $request->get('institution_id'),
            ];

            $perPage = min($request->get('per_page', 50), 200);
            
            $query = Application::with(['status', 'createdBy', 'createdBy.institution']);

            // Permission-based scoping (same logic as dashboard)
            if (!$user->hasPermissionTo('view all applications')) {
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhere('assigned_police_officer_id', $user->id)
                        ->orWhere('assigned_nis_officer_id', $user->id)
                        ->orWhere('assigned_opc_approver_id', $user->id);
                });
            }

            // Apply filters
            if (isset($filters['status_id']) && $filters['status_id']) {
                $query->where('status_id', $filters['status_id']);
            }

            if (isset($filters['status']) && $filters['status']) {
                $status = \App\Models\ApplicationStatus::where('code', $filters['status'])->first();
                if ($status) {
                    $query->where('status_id', $status->id);
                }
            }

            if (isset($filters['created_by']) && $filters['created_by']) {
                $query->where('created_by', $filters['created_by']);
            }

            if (isset($filters['assigned_opc_approver_id']) && $filters['assigned_opc_approver_id']) {
                $query->where('assigned_opc_approver_id', $filters['assigned_opc_approver_id']);
            }

            if (isset($filters['date_from']) && $filters['date_from']) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to']) && $filters['date_to']) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            if (isset($filters['institution_id']) && $filters['institution_id']) {
                $query->whereHas('createdBy', function($q) use ($filters) {
                    $q->where('institution_id', $filters['institution_id']);
                });
            }

            $applications = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $applications->items(),
                'meta' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve application reports', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve application reports',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Get vetting statistics and records
     */
    public function vetting(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $perPage = min($request->get('per_page', 50), 200);

            $query = VettingRecord::with(['vettingType', 'status', 'application', 'conductedBy']);

            // Permission-based scoping
            if (!$user->hasPermissionTo('view all applications')) {
                $query->whereHas('application', function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhere('assigned_police_officer_id', $user->id)
                        ->orWhere('assigned_nis_officer_id', $user->id)
                        ->orWhere('assigned_opc_approver_id', $user->id);
                });
            }

            // Date filters
            if ($dateFrom) {
                $query->whereDate('vetting_records.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('vetting_records.created_at', '<=', $dateTo);
            }

            // Statistics
            $total = $query->count();
            $policeVetting = (clone $query)->whereHas('vettingType', function($q) {
                $q->where('code', 'police');
            })->count();
            $nisVetting = (clone $query)->whereHas('vettingType', function($q) {
                $q->where('code', 'nis');
            })->count();

            $completed = (clone $query)->whereHas('status', function($q) {
                $q->where('code', 'completed');
            })->count();

            // Breakdown by type and status
            $breakdown = VettingRecord::select(
                'vetting_types.name as vetting_type',
                'vetting_statuses.name as status',
                DB::raw('count(*) as count')
            )
                ->join('vetting_types', 'vetting_records.vetting_type_id', '=', 'vetting_types.id')
                ->join('vetting_statuses', 'vetting_records.status_id', '=', 'vetting_statuses.id');
            
            if ($dateFrom) {
                $breakdown->whereDate('vetting_records.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $breakdown->whereDate('vetting_records.created_at', '<=', $dateTo);
            }
            
            $breakdown = $breakdown->groupBy('vetting_types.name', 'vetting_statuses.name')->get();

            // Get paginated records
            $records = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $records->items(),
                'summary' => [
                    'total' => $total,
                    'police_vetting' => $policeVetting,
                    'nis_vetting' => $nisVetting,
                    'completed' => $completed,
                ],
                'breakdown' => $breakdown,
                'meta' => [
                    'current_page' => $records->currentPage(),
                    'last_page' => $records->lastPage(),
                    'per_page' => $records->perPage(),
                    'total' => $records->total(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve vetting statistics', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve vetting statistics',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Get audit logs (Admin only)
     */
    public function audit(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id' => $request->get('user_id'),
                'action' => $request->get('action'),
                'model_type' => $request->get('model_type'),
                'model_id' => $request->get('model_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'order_by' => $request->get('order_by', 'created_at'),
                'order_dir' => $request->get('order_dir', 'desc'),
            ];

            $perPage = min($request->get('per_page', 50), 200);
            $logs = $this->auditService->getAuditLogs($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve audit logs', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve audit logs',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Export reports (CSV/PDF)
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'applications'); // applications, vetting, audit
            $format = $request->get('format', 'csv'); // csv, pdf
            $filters = $request->only(['status', 'date_from', 'date_to', 'institution_id']);

            // For now, return data that can be exported
            // In production, you would generate actual CSV/PDF files
            $data = [];

            switch ($type) {
                case 'applications':
                    $query = Application::with(['status', 'createdBy']);
                    if (isset($filters['status'])) {
                        $status = \App\Models\ApplicationStatus::where('code', $filters['status'])->first();
                        if ($status) {
                            $query->where('status_id', $status->id);
                        }
                    }
                    if (isset($filters['date_from'])) {
                        $query->whereDate('created_at', '>=', $filters['date_from']);
                    }
                    if (isset($filters['date_to'])) {
                        $query->whereDate('created_at', '<=', $filters['date_to']);
                    }
                    $data = $query->get();
                    break;

                case 'vetting':
                    $query = VettingRecord::with(['vettingType', 'status', 'application']);
                    if (isset($filters['date_from'])) {
                        $query->whereDate('created_at', '>=', $filters['date_from']);
                    }
                    if (isset($filters['date_to'])) {
                        $query->whereDate('created_at', '<=', $filters['date_to']);
                    }
                    $data = $query->get();
                    break;

                case 'audit':
                    $auditFilters = [
                        'date_from' => $filters['date_from'] ?? null,
                        'date_to' => $filters['date_to'] ?? null,
                    ];
                    $logs = $this->auditService->getAuditLogs($auditFilters, 10000);
                    $data = $logs->items();
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => "Export data ready for {$format} format",
                'meta' => [
                    'type' => $type,
                    'format' => $format,
                    'count' => count($data),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to export data', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'EXPORT_FAILED',
                    'message' => 'Failed to export data',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }
}
