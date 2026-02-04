<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplicationService
{
    protected AuditService $auditService;
    protected NotificationService $notificationService;

    public function __construct(AuditService $auditService, NotificationService $notificationService)
    {
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
    }
    /**
     * Get applications with filters, search, and pagination
     */
    public function getApplications(array $filters = [], int $perPage = 20, ?User $user = null)
    {
        $query = Application::with([
            'status',
            'createdBy',
            'assignedPoliceOfficer',
            'assignedNisOfficer',
            'assignedOpcApprover',
            'documents',
            'vettingRecords',
        ]);

        // Permission-based scoping if user is provided
        if ($user && !$user->hasPermissionTo('view all applications')) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('assigned_police_officer_id', $user->id)
                    ->orWhere('assigned_nis_officer_id', $user->id)
                    ->orWhere('assigned_opc_approver_id', $user->id);
            });
        }

        // Search by application number, full name, or national ID
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('current_name', 'like', "%{$search}%")
                  ->orWhere('requested_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if (isset($filters['status_id']) && $filters['status_id']) {
            $query->where('status_id', $filters['status_id']);
        }

        // Filter by status code
        if (isset($filters['status']) && $filters['status']) {
            $status = ApplicationStatus::where('code', $filters['status'])->first();
            if ($status) {
                $query->where('status_id', $status->id);
            }
        }

        // Filter by created_by
        if (isset($filters['created_by']) && $filters['created_by']) {
            $query->where('created_by', $filters['created_by']);
        }

        // Filter by assigned officer
        if (isset($filters['assigned_police_officer_id']) && $filters['assigned_police_officer_id']) {
            $query->where('assigned_police_officer_id', $filters['assigned_police_officer_id']);
        }

        if (isset($filters['assigned_nis_officer_id']) && $filters['assigned_nis_officer_id']) {
            $query->where('assigned_nis_officer_id', $filters['assigned_nis_officer_id']);
        }

        // Date range filters
        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Create a new application
     */
    public function createApplication(array $data, User $user): Application
    {
        DB::beginTransaction();
        try {
            // Get pending status
            $pendingStatus = ApplicationStatus::where('code', 'pending')->firstOrFail();

            $application = Application::create([
                'full_name' => $data['full_name'],
                'national_id' => $data['national_id'] ?? null,
                'current_name' => $data['current_name'] ?? null,
                'requested_name' => $data['requested_name'],
                'reason' => $data['reason'],
                'status_id' => $pendingStatus->id,
                'created_by' => $user->id,
                'submitted_at' => now(),
            ]);

            // Log audit
            $this->auditService->logCreate($user, Application::class, $application->id, $application->toArray());

            DB::commit();
            return $application->load(['status', 'createdBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create application: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an application
     */
    public function updateApplication(Application $application, array $data, User $user): Application
    {
        DB::beginTransaction();
        try {
            $oldValues = $application->toArray();

            $application->update([
                'full_name' => $data['full_name'] ?? $application->full_name,
                'national_id' => $data['national_id'] ?? $application->national_id,
                'current_name' => $data['current_name'] ?? $application->current_name,
                'requested_name' => $data['requested_name'] ?? $application->requested_name,
                'reason' => $data['reason'] ?? $application->reason,
            ]);

            // Log audit
            $this->auditService->logUpdate($user, Application::class, $application->id, $oldValues, $application->toArray());

            DB::commit();
            return $application->fresh(['status', 'createdBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update application: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign application to police officer
     */
    public function assignToPolice(Application $application, int $policeOfficerId, User $user): Application
    {
        DB::beginTransaction();
        try {
            $policeOfficer = User::findOrFail($policeOfficerId);

            // Verify target user can conduct police vetting
            if (!$policeOfficer->hasPermissionTo('conduct police vetting')) {
                throw new \Exception('User must have permission to conduct police vetting');
            }

            // Get police_vetting status
            $policeVettingStatus = ApplicationStatus::where('code', 'police_vetting')->firstOrFail();

            $oldValues = $application->toArray();

            $application->update([
                'assigned_police_officer_id' => $policeOfficerId,
                'status_id' => $policeVettingStatus->id,
            ]);

            // Log audit
            $this->auditService->logUpdate($user, Application::class, $application->id, $oldValues, $application->toArray());

            // Send notification to police officer
            $this->notificationService->notifyApplicationAssigned($application, $policeOfficer, 'police');

            DB::commit();
            return $application->fresh(['status', 'assignedPoliceOfficer']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign application to police: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign application to NIS officer
     */
    public function assignToNis(Application $application, int $nisOfficerId, User $user): Application
    {
        DB::beginTransaction();
        try {
            $nisOfficer = User::findOrFail($nisOfficerId);

            // Verify target user can conduct NIS vetting
            if (!$nisOfficer->hasPermissionTo('conduct nis vetting')) {
                throw new \Exception('User must have permission to conduct NIS vetting');
            }

            // Check if police vetting is completed
            // After police vetting is completed, status transitions to opc_review
            // So we check if police vetting record exists and is completed
            // Load the application with necessary relationships
            $application->load(['policeVetting.status', 'status']);

            $policeVetting = $application->policeVetting;
            if (!$policeVetting || $policeVetting->status->code !== 'completed') {
                throw new \Exception('Police vetting must be completed before assigning to NIS');
            }

            // Also check that application status allows NIS assignment
            // Status should be opc_review or police_completed
            $allowedStatuses = ['opc_review', 'police_completed'];
            if (!in_array($application->status->code, $allowedStatuses)) {
                throw new \Exception('Application must be in OPC review or police completed status to assign to NIS');
            }

            // Get nis_vetting status
            $nisVettingStatus = ApplicationStatus::where('code', 'nis_vetting')->firstOrFail();

            $oldValues = $application->toArray();

            $application->update([
                'assigned_nis_officer_id' => $nisOfficerId,
                'status_id' => $nisVettingStatus->id,
            ]);

            // Log audit
            $this->auditService->logUpdate($user, Application::class, $application->id, $oldValues, $application->toArray());

            // Send notification to NIS officer
            $this->notificationService->notifyApplicationAssigned($application, $nisOfficer, 'NIS');

            DB::commit();
            return $application->fresh(['status', 'assignedNisOfficer']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign application to NIS: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get application status history
     */
    public function getStatusHistory(Application $application)
    {
        // Get audit logs related to status changes
        return AuditLog::where('model_type', Application::class)
            ->where('model_id', $application->id)
            ->whereIn('action', [
                'application_created',
                'application_status_changed',
                'application_assigned_police',
                'application_assigned_nis',
                'application_approved',
                'application_denied',
            ])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Forward application from OPC review to pending approval
     * Only allowed when both police and NIS vetting are completed
     */
    public function forwardToApproval(Application $application, User $user): Application
    {
        DB::beginTransaction();
        try {
            // Verify application is in OPC review
            if ($application->status->code !== 'opc_review') {
                throw new \Exception('Application must be in OPC review status to forward to approval');
            }

            // Verify both vetting are completed
            $policeVetting = $application->policeVetting;
            $nisVetting = $application->nisVetting;

            if (!$policeVetting || $policeVetting->status->code !== 'completed') {
                throw new \Exception('Police vetting must be completed before forwarding to approval');
            }

            if (!$nisVetting || $nisVetting->status->code !== 'completed') {
                throw new \Exception('NIS vetting must be completed before forwarding to approval');
            }

            // Get pending approval status
            $pendingApprovalStatus = ApplicationStatus::where('code', 'pending_approval')->firstOrFail();

            $oldValues = $application->toArray();

            // Update application status
            $application->update([
                'status_id' => $pendingApprovalStatus->id,
            ]);

            // Log audit
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldValues['status_id'] ?? null, $application->status_id);

            // Send notification to OPC approver
            $this->notificationService->notifyApprovalRequired($application);

            DB::commit();
            return $application->fresh(['status', 'policeVetting', 'nisVetting']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to forward application to approval: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send back application from OPC approver to admin for review
     */
    public function sendBackToAdmin(Application $application, string $reason, User $user): Application
    {
        DB::beginTransaction();
        try {
            // Verify application is in pending_approval status
            if ($application->status->code !== 'pending_approval') {
                throw new \Exception('Application must be in pending approval status to send back to admin');
            }

            // Get opc_review status (admin will review)
            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();

            $oldValues = $application->toArray();

            // Update application status and store send-back reason
            $application->update([
                'status_id' => $opcReviewStatus->id,
                'approver_send_back_reason' => $reason,
                'approver_send_back_at' => now(),
            ]);

            // Log audit
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldValues['status_id'] ?? null, $application->status_id);

            // Send notification to admin
            $this->notificationService->notifyApplicationSentBackToAdmin($application, $user, $reason);

            DB::commit();
            return $application->fresh(['status']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send back application to admin: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Admin handles approver send-back: can send to police, send to NIS, or allow editing
     */
    public function handleApproverSendBack(Application $application, string $action, ?string $reason = null, User $user): Application
    {
        DB::beginTransaction();
        try {
            // Verify application was sent back by approver
            if (!$application->approver_send_back_reason) {
                throw new \Exception('Application was not sent back by approver');
            }

            // Verify application is in opc_review status
            if ($application->status->code !== 'opc_review') {
                throw new \Exception('Application must be in OPC review status');
            }

            $oldValues = $application->toArray();

            switch ($action) {
                case 'send_to_police':
                    // Send back to police vetting
                    $policeVettingStatus = ApplicationStatus::where('code', 'police_vetting')->firstOrFail();
                    $application->update([
                        'status_id' => $policeVettingStatus->id,
                        'approver_send_back_reason' => null, // Clear the reason
                        'approver_send_back_at' => null,
                    ]);
                    // Send notification to assigned police officer
                    if ($application->assigned_police_officer) {
                        $this->notificationService->notifyApplicationSentBackToOfficer($application, $application->assigned_police_officer, 'police', $reason);
                    }
                    break;

                case 'send_to_nis':
                    // Send back to NIS vetting
                    $nisVettingStatus = ApplicationStatus::where('code', 'nis_vetting')->firstOrFail();
                    $application->update([
                        'status_id' => $nisVettingStatus->id,
                        'approver_send_back_reason' => null, // Clear the reason
                        'approver_send_back_at' => null,
                    ]);
                    // Send notification to assigned NIS officer
                    if ($application->assigned_nis_officer) {
                        $this->notificationService->notifyApplicationSentBackToOfficer($application, $application->assigned_nis_officer, 'nis', $reason);
                    }
                    break;

                case 'allow_editing':
                    // Keep in opc_review but clear send-back reason to allow admin editing
                    $application->update([
                        'approver_send_back_reason' => null,
                        'approver_send_back_at' => null,
                    ]);
                    // Application stays in opc_review, admin can now edit
                    break;

                default:
                    throw new \Exception('Invalid action. Must be: send_to_police, send_to_nis, or allow_editing');
            }

            // Log audit
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldValues['status_id'] ?? null, $application->status_id);

            DB::commit();
            return $application->fresh(['status']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to handle approver send-back: ' . $e->getMessage());
            throw $e;
        }
    }
}

