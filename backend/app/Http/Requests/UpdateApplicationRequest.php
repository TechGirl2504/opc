<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\NameChangeReason;
use Illuminate\Validation\Rule;

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
        $draftStatus = ApplicationStatus::where('code', 'draft')->first();
        $handoffStatus = ApplicationStatus::where('code', 'handoff_to_admin')->first();
        $returnedStatus = ApplicationStatus::where('code', 'returned_to_data_entry')->first();
        $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->first();
        $isAdminReviewAfterApproverReturn = $user->hasActiveRole('admin')
            && (int) $application->status_id === (int) ($opcReviewStatus?->id)
            && !empty($application->approver_send_back_reason);

        if ($isAdminReviewAfterApproverReturn) {
            return true;
        }

        // Assigned applications are locked from direct editing.
        if ($application->assigned_police_officer_id || $application->assigned_nis_officer_id) {
            return false;
        }

        // Data entry can edit their own draft or handoff record.
        if (
            $user->hasActivePermission('create applications')
            && in_array($application->status_id, [$draftStatus?->id, $handoffStatus?->id, $returnedStatus?->id], true)
            && (int) $application->created_by === (int) $user->id
        ) {
            return true;
        }

        // Admin can edit an unassigned handoff application for minor corrections.
        // Admin can also edit an OPC review file after the approver has sent it back.
        if (
            $user->hasActiveRole('admin')
            && in_array($application->status_id, [$handoffStatus?->id], true)
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
                'sometimes',
                'required',
                'string',
                'regex:/^[A-Z0-9]{8}$/',
                Rule::unique('applications', 'national_id')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at'),
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:25',
                'regex:/^[0-9+\-\s()]+$/',
            ],
            'email' => [
                'nullable',
                'string',
                'max:255',
                'email',
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
            'requested_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                'different:full_name',
            ],
            'reason_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('name_change_reasons', 'id'),
            ],
            'reason' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::in($this->allowedReasonNames()),
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
            'national_id.required' => 'National ID is required.',
            'national_id.unique' => 'This National ID already exists. Use a different National ID.',
            'date_of_birth.before' => 'Date of birth must be a past date.',
            'phone_number.regex' => 'Phone number may contain only digits, spaces, plus signs, hyphens, and parentheses.',
            'email.email' => 'Email address must be a valid email.',
            'email.max' => 'Email address must not exceed 255 characters.',
            'district.max' => 'District must not exceed 255 characters.',
            'traditional_authority.max' => 'T/A must not exceed 255 characters.',
            'village.max' => 'Village must not exceed 255 characters.',
            'requested_name.different' => 'Requested name must be different from current full name.',
            'reason_id.integer' => 'Please select a valid reason for change.',
            'reason_id.exists' => 'Please select a valid reason for change.',
            'reason.in' => 'Select a valid reason for change.',
        ];
    }

    /**
     * Get the active managed reasons plus the application's current value for compatibility.
     */
    protected function allowedReasonNames(): array
    {
        $allowed = NameChangeReason::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $applicationId = $this->route('id');
        if ($applicationId) {
            $application = Application::find($applicationId);
            if ($application?->reason) {
                $allowed[] = $application->reason;
            }
        }

        return array_values(array_unique($allowed));
    }
}
