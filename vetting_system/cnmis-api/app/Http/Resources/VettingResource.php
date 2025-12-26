<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VettingResource extends JsonResource
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
            'application_id' => $this->application_id,
            'vetting_type' => $this->whenLoaded('vettingType', function () {
                return [
                    'id' => $this->vettingType->id,
                    'name' => $this->vettingType->name,
                    'code' => $this->vettingType->code,
                ];
            }),
            'status' => $this->whenLoaded('vettingStatus', function () {
                return [
                    'id' => $this->vettingStatus->id,
                    'name' => $this->vettingStatus->name,
                    'code' => $this->vettingStatus->code,
                ];
            }),
            'conducted_by' => $this->whenLoaded('conductedBy', function () {
                return [
                    'id' => $this->conductedBy->id,
                    'username' => $this->conductedBy->username,
                ];
            }),
            'remarks' => $this->remarks,
            'findings' => $this->findings,
            'recommendation' => $this->whenLoaded('recommendation', function () {
                return [
                    'id' => $this->recommendation->id,
                    'name' => $this->recommendation->name,
                    'code' => $this->recommendation->code,
                ];
            }),
            'vetting_date' => $this->vetting_date?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
