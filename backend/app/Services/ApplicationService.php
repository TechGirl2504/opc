<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\NameChangeReason;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplicationService
{
    protected AuditService $auditService;
    protected NotificationService $notificationService;

    private const FINAL_STATUSES = ['approved', 'denied', 'archived'];
    private const CREATOR_EDITABLE_STATUSES = ['draft', 'returned_to_data_entry'];
    private const ADMIN_EDITABLE_STATUSES = ['handoff_to_admin'];

    public function __construct(AuditService $auditService, NotificationService $notificationService)
    {
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
    }

    /**
     * Check whether a user can access a specific application.
     */
    public function canAccessApplication(Application $application, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasActivePermission('view all applications') || $this->isApproverUser($user)) {
            return true;
        }

        $isCreator = ($application->created_by !== null) && ((int) $application->created_by === (int) $user->id);
        $isAssignedPolice = ($application->assigned_police_officer_id !== null) && ((int) $application->assigned_police_officer_id === (int) $user->id);
        $isAssignedNis = ($application->assigned_nis_officer_id !== null) && ((int) $application->assigned_nis_officer_id === (int) $user->id);
        $isAssignedApprover = ($application->assigned_opc_approver_id !== null) && ((int) $application->assigned_opc_approver_id === (int) $user->id);

        return $isCreator || $isAssignedPolice || $isAssignedNis || $isAssignedApprover;
    }

    private function isApproverUser(User $user): bool
    {
        return $user->hasActiveRole('opc_approver');
    }

    /**
     * Resolve the workflow actions that should be visible for the current user.
     */
    public function getAllowedActions(Application $application, ?User $user): array
    {
        if (!$user || !$application->status) {
            return [];
        }

        $statusCode = $application->status->code;
        $isCreator = ($application->created_by !== null) && ((int) $application->created_by === (int) $user->id);
        $isAssignedPolice = ($application->assigned_police_officer_id !== null) && ((int) $application->assigned_police_officer_id === (int) $user->id);
        $isAssignedNis = ($application->assigned_nis_officer_id !== null) && ((int) $application->assigned_nis_officer_id === (int) $user->id);
        $isAssignedApprover = ($application->assigned_opc_approver_id !== null) && ((int) $application->assigned_opc_approver_id === (int) $user->id);
        $isAdmin = $user->hasActiveRole('admin');

        $allowedActions = [];

        if ($this->canAccessApplication($application, $user)) {
            $allowedActions[] = 'view_application';
        }

        if ($this->canEditApplicationWorkflow($application, $user, $isCreator, $isAdmin, $statusCode)) {
            $allowedActions[] = 'edit_application';
        }

        if ($this->canHandleApproverSendBackWorkflow($application, $user, $isAdmin, $statusCode)) {
            $allowedActions[] = 'handle_approver_send_back';
        }

        if ($this->canDeleteApplicationWorkflow($application, $user, $isCreator, $isAdmin, $statusCode)) {
            $allowedActions[] = 'delete_application';
        }

        if ($user->hasActivePermission('upload documents') && !in_array($statusCode, self::FINAL_STATUSES, true)) {
            $allowedActions[] = 'upload_documents';
        }

        if ($user->hasActivePermission('view documents')) {
            $allowedActions[] = 'view_documents';
        }

        if ($user->hasActivePermission('download documents')) {
            $allowedActions[] = 'download_documents';
        }

        if ($user->hasActivePermission('delete documents') && !in_array($statusCode, self::FINAL_STATUSES, true)) {
            $allowedActions[] = 'delete_documents';
        }

        if ($user->hasActivePermission('assign applications') && $this->canAssignPoliceWorkflow($application, $statusCode)) {
            $allowedActions[] = 'assign_police_officer';
        }

        if ($user->hasActivePermission('assign applications') && $this->canAssignNisWorkflow($application, $statusCode)) {
            $allowedActions[] = 'assign_nis_officer';
        }

        if ($user->hasActivePermission('conduct police vetting') && $isAssignedPolice && $this->canConductPoliceVettingWorkflow($application)) {
            $allowedActions[] = 'conduct_police_vetting';
        }

        if ($user->hasActivePermission('conduct nis vetting') && $isAssignedNis && $this->canConductNisVettingWorkflow($application)) {
            $allowedActions[] = 'conduct_nis_vetting';
        }

        if ($isAdmin && $user->hasActivePermission('send back vetting') && $this->canSendBackPoliceWorkflow($application)) {
            $allowedActions[] = 'send_back_police_vetting';
        }

        if ($isAdmin && $user->hasActivePermission('send back vetting') && $this->canSendBackNisWorkflow($application)) {
            $allowedActions[] = 'send_back_nis_vetting';
        }

        if ($isAdmin && $user->hasActivePermission('approve applications') && $this->canForwardToApprovalWorkflow($application)) {
            $allowedActions[] = 'forward_to_approval';
        }

        if (
            $user->hasActivePermission('create applications')
            && $isCreator
            && in_array($statusCode, ['draft', 'returned_to_data_entry'], true)
        ) {
            $allowedActions[] = 'forward_to_admin';
        }

        if ($this->isApproverUser($user) && $statusCode === 'pending_approval') {
            $allowedActions[] = 'approve_application';
            $allowedActions[] = 'send_back_to_admin';
        }

        if ($this->isApproverUser($user) && $statusCode === 'pending_approval') {
            $allowedActions[] = 'deny_application';
        }

        if (
            ($user->hasActiveRole('admin') || $user->hasActivePermission('manage application statuses'))
            && $statusCode === 'handoff_to_admin'
            && !$application->assigned_police_officer_id
            && !$application->assigned_nis_officer_id
            && !$application->assigned_opc_approver_id
        ) {
            $allowedActions[] = 'send_back_to_data_entry';
        }

        return array_values(array_unique($allowedActions));
    }

    public function canEditApplication(Application $application, User $user): bool
    {
        return in_array('edit_application', $this->getAllowedActions($application, $user), true);
    }

    public function canDeleteApplication(Application $application, User $user): bool
    {
        return in_array('delete_application', $this->getAllowedActions($application, $user), true);
    }

    private function canEditApplicationWorkflow(Application $application, User $user, bool $isCreator, bool $isAdmin, string $statusCode): bool
    {
        $isAdminReviewAfterApproverReturn = $isAdmin
            && $statusCode === 'opc_review'
            && !empty($application->approver_send_back_reason);

        if (
            !$isAdminReviewAfterApproverReturn
            && ($application->assigned_police_officer_id || $application->assigned_nis_officer_id || $application->assigned_opc_approver_id)
        ) {
            return false;
        }

        if (in_array($statusCode, self::CREATOR_EDITABLE_STATUSES, true)) {
            return $isCreator && $user->hasActivePermission('create applications');
        }

        if ($isAdminReviewAfterApproverReturn) {
            return true;
        }

        if (in_array($statusCode, self::ADMIN_EDITABLE_STATUSES, true)) {
            return $isAdmin;
        }

        return false;
    }

    private function canHandleApproverSendBackWorkflow(Application $application, ?User $user, bool $isAdmin, string $statusCode): bool
    {
        return $isAdmin
            && $user !== null
            && $statusCode === 'opc_review'
            && !empty($application->approver_send_back_reason);
    }

    private function canDeleteApplicationWorkflow(Application $application, User $user, bool $isCreator, bool $isAdmin, string $statusCode): bool
    {
        if ($application->assigned_police_officer_id || $application->assigned_nis_officer_id || $application->assigned_opc_approver_id) {
            return false;
        }

        if (!in_array($statusCode, ['draft', 'handoff_to_admin'], true)) {
            return false;
        }

        return $user->hasActivePermission('delete applications') && ($isCreator || $isAdmin);
    }

    private function canAssignPoliceWorkflow(Application $application, string $statusCode): bool
    {
        if (!in_array($statusCode, ['draft', 'handoff_to_admin', 'police_vetting'], true)) {
            if ($statusCode !== 'opc_review') {
                return false;
            }

            $policeVetting = $application->policeVetting;
            if ($policeVetting && $policeVetting->status?->code === 'completed') {
                return false;
            }
        }

        return true;
    }

    private function canAssignNisWorkflow(Application $application, string $statusCode): bool
    {
        if (in_array($statusCode, ['police_completed', 'nis_vetting'], true)) {
            return true;
        }

        if ($statusCode !== 'opc_review') {
            return false;
        }

        $policeVetting = $application->policeVetting;
        $nisVetting = $application->nisVetting;

        return $policeVetting && $policeVetting->status?->code === 'completed'
            && (!$nisVetting || $nisVetting->status?->code !== 'completed');
    }

    private function canConductPoliceVettingWorkflow(Application $application): bool
    {
        $statusCode = $application->status->code ?? null;
        if (in_array($statusCode, ['draft', 'handoff_to_admin', 'police_vetting'], true)) {
            return true;
        }

        return $application->policeVetting?->status?->code === 'sent_back';
    }

    private function canConductNisVettingWorkflow(Application $application): bool
    {
        $statusCode = $application->status->code ?? null;
        if ($statusCode === 'nis_vetting') {
            return true;
        }

        return $application->nisVetting?->status?->code === 'sent_back';
    }

    private function canSendBackPoliceWorkflow(Application $application): bool
    {
        return ($application->status->code ?? null) === 'opc_review'
            && $application->policeVetting?->status?->code === 'completed';
    }

    private function canSendBackNisWorkflow(Application $application): bool
    {
        return ($application->status->code ?? null) === 'opc_review'
            && $application->nisVetting?->status?->code === 'completed';
    }

    private function canForwardToApprovalWorkflow(Application $application): bool
    {
        return ($application->status->code ?? null) === 'opc_review'
            && $application->policeVetting?->status?->code === 'completed'
            && $application->nisVetting?->status?->code === 'completed';
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
            'nameChangeReason',
            'documents',
            'vettingRecords',
        ]);

        // Permission-based scoping if user is provided
        if ($user && !$user->hasActivePermission('view all applications') && !$this->isApproverUser($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('assigned_police_officer_id', $user->id)
                    ->orWhere('assigned_nis_officer_id', $user->id)
                    ->orWhere('assigned_opc_approver_id', $user->id);
            });
        }

        // Search across identity, location, reason, and status fields.
        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $terms = preg_split('/\s+/', trim((string) $filters['search'])) ?: [];

            $query->where(function ($outerQuery) use ($terms) {
                foreach ($terms as $term) {
                    $outerQuery->where(function ($q) use ($term) {
                        $like = "%{$term}%";

                        $q->where('application_number', 'like', $like)
                          ->orWhere('full_name', 'like', $like)
                          ->orWhere('national_id', 'like', $like)
                          ->orWhere('date_of_birth', 'like', $like)
                          ->orWhere('phone_number', 'like', $like)
                          ->orWhere('district', 'like', $like)
                          ->orWhere('traditional_authority', 'like', $like)
                          ->orWhere('village', 'like', $like)
                          ->orWhere('requested_name', 'like', $like)
                          ->orWhere('reason', 'like', $like)
                          ->orWhereHas('nameChangeReason', function ($reasonQuery) use ($like) {
                              $reasonQuery->where('name', 'like', $like)
                                  ->orWhere('code', 'like', $like);
                          })
                          ->orWhereHas('status', function ($statusQuery) use ($like) {
                              $statusQuery->where('name', 'like', $like)
                                  ->orWhere('code', 'like', $like);
                          });
                    });
                }
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

        if (!empty($filters['review_state'])) {
            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->first();
            if ($opcReviewStatus) {
                $query->where('status_id', $opcReviewStatus->id);

                if ($filters['review_state'] === 'returned') {
                    $query->whereNotNull('approver_send_back_reason');
                } elseif ($filters['review_state'] === 'open') {
                    $query->whereNull('approver_send_back_reason');
                }
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

        if (isset($filters['assigned_opc_approver_id']) && $filters['assigned_opc_approver_id']) {
            $query->where('assigned_opc_approver_id', $filters['assigned_opc_approver_id']);
        }

        if (!empty($filters['vetting_type']) && !empty($filters['vetting_state'])) {
            $vettingRelation = $filters['vetting_type'] === 'nis' ? 'nisVetting' : 'policeVetting';
            $sentBackCondition = function ($q) {
                $q->where('code', 'sent_back');
            };
            $completedCondition = function ($q) {
                $q->where('code', 'completed');
            };

            if ($filters['vetting_state'] === 'returned') {
                $query->whereHas("{$vettingRelation}.status", $sentBackCondition);
            } elseif ($filters['vetting_state'] === 'active') {
                $query->whereDoesntHave("{$vettingRelation}.status", $sentBackCondition);
            } elseif ($filters['vetting_state'] === 'completed') {
                $query->whereHas("{$vettingRelation}.status", $completedCondition);
            }
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
            // New applications start as drafts until they are explicitly handed off to admin.
            $draftStatus = ApplicationStatus::where('code', 'draft')->firstOrFail();
            $reason = $this->resolveNameChangeReason($data);

            $application = Application::create([
                'full_name' => $data['full_name'],
                'national_id' => $data['national_id'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'district' => $data['district'],
                'traditional_authority' => $data['traditional_authority'],
                'village' => $data['village'],
                'requested_name' => $data['requested_name'],
                'name_change_reason_id' => $reason?->id,
                'reason' => $reason?->name ?? ($data['reason'] ?? null),
                'status_id' => $draftStatus->id,
                'created_by' => $user->id,
                'submitted_at' => null,
            ]);

            // Log audit
            $this->auditService->logCreate($user, Application::class, $application->id, $application->toArray());

            DB::commit();
            return $application->load(['status', 'createdBy', 'nameChangeReason']);
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
            $reason = $this->resolveNameChangeReason($data);

            $application->update([
                'full_name' => $data['full_name'] ?? $application->full_name,
                'national_id' => $data['national_id'] ?? $application->national_id,
                'date_of_birth' => $data['date_of_birth'] ?? $application->date_of_birth,
                'email' => $data['email'] ?? $application->email,
                'phone_number' => $data['phone_number'] ?? $application->phone_number,
                'district' => $data['district'] ?? $application->district,
                'traditional_authority' => $data['traditional_authority'] ?? $application->traditional_authority,
                'village' => $data['village'] ?? $application->village,
                'requested_name' => $data['requested_name'] ?? $application->requested_name,
                'name_change_reason_id' => $reason?->id ?? $application->name_change_reason_id,
                'reason' => $reason?->name ?? ($data['reason'] ?? $application->reason),
            ]);

            // Log audit
            $this->auditService->logUpdate($user, Application::class, $application->id, $oldValues, $application->toArray());

            DB::commit();
            return $application->fresh(['status', 'createdBy', 'nameChangeReason']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update application: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Forward a draft or returned application to admin review.
     */
    public function forwardToAdmin(Application $application, User $user): Application
    {
        DB::beginTransaction();
        try {
            $application->loadMissing('status');

            $handoffStatus = ApplicationStatus::where('code', 'handoff_to_admin')->firstOrFail();
            $allowedCurrentStatuses = ['draft', 'returned_to_data_entry', 'handoff_to_admin'];

            if (!in_array($application->status?->code, $allowedCurrentStatuses, true)) {
                throw new \Exception('Application cannot be handed off to admin from the current status');
            }

            if ($application->created_by !== null && (int) $application->created_by !== (int) $user->id && !$user->hasActiveRole('admin')) {
                throw new \Exception('Only the creator or an admin can hand off this application');
            }

            $oldValues = $application->toArray();

            $application->update([
                'status_id' => $handoffStatus->id,
                'data_entry_return_reason' => null,
                'data_entry_return_at' => null,
                'submitted_at' => now(),
            ]);

            $this->auditService->logStatusChange(
                $user,
                Application::class,
                $application->id,
                $oldValues['status_id'] ?? null,
                $application->status_id
            );

            $this->notificationService->notifyApplicationReceivedByAdmins($application, $user);

            DB::commit();
            return $application->fresh(['status', 'createdBy', 'nameChangeReason']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to forward application to admin: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Resolve a submitted reason into a managed name-change reason.
     */
    protected function resolveNameChangeReason(array $data): ?NameChangeReason
    {
        if (!empty($data['reason_id'])) {
            return NameChangeReason::find($data['reason_id']);
        }

        if (!empty($data['reason'])) {
            return NameChangeReason::where('name', $data['reason'])->first()
                ?? NameChangeReason::where('code', $data['reason'])->first();
        }

        return null;
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
            if (!$this->isApproverUser($user)) {
                throw new \Exception('Only the OPC approver can send applications back to admin');
            }

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
    public function handleApproverSendBack(Application $application, string $action, User $user, ?string $reason = null): Application
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
                    if (!$application->assignedPoliceOfficer) {
                        throw new \Exception('No assigned police officer is available for this application');
                    }
                    // Send back to police vetting
                    $policeVettingStatus = ApplicationStatus::where('code', 'police_vetting')->firstOrFail();
                    $application->update([
                        'status_id' => $policeVettingStatus->id,
                        'approver_send_back_reason' => null, // Clear the reason
                        'approver_send_back_at' => null,
                    ]);
                    // Send notification to assigned police officer
                    if ($application->assignedPoliceOfficer) {
                        $this->notificationService->notifyApplicationSentBackToOfficer($application, $application->assignedPoliceOfficer, 'police', $reason);
                    }
                    break;

                case 'send_to_nis':
                    if (!$application->assignedNisOfficer) {
                        throw new \Exception('No assigned NIS officer is available for this application');
                    }
                    // Send back to NIS vetting
                    $nisVettingStatus = ApplicationStatus::where('code', 'nis_vetting')->firstOrFail();
                    $application->update([
                        'status_id' => $nisVettingStatus->id,
                        'approver_send_back_reason' => null, // Clear the reason
                        'approver_send_back_at' => null,
                    ]);
                    // Send notification to assigned NIS officer
                    if ($application->assignedNisOfficer) {
                        $this->notificationService->notifyApplicationSentBackToOfficer($application, $application->assignedNisOfficer, 'nis', $reason);
                    }
                    break;

                default:
                    throw new \Exception('Invalid action. Must be: send_to_police or send_to_nis');
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

    /**
     * Send application back to data entry from admin review.
     */
    public function sendBackToDataEntry(Application $application, string $reason, User $user): Application
    {
        DB::beginTransaction();
        try {
            if ($application->status->code !== 'handoff_to_admin') {
                throw new \Exception('Application must be in handoff to admin status to send back to data entry');
            }

            if ($application->assigned_police_officer_id || $application->assigned_nis_officer_id || $application->assigned_opc_approver_id) {
                throw new \Exception('Assigned applications cannot be sent back to data entry');
            }

            $returnedStatus = ApplicationStatus::where('code', 'returned_to_data_entry')->firstOrFail();
            $oldValues = $application->toArray();

            $application->update([
                'status_id' => $returnedStatus->id,
                'data_entry_return_reason' => $reason,
                'data_entry_return_at' => now(),
            ]);

            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldValues['status_id'] ?? null, $application->status_id);
            $this->notificationService->notifyApplicationSentBackToDataEntry($application, $user, $reason);

            DB::commit();
            return $application->fresh(['status']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send application back to data entry: ' . $e->getMessage());
            throw $e;
        }
    }
}
