<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VettingRecord;
use App\Models\Application;

class SendBackVettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!($this->user()?->hasActivePermission('send back vetting') ?? false)) {
            return false;
        }

        // Get vetting record ID from route
        $vettingId = $this->route('id');
        if (!$vettingId) {
            return false;
        }

        $vettingRecord = VettingRecord::find($vettingId);
        if (!$vettingRecord) {
            return false;
        }

        $application = $vettingRecord->application;
        
        // Only allow sending back if application is in OPC review status
        // and vetting is completed
        if ($application->status->code !== 'opc_review') {
            return false;
        }

        if ($vettingRecord->status->code !== 'completed') {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'A reason for sending back is required.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'reason.max' => 'The reason must not exceed 2000 characters.',
        ];
    }
}
