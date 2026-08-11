<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_number' => $this->application_number,
            'full_name' => $this->full_name,
            'national_id' => $this->national_id,
            'district' => $this->district,
            'traditional_authority' => $this->traditional_authority,
            'village' => $this->village,
            'current_name' => $this->current_name,
            'requested_name' => $this->requested_name,
            'reason' => $this->reason,
            'status' => [
                'id' => $this->status->id ?? null,
                'name' => $this->status->name ?? null,
                'code' => $this->status->code ?? null,
            ],
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'username' => $this->createdBy->username,
                    'email' => $this->createdBy->email,
                ];
            }),
            'assigned_police_officer' => $this->whenLoaded('assignedPoliceOfficer', function () {
                return [
                    'id' => $this->assignedPoliceOfficer->id,
                    'username' => $this->assignedPoliceOfficer->username,
                ];
            }),
            'assigned_nis_officer' => $this->whenLoaded('assignedNisOfficer', function () {
                return [
                    'id' => $this->assignedNisOfficer->id,
                    'username' => $this->assignedNisOfficer->username,
                ];
            }),
            'assigned_opc_approver' => $this->whenLoaded('assignedOpcApprover', function () {
                return [
                    'id' => $this->assignedOpcApprover->id,
                    'username' => $this->assignedOpcApprover->username,
                ];
            }),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'police_vetting_completed_at' => $this->police_vetting_completed_at?->toIso8601String(),
            'nis_vetting_completed_at' => $this->nis_vetting_completed_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'approver_send_back_reason' => $this->approver_send_back_reason,
            'approver_send_back_at' => $this->approver_send_back_at?->toIso8601String(),
            'data_entry_return_reason' => $this->data_entry_return_reason,
            'data_entry_return_at' => $this->data_entry_return_at?->toIso8601String(),
            'allowed_actions' => app(\App\Services\ApplicationService::class)->getAllowedActions($this->resource, request()->user()),
            'documents_count' => $this->whenCounted('documents'),
            'vetting_records_count' => $this->whenCounted('vettingRecords'),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
