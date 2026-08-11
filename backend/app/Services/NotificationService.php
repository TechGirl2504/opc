<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Application;

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
     * Notify every user with a given role.
     *
     * @param array<int, string> $roles
     */
    private function notifyRoleUsers(
        array $roles,
        string $type,
        string $title,
        string $message,
        Application $application,
        ?int $excludeUserId = null
    ): void {
        $query = User::whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        });

        if ($excludeUserId !== null) {
            $query->where('id', '!=', $excludeUserId);
        }

        foreach ($query->get() as $user) {
            $this->createNotification(
                $user,
                $type,
                $title,
                $message,
                Application::class,
                $application->id
            );
        }
    }

    /**
     * Notify admins that a new or resubmitted application has been received.
     */
    public function notifyApplicationReceivedByAdmins(Application $application, ?User $initiator = null): void
    {
        $this->notifyRoleUsers(
            ['admin'],
            'application_received',
            'Application Received',
            "Application {$application->application_number} has been received and is awaiting review.",
            $application,
            $initiator?->id
        );
    }

    /**
     * Notify user when application is assigned
     */
    public function notifyApplicationAssigned(Application $application, User $assignedUser, string $vettingType): void
    {
        $this->createNotification(
            $assignedUser,
            'application_assigned',
            'Application Received for Vetting',
            "Application {$application->application_number} has been received and assigned to you for {$vettingType} vetting.",
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
     * Notify admins when vetting is completed and the file returns to OPC review.
     */
    public function notifyVettingCompleted(Application $application, string $vettingType): void
    {
        $this->notifyRoleUsers(
            ['admin'],
            'vetting_completed',
            ucfirst($vettingType) . ' Vetting Completed',
            ucfirst($vettingType) . " vetting has been completed for application {$application->application_number} and returned to OPC review.",
            $application
        );
    }

    /**
     * Notify OPC approvers when approval is required.
     */
    public function notifyApprovalRequired(Application $application): void
    {
        $this->notifyRoleUsers(
            ['opc_approver'],
            'approval_required',
            'Application Ready for Approval',
            "Application {$application->application_number} is ready for final approval. Both vetting processes are complete.",
            $application
        );
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
        $this->notifyRoleUsers(
            ['admin'],
            'application_sent_back_to_admin',
            'Application Sent Back for Review',
            "Application {$application->application_number} has been sent back by approver {$approver->username} for review. Reason: {$reason}",
            $application,
            $approver->id
        );
    }

    /**
     * Notify the application creator when a pending record is sent back for correction.
     */
    public function notifyApplicationSentBackToDataEntry(Application $application, User $reviewer, string $reason): void
    {
        if (!$application->createdBy) {
            return;
        }

        $this->createNotification(
            $application->createdBy,
            'application_sent_back_to_data_entry',
            'Application Returned for Correction',
            "Application {$application->application_number} was returned by {$reviewer->username} for correction. Reason: {$reason}",
            Application::class,
            $application->id
        );
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
