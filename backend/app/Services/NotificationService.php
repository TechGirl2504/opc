<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Application;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $relatedModelType = null,
        ?int $relatedModelId = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_model_type' => $relatedModelType,
            'related_model_id' => $relatedModelId,
        ]);
    }

    /**
     * Notify user when application is assigned
     */
    public function notifyApplicationAssigned(Application $application, User $assignedUser, string $vettingType): void
    {
        $this->createNotification(
            $assignedUser,
            'application_assigned',
            'Application Assigned',
            "Application {$application->application_number} has been assigned to you for {$vettingType} vetting.",
            Application::class,
            $application->id
        );
    }

    /**
     * Notify officer when vetting is sent back for clarifications
     */
    public function notifyVettingSentBack(\App\Models\VettingRecord $vettingRecord, User $officer, string $reason): void
    {
        $application = $vettingRecord->application;
        $vettingType = $vettingRecord->vettingType->name;
        
        $this->createNotification(
            $officer,
            'vetting_sent_back',
            'Vetting Sent Back for Clarifications',
            "Your {$vettingType} vetting for application {$application->application_number} has been sent back for clarifications. Reason: {$reason}",
            \App\Models\Application::class,
            $application->id
        );
    }

    /**
     * Notify OPC when vetting is completed
     */
    public function notifyVettingCompleted(Application $application, string $vettingType): void
    {
        $opcUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['opc_data_entry', 'opc_approver']);
        })->get();

        foreach ($opcUsers as $user) {
            $this->createNotification(
                $user,
                'vetting_completed',
                ucfirst($vettingType) . ' Vetting Completed',
                "{$vettingType} vetting has been completed for application {$application->application_number}.",
                Application::class,
                $application->id
            );
        }
    }

    /**
     * Notify OPC approver when approval is required
     */
    public function notifyApprovalRequired(Application $application): void
    {
        $approvers = User::whereHas('roles', function($query) {
            $query->where('name', 'opc_approver');
        })->get();

        foreach ($approvers as $user) {
            $this->createNotification(
                $user,
                'approval_required',
                'Approval Required',
                "Application {$application->application_number} is ready for final approval. Both vetting processes are complete.",
                Application::class,
                $application->id
            );
        }
    }

    /**
     * Notify when application status changes
     */
    public function notifyStatusChanged(Application $application, string $oldStatus, string $newStatus): void
    {
        // Notify application creator
        if ($application->createdBy) {
            $this->createNotification(
                $application->createdBy,
                'application_status_changed',
                'Application Status Changed',
                "Application {$application->application_number} status has changed from {$oldStatus} to {$newStatus}.",
                Application::class,
                $application->id
            );
        }

        // Notify assigned officers if applicable
        if ($application->assignedPoliceOfficer) {
            $this->createNotification(
                $application->assignedPoliceOfficer,
                'application_status_changed',
                'Application Status Changed',
                "Application {$application->application_number} status has changed to {$newStatus}.",
                Application::class,
                $application->id
            );
        }

        if ($application->assignedNisOfficer) {
            $this->createNotification(
                $application->assignedNisOfficer,
                'application_status_changed',
                'Application Status Changed',
                "Application {$application->application_number} status has changed to {$newStatus}.",
                Application::class,
                $application->id
            );
        }
    }

    /**
     * Notify when document is uploaded
     */
    public function notifyDocumentUploaded(Application $application, User $uploadedBy, string $documentType): void
    {
        // Notify application creator
        if ($application->createdBy && $application->createdBy->id !== $uploadedBy->id) {
            $this->createNotification(
                $application->createdBy,
                'document_uploaded',
                'Document Uploaded',
                "A new {$documentType} document has been uploaded for application {$application->application_number}.",
                Application::class,
                $application->id
            );
        }

        // Notify assigned officers
        if ($application->assignedPoliceOfficer && $application->assignedPoliceOfficer->id !== $uploadedBy->id) {
            $this->createNotification(
                $application->assignedPoliceOfficer,
                'document_uploaded',
                'Document Uploaded',
                "A new document has been uploaded for application {$application->application_number}.",
                Application::class,
                $application->id
            );
        }

        if ($application->assignedNisOfficer && $application->assignedNisOfficer->id !== $uploadedBy->id) {
            $this->createNotification(
                $application->assignedNisOfficer,
                'document_uploaded',
                'Document Uploaded',
                "A new document has been uploaded for application {$application->application_number}.",
                Application::class,
                $application->id
            );
        }
    }

    /**
     * Notify when application is approved
     */
    public function notifyApplicationApproved(Application $application): void
    {
        if ($application->createdBy) {
            $this->createNotification(
                $application->createdBy,
                'application_approved',
                'Application Approved',
                "Application {$application->application_number} has been approved.",
                Application::class,
                $application->id
            );
        }
    }

    /**
     * Notify when application is denied
     */
    public function notifyApplicationDenied(Application $application, string $reason): void
    {
        if ($application->createdBy) {
            $this->createNotification(
                $application->createdBy,
                'application_denied',
                'Application Denied',
                "Application {$application->application_number} has been denied. Reason: {$reason}",
                Application::class,
                $application->id
            );
        }
    }

    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $notification;
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Notify admin when application is sent back by approver
     */
    public function notifyApplicationSentBackToAdmin(Application $application, User $approver, string $reason): void
    {
        $admins = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($admins as $admin) {
            $this->createNotification(
                $admin,
                'application_sent_back_to_admin',
                'Application Sent Back for Review',
                "Application {$application->application_number} has been sent back by approver {$approver->username} for review. Reason: {$reason}",
                Application::class,
                $application->id
            );
        }
    }

    /**
     * Notify officer when application is sent back to them by admin
     */
    public function notifyApplicationSentBackToOfficer(Application $application, User $officer, string $vettingType, ?string $reason = null): void
    {
        $reasonText = $reason ? " Reason: {$reason}" : '';
        $this->createNotification(
            $officer,
            'application_sent_back_to_officer',
            'Application Sent Back for Review',
            "Application {$application->application_number} has been sent back to you for {$vettingType} vetting review.{$reasonText}",
            Application::class,
            $application->id
        );
    }
}

