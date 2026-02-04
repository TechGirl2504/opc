<?php

namespace App\Services;

use App\Models\Application;
use App\Models\VettingRecord;
use App\Models\VettingType;
use App\Models\VettingStatus;
use App\Models\ApplicationStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VettingService
{
    protected AuditService $auditService;
    protected NotificationService $notificationService;

    public function __construct(AuditService $auditService, NotificationService $notificationService)
    {
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get police vetting record for application
     */
    public function getPoliceVetting(Application $application): ?VettingRecord
    {
        $policeType = VettingType::where('code', 'police')->first();
        if (!$policeType) {
            return null;
        }

        return VettingRecord::where('application_id', $application->id)
            ->where('vetting_type_id', $policeType->id)
            ->with(['vettingType', 'status', 'conductedBy', 'recommendation'])
            ->first();
    }

    /**
     * Get NIS vetting record for application
     */
    public function getNisVetting(Application $application): ?VettingRecord
    {
        $nisType = VettingType::where('code', 'nis')->first();
        if (!$nisType) {
            return null;
        }

        return VettingRecord::where('application_id', $application->id)
            ->where('vetting_type_id', $nisType->id)
            ->with(['vettingType', 'status', 'conductedBy', 'recommendation'])
            ->first();
    }

    /**
     * Submit police vetting
     */
    public function submitPoliceVetting(Application $application, array $data, User $user, $file = null): VettingRecord
    {
        DB::beginTransaction();
        try {
            $policeType = VettingType::where('code', 'police')->firstOrFail();
            $inProgressStatus = VettingStatus::where('code', 'in_progress')->firstOrFail();
            $completedStatus = VettingStatus::where('code', 'completed')->firstOrFail();
            $policeCompletedAppStatus = ApplicationStatus::where('code', 'police_completed')->firstOrFail();
            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();

            // Check if vetting record exists
            $vettingRecord = VettingRecord::where('application_id', $application->id)
                ->where('vetting_type_id', $policeType->id)
                ->first();

            if ($vettingRecord) {
                // Update existing record
                $oldValues = $vettingRecord->toArray();
                $vettingRecord->update([
                    'remarks' => $data['remarks'] ?? $vettingRecord->remarks,
                    'findings' => $data['findings'] ?? $vettingRecord->findings,
                    'recommendation_id' => $data['recommendation_id'] ?? $vettingRecord->recommendation_id,
                    'vetting_date' => $data['vetting_date'] ?? $vettingRecord->vetting_date,
                    'status_id' => $completedStatus->id,
                    'completed_at' => now(),
                ]);
            } else {
                // Create new record
                $vettingRecord = VettingRecord::create([
                    'application_id' => $application->id,
                    'vetting_type_id' => $policeType->id,
                    'conducted_by' => $user->id,
                    'status_id' => $completedStatus->id,
                    'remarks' => $data['remarks'] ?? null,
                    'findings' => $data['findings'] ?? null,
                    'recommendation_id' => $data['recommendation_id'] ?? null,
                    'vetting_date' => $data['vetting_date'] ?? now(),
                    'completed_at' => now(),
                ]);
                $oldValues = null;
            }

            // Upload document if provided
            if ($file) {
                $documentType = \App\Models\DocumentType::where('code', 'police_vetting_report')->first();
                if ($documentType) {
                    app(\App\Services\DocumentService::class)->uploadDocument(
                        $application,
                        $file,
                        $documentType->id,
                        $user,
                        'Police vetting report'
                    );
                }
            }

            // Update application status
            $oldAppValues = $application->toArray();
            $application->update([
                'status_id' => $policeCompletedAppStatus->id,
                'police_vetting_completed_at' => now(),
            ]);

            // Auto-transition to OPC review
            $application->update([
                'status_id' => $opcReviewStatus->id,
            ]);

            // Log audit
            if ($oldValues) {
                $this->auditService->logUpdate($user, VettingRecord::class, $vettingRecord->id, $oldValues, $vettingRecord->toArray());
            } else {
                $this->auditService->logCreate($user, VettingRecord::class, $vettingRecord->id, $vettingRecord->toArray());
            }
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldAppValues['status_id'] ?? null, $application->status_id);

            // Send notification to OPC
            $this->notificationService->notifyVettingCompleted($application, 'police');

            DB::commit();
            return $vettingRecord->fresh(['vettingType', 'status', 'conductedBy', 'recommendation']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit police vetting: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submit NIS vetting
     */
    public function submitNisVetting(Application $application, array $data, User $user, $file = null): VettingRecord
    {
        DB::beginTransaction();
        try {
            $nisType = VettingType::where('code', 'nis')->firstOrFail();
            $completedStatus = VettingStatus::where('code', 'completed')->firstOrFail();
            $nisCompletedAppStatus = ApplicationStatus::where('code', 'nis_completed')->firstOrFail();

            // Check if vetting record exists
            $vettingRecord = VettingRecord::where('application_id', $application->id)
                ->where('vetting_type_id', $nisType->id)
                ->first();

            if ($vettingRecord) {
                // Update existing record
                $oldValues = $vettingRecord->toArray();
                $vettingRecord->update([
                    'remarks' => $data['remarks'] ?? $vettingRecord->remarks,
                    'findings' => $data['findings'] ?? $vettingRecord->findings,
                    'recommendation_id' => $data['recommendation_id'] ?? $vettingRecord->recommendation_id,
                    'vetting_date' => $data['vetting_date'] ?? $vettingRecord->vetting_date,
                    'status_id' => $completedStatus->id,
                    'completed_at' => now(),
                ]);
            } else {
                // Create new record
                $vettingRecord = VettingRecord::create([
                    'application_id' => $application->id,
                    'vetting_type_id' => $nisType->id,
                    'conducted_by' => $user->id,
                    'status_id' => $completedStatus->id,
                    'remarks' => $data['remarks'] ?? null,
                    'findings' => $data['findings'] ?? null,
                    'recommendation_id' => $data['recommendation_id'] ?? null,
                    'vetting_date' => $data['vetting_date'] ?? now(),
                    'completed_at' => now(),
                ]);
                $oldValues = null;
            }

            // Upload document if provided
            if ($file) {
                $documentType = \App\Models\DocumentType::where('code', 'nis_vetting_report')->first();
                if ($documentType) {
                    app(\App\Services\DocumentService::class)->uploadDocument(
                        $application,
                        $file,
                        $documentType->id,
                        $user,
                        'NIS vetting report'
                    );
                }
            }

            // Update application status
            $oldAppValues = $application->toArray();
            $application->update([
                'status_id' => $nisCompletedAppStatus->id,
                'nis_vetting_completed_at' => now(),
            ]);

            // Auto-transition to OPC review (same as police vetting)
            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();
            $application->update([
                'status_id' => $opcReviewStatus->id,
            ]);

            // Log audit
            if ($oldValues) {
                $this->auditService->logUpdate($user, VettingRecord::class, $vettingRecord->id, $oldValues, $vettingRecord->toArray());
            } else {
                $this->auditService->logCreate($user, VettingRecord::class, $vettingRecord->id, $vettingRecord->toArray());
            }
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldAppValues['status_id'] ?? null, $application->status_id);

            // Send notification to OPC (for review, not approval yet)
            $this->notificationService->notifyVettingCompleted($application, 'nis');

            DB::commit();
            return $vettingRecord->fresh(['vettingType', 'status', 'conductedBy', 'recommendation']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit NIS vetting: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update vetting record
     */
    public function updateVetting(VettingRecord $vettingRecord, array $data, User $user, $file = null): VettingRecord
    {
        DB::beginTransaction();
        try {
            $oldValues = $vettingRecord->toArray();

            $vettingRecord->update([
                'remarks' => $data['remarks'] ?? $vettingRecord->remarks,
                'findings' => $data['findings'] ?? $vettingRecord->findings,
                'recommendation_id' => $data['recommendation_id'] ?? $vettingRecord->recommendation_id,
                'vetting_date' => $data['vetting_date'] ?? $vettingRecord->vetting_date,
            ]);

            // Upload document if provided
            if ($file) {
                $application = $vettingRecord->application;
                $documentTypeCode = $vettingRecord->vettingType->code === 'police' 
                    ? 'police_vetting_report' 
                    : 'nis_vetting_report';
                
                $documentType = \App\Models\DocumentType::where('code', $documentTypeCode)->first();
                if ($documentType) {
                    app(\App\Services\DocumentService::class)->uploadDocument(
                        $application,
                        $file,
                        $documentType->id,
                        $user,
                        ucfirst($vettingRecord->vettingType->code) . ' vetting report update'
                    );
                }
            }

            // Log audit
            $this->auditService->logUpdate($user, VettingRecord::class, $vettingRecord->id, $oldValues, $vettingRecord->toArray());

            DB::commit();
            return $vettingRecord->fresh(['vettingType', 'status', 'conductedBy', 'recommendation']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update vetting: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save police vetting as draft (in_progress status, doesn't move application forward)
     */
    public function savePoliceVettingDraft(Application $application, array $data, User $user, $file = null): VettingRecord
    {
        DB::beginTransaction();
        try {
            $policeType = VettingType::where('code', 'police')->firstOrFail();
            $inProgressStatus = VettingStatus::where('code', 'in_progress')->firstOrFail();

            // Check if vetting record exists
            $vettingRecord = VettingRecord::where('application_id', $application->id)
                ->where('vetting_type_id', $policeType->id)
                ->first();

            if ($vettingRecord) {
                // Update existing record (keep as draft)
                $oldValues = $vettingRecord->toArray();
                $vettingRecord->update([
                    'remarks' => $data['remarks'] ?? $vettingRecord->remarks,
                    'findings' => $data['findings'] ?? $vettingRecord->findings,
                    'recommendation_id' => $data['recommendation_id'] ?? $vettingRecord->recommendation_id,
                    'vetting_date' => $data['vetting_date'] ?? $vettingRecord->vetting_date,
                    'status_id' => $inProgressStatus->id,
                    'completed_at' => null, // Draft is not completed
                ]);
            } else {
                // Create new draft record
                $vettingRecord = VettingRecord::create([
                    'application_id' => $application->id,
                    'vetting_type_id' => $policeType->id,
                    'conducted_by' => $user->id,
                    'status_id' => $inProgressStatus->id,
                    'remarks' => $data['remarks'] ?? null,
                    'findings' => $data['findings'] ?? null,
                    'recommendation_id' => $data['recommendation_id'] ?? null,
                    'vetting_date' => $data['vetting_date'] ?? now(),
                    'completed_at' => null,
                ]);
                $oldValues = null;
            }

            // Upload document if provided
            if ($file) {
                $documentType = \App\Models\DocumentType::where('code', 'police_vetting_report')->first();
                if ($documentType) {
                    app(\App\Services\DocumentService::class)->uploadDocument(
                        $application,
                        $file,
                        $documentType->id,
                        $user,
                        'Police vetting report (draft)'
                    );
                }
            }

            // Log audit
            if ($oldValues) {
                $this->auditService->logUpdate($user, VettingRecord::class, $vettingRecord->id, $oldValues, $vettingRecord->toArray());
            } else {
                $this->auditService->logCreate($user, VettingRecord::class, $vettingRecord->id, $vettingRecord->toArray());
            }

            // Note: Application status does NOT change for drafts

            DB::commit();
            return $vettingRecord->fresh(['vettingType', 'status', 'conductedBy', 'recommendation']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save police vetting draft: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Complete police vetting (completed status, moves application forward)
     */
    public function completePoliceVetting(Application $application, array $data, User $user, $file = null): VettingRecord
    {
        DB::beginTransaction();
        try {
            $policeType = VettingType::where('code', 'police')->firstOrFail();
            $completedStatus = VettingStatus::where('code', 'completed')->firstOrFail();
            $policeCompletedAppStatus = ApplicationStatus::where('code', 'police_completed')->firstOrFail();
            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();

            // Check if vetting record exists
            $vettingRecord = VettingRecord::where('application_id', $application->id)
                ->where('vetting_type_id', $policeType->id)
                ->first();

            if ($vettingRecord) {
                // Update existing record to completed
                $oldValues = $vettingRecord->toArray();
                $vettingRecord->update([
                    'remarks' => $data['remarks'] ?? $vettingRecord->remarks,
                    'findings' => $data['findings'] ?? $vettingRecord->findings,
                    'recommendation_id' => $data['recommendation_id'] ?? $vettingRecord->recommendation_id,
                    'vetting_date' => $data['vetting_date'] ?? $vettingRecord->vetting_date,
                    'status_id' => $completedStatus->id,
                    'completed_at' => now(),
                ]);
            } else {
                // Create new completed record
                $vettingRecord = VettingRecord::create([
                    'application_id' => $application->id,
                    'vetting_type_id' => $policeType->id,
                    'conducted_by' => $user->id,
                    'status_id' => $completedStatus->id,
                    'remarks' => $data['remarks'] ?? null,
                    'findings' => $data['findings'] ?? null,
                    'recommendation_id' => $data['recommendation_id'] ?? null,
                    'vetting_date' => $data['vetting_date'] ?? now(),
                    'completed_at' => now(),
                ]);
                $oldValues = null;
            }

            // Upload document if provided
            if ($file) {
                $documentType = \App\Models\DocumentType::where('code', 'police_vetting_report')->first();
                if ($documentType) {
                    app(\App\Services\DocumentService::class)->uploadDocument(
                        $application,
                        $file,
                        $documentType->id,
                        $user,
                        'Police vetting report'
                    );
                }
            }

            // Update application status
            $oldAppValues = $application->toArray();
            $application->update([
                'status_id' => $policeCompletedAppStatus->id,
                'police_vetting_completed_at' => now(),
            ]);

            // Auto-transition to OPC review
            $application->update([
                'status_id' => $opcReviewStatus->id,
            ]);

            // Log audit
            if ($oldValues) {
                $this->auditService->logUpdate($user, VettingRecord::class, $vettingRecord->id, $oldValues, $vettingRecord->toArray());
            } else {
                $this->auditService->logCreate($user, VettingRecord::class, $vettingRecord->id, $vettingRecord->toArray());
            }
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldAppValues['status_id'] ?? null, $application->status_id);

            // Send notification to OPC
            $this->notificationService->notifyVettingCompleted($application, 'police');

            DB::commit();
            return $vettingRecord->fresh(['vettingType', 'status', 'conductedBy', 'recommendation']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete police vetting: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save NIS vetting as draft (in_progress status, doesn't move application forward)
     */
    public function saveNisVettingDraft(Application $application, array $data, User $user, $file = null): VettingRecord
    {
        DB::beginTransaction();
        try {
            $nisType = VettingType::where('code', 'nis')->firstOrFail();
            $inProgressStatus = VettingStatus::where('code', 'in_progress')->firstOrFail();

            // Check if vetting record exists
            $vettingRecord = VettingRecord::where('application_id', $application->id)
                ->where('vetting_type_id', $nisType->id)
                ->first();

            if ($vettingRecord) {
                // Update existing record (keep as draft)
                $oldValues = $vettingRecord->toArray();
                $vettingRecord->update([
                    'remarks' => $data['remarks'] ?? $vettingRecord->remarks,
                    'findings' => $data['findings'] ?? $vettingRecord->findings,
                    'recommendation_id' => $data['recommendation_id'] ?? $vettingRecord->recommendation_id,
                    'vetting_date' => $data['vetting_date'] ?? $vettingRecord->vetting_date,
                    'status_id' => $inProgressStatus->id,
                    'completed_at' => null, // Draft is not completed
                ]);
            } else {
                // Create new draft record
                $vettingRecord = VettingRecord::create([
                    'application_id' => $application->id,
                    'vetting_type_id' => $nisType->id,
                    'conducted_by' => $user->id,
                    'status_id' => $inProgressStatus->id,
                    'remarks' => $data['remarks'] ?? null,
                    'findings' => $data['findings'] ?? null,
                    'recommendation_id' => $data['recommendation_id'] ?? null,
                    'vetting_date' => $data['vetting_date'] ?? now(),
                    'completed_at' => null,
                ]);
                $oldValues = null;
            }

            // Upload document if provided
            if ($file) {
                $documentType = \App\Models\DocumentType::where('code', 'nis_vetting_report')->first();
                if ($documentType) {
                    app(\App\Services\DocumentService::class)->uploadDocument(
                        $application,
                        $file,
                        $documentType->id,
                        $user,
                        'NIS vetting report (draft)'
                    );
                }
            }

            // Log audit
            if ($oldValues) {
                $this->auditService->logUpdate($user, VettingRecord::class, $vettingRecord->id, $oldValues, $vettingRecord->toArray());
            } else {
                $this->auditService->logCreate($user, VettingRecord::class, $vettingRecord->id, $vettingRecord->toArray());
            }

            // Note: Application status does NOT change for drafts

            DB::commit();
            return $vettingRecord->fresh(['vettingType', 'status', 'conductedBy', 'recommendation']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save NIS vetting draft: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Complete NIS vetting (completed status, moves application forward)
     */
    public function completeNisVetting(Application $application, array $data, User $user, $file = null): VettingRecord
    {
        DB::beginTransaction();
        try {
            $nisType = VettingType::where('code', 'nis')->firstOrFail();
            $completedStatus = VettingStatus::where('code', 'completed')->firstOrFail();
            $nisCompletedAppStatus = ApplicationStatus::where('code', 'nis_completed')->firstOrFail();
            $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();

            // Check if vetting record exists
            $vettingRecord = VettingRecord::where('application_id', $application->id)
                ->where('vetting_type_id', $nisType->id)
                ->first();

            if ($vettingRecord) {
                // Update existing record to completed
                $oldValues = $vettingRecord->toArray();
                $vettingRecord->update([
                    'remarks' => $data['remarks'] ?? $vettingRecord->remarks,
                    'findings' => $data['findings'] ?? $vettingRecord->findings,
                    'recommendation_id' => $data['recommendation_id'] ?? $vettingRecord->recommendation_id,
                    'vetting_date' => $data['vetting_date'] ?? $vettingRecord->vetting_date,
                    'status_id' => $completedStatus->id,
                    'completed_at' => now(),
                ]);
            } else {
                // Create new completed record
                $vettingRecord = VettingRecord::create([
                    'application_id' => $application->id,
                    'vetting_type_id' => $nisType->id,
                    'conducted_by' => $user->id,
                    'status_id' => $completedStatus->id,
                    'remarks' => $data['remarks'] ?? null,
                    'findings' => $data['findings'] ?? null,
                    'recommendation_id' => $data['recommendation_id'] ?? null,
                    'vetting_date' => $data['vetting_date'] ?? now(),
                    'completed_at' => now(),
                ]);
                $oldValues = null;
            }

            // Upload document if provided
            if ($file) {
                $documentType = \App\Models\DocumentType::where('code', 'nis_vetting_report')->first();
                if ($documentType) {
                    app(\App\Services\DocumentService::class)->uploadDocument(
                        $application,
                        $file,
                        $documentType->id,
                        $user,
                        'NIS vetting report'
                    );
                }
            }

            // Update application status
            $oldAppValues = $application->toArray();
            $application->update([
                'status_id' => $nisCompletedAppStatus->id,
                'nis_vetting_completed_at' => now(),
            ]);

            // Auto-transition to OPC review (same as police vetting)
            $application->update([
                'status_id' => $opcReviewStatus->id,
            ]);

            // Log audit
            if ($oldValues) {
                $this->auditService->logUpdate($user, VettingRecord::class, $vettingRecord->id, $oldValues, $vettingRecord->toArray());
            } else {
                $this->auditService->logCreate($user, VettingRecord::class, $vettingRecord->id, $vettingRecord->toArray());
            }
            $this->auditService->logStatusChange($user, Application::class, $application->id, $oldAppValues['status_id'] ?? null, $application->status_id);

            // Send notification to OPC (for review, not approval yet)
            $this->notificationService->notifyVettingCompleted($application, 'nis');

            DB::commit();
            return $vettingRecord->fresh(['vettingType', 'status', 'conductedBy', 'recommendation']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete NIS vetting: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send back vetting record for clarifications
     */
    public function sendBackVetting(VettingRecord $vettingRecord, string $reason, User $user): VettingRecord
    {
        DB::beginTransaction();
        try {
            // Get or create sent_back status if it doesn't exist
            $sentBackStatus = VettingStatus::firstOrCreate(
                ['code' => 'sent_back'],
                [
                    'name' => 'Sent Back',
                    'description' => 'Vetting has been sent back for clarifications',
                    'is_active' => true,
                ]
            );
            $application = $vettingRecord->application;
            
            // Determine which vetting type to return to
            $vettingType = $vettingRecord->vettingType;
            $targetAppStatus = null;
            
            if ($vettingType->code === 'police') {
                $targetAppStatus = ApplicationStatus::where('code', 'police_vetting')->firstOrFail();
            } elseif ($vettingType->code === 'nis') {
                $targetAppStatus = ApplicationStatus::where('code', 'nis_vetting')->firstOrFail();
            } else {
                throw new \Exception('Invalid vetting type');
            }

            $oldValues = $vettingRecord->toArray();
            $oldAppValues = $application->toArray();

            // Update vetting record status and return reason
            $vettingRecord->update([
                'status_id' => $sentBackStatus->id,
                'return_reason' => $reason,
                'completed_at' => null, // Reset completed_at since it's being sent back
            ]);

            // Update application status back to vetting
            $application->update([
                'status_id' => $targetAppStatus->id,
            ]);

            // Log audit
            $this->auditService->logUpdate($user, VettingRecord::class, $vettingRecord->id, $oldValues, $vettingRecord->toArray());
            $this->auditService->logUpdate($user, Application::class, $application->id, $oldAppValues, $application->toArray());

            // Send notification to the officer who conducted the vetting
            $officer = $vettingRecord->conductedBy;
            if ($officer) {
                $this->notificationService->notifyVettingSentBack($vettingRecord, $officer, $reason);
            }

            DB::commit();
            return $vettingRecord->fresh(['vettingType', 'status', 'conductedBy', 'recommendation', 'application.status']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send back vetting: ' . $e->getMessage());
            throw $e;
        }
    }

}

