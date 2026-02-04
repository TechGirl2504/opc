<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Decision;
use App\Models\DecisionType;
use App\Models\DecisionValue;
use App\Models\ApplicationStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DecisionService
{
    protected AuditService $auditService;
    protected NotificationService $notificationService;

    public function __construct(AuditService $auditService, NotificationService $notificationService)
    {
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
    }
    /**
     * Approve an application
     */
    public function approveApplication(Application $application, User $user, ?string $conditions = null): Decision
    {
        DB::beginTransaction();
        try {
            // Verify both vetting are completed
            $policeVetting = $application->policeVetting;
            $nisVetting = $application->nisVetting;

            if (!$policeVetting || $policeVetting->status->code !== 'completed') {
                throw new \Exception('Police vetting must be completed before approval');
            }

            if (!$nisVetting || $nisVetting->status->code !== 'completed') {
                throw new \Exception('NIS vetting must be completed before approval');
            }

            // Get decision types and values
            $finalApprovalType = DecisionType::where('code', 'final_approval')->firstOrFail();
            $approvedValue = DecisionValue::where('code', 'approved')->firstOrFail();
            $approvedStatus = ApplicationStatus::where('code', 'approved')->firstOrFail();

            // Create decision record
            $decision = Decision::create([
                'application_id' => $application->id,
                'decision_type_id' => $finalApprovalType->id,
                'decision_value_id' => $approvedValue->id,
                'decided_by' => $user->id,
                'conditions' => $conditions,
                'decided_at' => now(),
            ]);

            // Update application status
            $oldValues = $application->toArray();
            $application->update([
                'status_id' => $approvedStatus->id,
                'assigned_opc_approver_id' => $user->id,
                'decided_at' => now(),
            ]);

            // Log audit
            $this->auditService->logCreate($user, Decision::class, $decision->id, $decision->toArray());
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldValues['status_id'] ?? null, $application->status_id);

            // Send notification
            $this->notificationService->notifyApplicationApproved($application);

            DB::commit();
            return $decision->load(['decisionType', 'decisionValue', 'decidedBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve application: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deny an application
     */
    public function denyApplication(Application $application, User $user, string $denialReason): Decision
    {
        DB::beginTransaction();
        try {
            // Get decision types and values
            $finalApprovalType = DecisionType::where('code', 'final_approval')->firstOrFail();
            $deniedValue = DecisionValue::where('code', 'denied')->firstOrFail();
            $deniedStatus = ApplicationStatus::where('code', 'denied')->firstOrFail();

            // Create decision record
            $decision = Decision::create([
                'application_id' => $application->id,
                'decision_type_id' => $finalApprovalType->id,
                'decision_value_id' => $deniedValue->id,
                'decided_by' => $user->id,
                'denial_reason' => $denialReason,
                'decided_at' => now(),
            ]);

            // Update application status
            $oldValues = $application->toArray();
            $application->update([
                'status_id' => $deniedStatus->id,
                'assigned_opc_approver_id' => $user->id,
                'decided_at' => now(),
            ]);

            // Log audit
            $this->auditService->logCreate($user, Decision::class, $decision->id, $decision->toArray());
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldValues['status_id'] ?? null, $application->status_id);

            // Send notification
            $this->notificationService->notifyApplicationDenied($application, $denialReason);

            DB::commit();
            return $decision->load(['decisionType', 'decisionValue', 'decidedBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to deny application: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get decision history for an application
     */
    public function getDecisionHistory(Application $application)
    {
        return Decision::where('application_id', $application->id)
            ->with(['decisionType', 'decisionValue', 'decidedBy'])
            ->orderBy('decided_at', 'desc')
            ->get();
    }

}

