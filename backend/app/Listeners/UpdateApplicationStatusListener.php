<?php

namespace App\Listeners;

use App\Events\VettingCompleted;
use App\Models\ApplicationStatus;

class UpdateApplicationStatusListener
{
    /**
     * Handle VettingCompleted event
     * Auto-transition application status based on vetting completion
     */
    public function handle(VettingCompleted $event): void
    {
        $application = $event->application;
        $vettingType = $event->vettingType;

        if ($vettingType === 'police') {
            // After police vetting, move to OPC review
            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->first();
            if ($opcReviewStatus) {
                $application->update(['status_id' => $opcReviewStatus->id]);
            }
        } elseif ($vettingType === 'nis') {
            // After NIS vetting, move to OPC review (same as police vetting)
            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->first();
            if ($opcReviewStatus) {
                $application->update(['status_id' => $opcReviewStatus->id]);
            }
        }
    }
}
