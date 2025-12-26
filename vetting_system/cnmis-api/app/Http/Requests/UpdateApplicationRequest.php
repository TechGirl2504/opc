<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;

class UpdateApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get application ID from route
        $applicationId = $this->route('id');
        if (!$applicationId) {
            return false;
        }

        $application = Application::find($applicationId);
        if (!$application) {
            return false;
        }
        
        // Check if application is assigned - if assigned, no one can edit (not even admin)
        if ($application->assigned_police_officer_id || $application->assigned_nis_officer_id) {
            return false;
        }

        // Only allow update if application is in pending status or user is admin
        if ($this->user()->hasRole('admin')) {
            return true;
        }

        // OPC Data Entry can only update pending applications
        if ($this->user()->hasRole('opc_data_entry')) {
            $pendingStatus = \App\Models\ApplicationStatus::where('code', 'pending')->first();
            return $application && $application->status_id === $pendingStatus?->id;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'full_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/',
            ],
            'national_id' => [
                'nullable',
                'string',
                'regex:/^[A-Z0-9]{8}$/',
            ],
            'current_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'requested_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                'different:current_name',
            ],
            'reason' => [
                'sometimes',
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.regex' => 'Full name must contain only alphabetic characters and spaces.',
            'national_id.regex' => 'National ID must be exactly 8 characters with uppercase letters and numbers only.',
            'requested_name.different' => 'Requested name must be different from current name.',
        ];
    }
}
