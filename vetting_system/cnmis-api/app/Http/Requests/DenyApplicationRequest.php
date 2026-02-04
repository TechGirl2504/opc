<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;

class DenyApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('deny applications') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'denial_reason' => 'required|string|min:10|max:5000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'denial_reason.required' => 'Denial reason is required.',
            'denial_reason.min' => 'Denial reason must be at least 10 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $applicationId = $this->route('id');
            $application = Application::find($applicationId);

            if (!$application) {
                $validator->errors()->add('application', 'Application not found.');
                return;
            }

            // Check if application is in pending_approval status
            $pendingApprovalStatus = \App\Models\ApplicationStatus::where('code', 'pending_approval')->first();
            if ($application->status_id !== $pendingApprovalStatus?->id) {
                $validator->errors()->add('application', 'Application must be in pending approval status.');
            }
        });
    }
}
