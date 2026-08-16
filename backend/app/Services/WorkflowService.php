<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\VettingRecord;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    /**
     * Validate state transition
     */
    public function canTransition(Application $application, string $targetStatusCode): bool
    {
        $currentStatus = $application->status;
        if (!$currentStatus) {
            return false;
        }

        $targetStatus = ApplicationStatus::where('code', $targetStatusCode)->first();
        if (!$targetStatus) {
            return false;
        }

        // Define valid transitions
        $validTransitions = [
            'draft' => ['handoff_to_admin'],
            'handoff_to_admin' => ['returned_to_data_entry', 'police_vetting', 'pending_approval'],
            'returned_to_data_entry' => ['draft', 'handoff_to_admin'],
            'police_vetting' => ['police_completed'],
            'police_completed' => ['opc_review'],
            'opc_review' => ['nis_vetting', 'police_vetting', 'pending_approval'],
            'nis_vetting' => ['nis_completed'],
            'nis_completed' => ['opc_review'],
            'pending_approval' => ['approved', 'denied', 'opc_review'],
            'approved' => ['archived'],
            'denied' => ['archived'],
        ];

        $currentCode = $currentStatus->code;
        $allowedTargets = $validTransitions[$currentCode] ?? [];

        return in_array($targetStatusCode, $allowedTargets);
    }

    /**
     * Transition application to new status
     */
    public function transitionTo(Application $application, string $targetStatusCode, ?string $reason = null): Application
    {
        if (!$this->canTransition($application, $targetStatusCode)) {
            $currentStatus = $application->status->code ?? 'unknown';
            throw new \Exception("Invalid state transition from '{$currentStatus}' to '{$targetStatusCode}'");
        }

        $targetStatus = ApplicationStatus::where('code', $targetStatusCode)->firstOrFail();
        $application->update(['status_id' => $targetStatus->id]);

        Log::info("Application {$application->application_number} transitioned to {$targetStatusCode}", [
            'application_id' => $application->id,
            'from_status' => $application->getOriginal('status_id'),
            'to_status' => $targetStatus->id,
            'reason' => $reason,
        ]);

        return $application->fresh(['status']);
    }

    /**
     * Check if police vetting is completed
     */
    public function isPoliceVettingCompleted(Application $application): bool
    {
        $policeVetting = $application->policeVetting;
        return $policeVetting && $policeVetting->status->code === 'completed';
    }

    /**
     * Check if NIS vetting is completed
     */
    public function isNisVettingCompleted(Application $application): bool
    {
        $nisVetting = $application->nisVetting;
        return $nisVetting && $nisVetting->status->code === 'completed';
    }

    /**
     * Check if application is ready for final approval
     */
    public function isReadyForApproval(Application $application): bool
    {
        return $this->isPoliceVettingCompleted($application) 
            && $this->isNisVettingCompleted($application)
            && $application->status->code === 'pending_approval';
    }

    /**
     * Get next valid statuses for an application
     */
    public function getNextValidStatuses(Application $application): array
    {
        $currentStatus = $application->status;
        if (!$currentStatus) {
            return [];
        }

        $validTransitions = [
            'draft' => ['handoff_to_admin'],
            'handoff_to_admin' => ['returned_to_data_entry', 'police_vetting', 'pending_approval'],
            'returned_to_data_entry' => ['draft', 'handoff_to_admin'],
            'police_vetting' => ['police_completed'],
            'police_completed' => ['opc_review'],
            'opc_review' => ['nis_vetting', 'police_vetting', 'pending_approval'],
            'nis_vetting' => ['nis_completed'],
            'nis_completed' => ['opc_review'],
            'pending_approval' => ['approved', 'denied', 'opc_review'],
            'approved' => ['archived'],
            'denied' => ['archived'],
        ];

        $currentCode = $currentStatus->code;
        $nextCodes = $validTransitions[$currentCode] ?? [];

        return ApplicationStatus::whereIn('code', $nextCodes)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }
}
