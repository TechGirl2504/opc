<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DecisionResource extends JsonResource
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
            'decision_type' => $this->whenLoaded('decisionType', function () {
                return [
                    'id' => $this->decisionType->id,
                    'name' => $this->decisionType->name,
                    'code' => $this->decisionType->code,
                ];
            }),
            'decision_value' => $this->whenLoaded('decisionValue', function () {
                return [
                    'id' => $this->decisionValue->id,
                    'name' => $this->decisionValue->name,
                    'code' => $this->decisionValue->code,
                ];
            }),
            'decided_by' => $this->whenLoaded('decidedBy', function () {
                return [
                    'id' => $this->decidedBy->id,
                    'username' => $this->decidedBy->username,
                ];
            }),
            'denial_reason' => $this->denial_reason,
            'conditions' => $this->conditions,
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
