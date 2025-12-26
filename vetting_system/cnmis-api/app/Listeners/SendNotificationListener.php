<?php

namespace App\Listeners;

use App\Events\ApplicationCreated;
use App\Events\ApplicationStatusChanged;
use App\Events\VettingCompleted;
use App\Events\ApplicationApproved;
use App\Events\ApplicationDenied;
use App\Events\DocumentUploaded;
use App\Services\NotificationService;

class SendNotificationListener
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle ApplicationCreated event
     */
    public function handleApplicationCreated(ApplicationCreated $event): void
    {
        // Notifications for application creation are handled in ApplicationService
        // This listener can be used for additional notifications if needed
    }

    /**
     * Handle ApplicationStatusChanged event
     */
    public function handleApplicationStatusChanged(ApplicationStatusChanged $event): void
    {
        $oldStatus = $event->oldStatusId ? \App\Models\ApplicationStatus::find($event->oldStatusId)?->name : 'Unknown';
        $newStatus = $event->newStatusId ? \App\Models\ApplicationStatus::find($event->newStatusId)?->name : 'Unknown';
        
        $this->notificationService->notifyStatusChanged(
            $event->application,
            $oldStatus,
            $newStatus
        );
    }

    /**
     * Handle VettingCompleted event
     */
    public function handleVettingCompleted(VettingCompleted $event): void
    {
        $this->notificationService->notifyVettingCompleted(
            $event->application,
            $event->vettingType
        );
    }

    /**
     * Handle ApplicationApproved event
     */
    public function handleApplicationApproved(ApplicationApproved $event): void
    {
        $this->notificationService->notifyApplicationApproved($event->application);
    }

    /**
     * Handle ApplicationDenied event
     */
    public function handleApplicationDenied(ApplicationDenied $event): void
    {
        $this->notificationService->notifyApplicationDenied(
            $event->application,
            $event->decision->denial_reason ?? 'No reason provided'
        );
    }

    /**
     * Handle DocumentUploaded event
     */
    public function handleDocumentUploaded(DocumentUploaded $event): void
    {
        $this->notificationService->notifyDocumentUploaded(
            $event->application,
            $event->document->uploadedBy,
            $event->document->documentType->name ?? 'Document'
        );
    }
}
