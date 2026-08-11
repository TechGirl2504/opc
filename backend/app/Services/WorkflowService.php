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
            'pending' => ['returned_to_data_entry', 'police_vetting'],
            'returned_to_data_entry' => ['pending'],
            'police_vetting' => ['police_completed'],
            'police_completed' => ['opc_review'],
            'opc_review' => ['nis_vetting', 'police_vetting', 'pending_approval'], // Allow going back to vetting when sent back, or forward to approval
            'nis_vetting' => ['nis_completed'],
            'nis_completed' => ['opc_review'], // NIS completion goes to OPC review (same as police)
            'pending_approval' => ['approved', 'denied', 'opc_review'], // Allow sending back to admin (opc_review)
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
            'pending' => ['returned_to_data_entry', 'police_vetting'],
            'returned_to_data_entry' => ['pending'],
            'police_vetting' => ['police_completed'],
            'police_completed' => ['opc_review'],
            'opc_review' => ['nis_vetting', 'police_vetting', 'pending_approval'], // Allow going back to vetting when sent back, or forward to approval
            'nis_vetting' => ['nis_completed'],
            'nis_completed' => ['opc_review'], // NIS completion goes to OPC review (same as police)
            'pending_approval' => ['approved', 'denied', 'opc_review'], // Allow sending back to admin (opc_review)
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
