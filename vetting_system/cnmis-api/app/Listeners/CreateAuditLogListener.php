<?php

namespace App\Listeners;

use App\Events\ApplicationCreated;
use App\Events\ApplicationStatusChanged;
use App\Events\VettingCompleted;
use App\Events\ApplicationApproved;
use App\Events\ApplicationDenied;
use App\Events\DocumentUploaded;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class CreateAuditLogListener
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle ApplicationCreated event
     */
    public function handleApplicationCreated(ApplicationCreated $event): void
    {
        $user = Auth::user();
        if ($user) {
            $this->auditService->logCreate(
                $user,
                \App\Models\Application::class,
                $event->application->id,
                $event->application->toArray()
            );
        }
    }

    /**
     * Handle ApplicationStatusChanged event
     */
    public function handleApplicationStatusChanged(ApplicationStatusChanged $event): void
    {
        $user = Auth::user();
        if ($user) {
            $this->auditService->logStatusChange(
                $user,
                \App\Models\Application::class,
                $event->application->id,
                $event->oldStatusId,
                $event->newStatusId
            );
        }
    }

    /**
     * Handle VettingCompleted event
     */
    public function handleVettingCompleted(VettingCompleted $event): void
    {
        $user = Auth::user();
        if ($user) {
            $this->auditService->logCreate(
                $user,
                \App\Models\VettingRecord::class,
                $event->vettingRecord->id,
                $event->vettingRecord->toArray()
            );
        }
    }

    /**
     * Handle ApplicationApproved event
     */
    public function handleApplicationApproved(ApplicationApproved $event): void
    {
        $user = Auth::user();
        if ($user) {
            $this->auditService->logCreate(
                $user,
                \App\Models\Decision::class,
                $event->decision->id,
                $event->decision->toArray()
            );
        }
    }

    /**
     * Handle ApplicationDenied event
     */
    public function handleApplicationDenied(ApplicationDenied $event): void
    {
        $user = Auth::user();
        if ($user) {
            $this->auditService->logCreate(
                $user,
                \App\Models\Decision::class,
                $event->decision->id,
                $event->decision->toArray()
            );
        }
    }

    /**
     * Handle DocumentUploaded event
     */
    public function handleDocumentUploaded(DocumentUploaded $event): void
    {
        $user = Auth::user();
        if ($user) {
            $this->auditService->logCreate(
                $user,
                \App\Models\Document::class,
                $event->document->id,
                $event->document->toArray()
            );
        }
    }
}
