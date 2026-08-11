<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;
use App\Models\ApplicationStatus;

class UpdateApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $applicationId = $this->route('id');
        if (!$applicationId) {
            return false;
        }

        $application = Application::find($applicationId);
        if (!$application) {
            return false;
        }

        $user = $this->user();
        if (!$user) {
            return false;
        }

        $application->loadMissing('status');
        $pendingStatus = ApplicationStatus::where('code', 'pending')->first();
        $returnedStatus = ApplicationStatus::where('code', 'returned_to_data_entry')->first();
        $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->first();
        $isAdminReviewAfterApproverReturn = $user->hasRole('admin')
            && (int) $application->status_id === (int) ($opcReviewStatus?->id)
            && !empty($application->approver_send_back_reason);

        if ($isAdminReviewAfterApproverReturn) {
            return true;
        }

        // Assigned applications are locked from direct editing.
        if ($application->assigned_police_officer_id || $application->assigned_nis_officer_id) {
            return false;
        }

        // Data entry can edit their own pending application.
        if (
            $user->hasPermissionTo('create applications')
            && in_array($application->status_id, [$pendingStatus?->id, $returnedStatus?->id], true)
            && (int) $application->created_by === (int) $user->id
        ) {
            return true;
        }

        // Admin can edit an unassigned pending application for minor corrections.
        // Admin can also edit an OPC review file after the approver has sent it back.
        if (
            $user->hasRole('admin')
            && $application->status_id === $pendingStatus?->id
        ) {
            return true;
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
            'district' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'traditional_authority' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'village' => [
                'sometimes',
                'required',
                'string',
                'max:255',
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
            'district.max' => 'District must not exceed 255 characters.',
            'traditional_authority.max' => 'T/A must not exceed 255 characters.',
            'village.max' => 'Village must not exceed 255 characters.',
            'requested_name.different' => 'Requested name must be different from current name.',
        ];
    }
}
