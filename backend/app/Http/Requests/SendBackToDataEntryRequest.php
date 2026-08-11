<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class SendBackToDataEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission(['manage application statuses', 'edit applications']) ?? false;
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
                'max:5000',
            ],
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

            if ($application->status?->code !== 'pending') {
                $validator->errors()->add('application', 'Application must be in pending status.');
            }

            if (
                $application->assigned_police_officer_id
                || $application->assigned_nis_officer_id
                || $application->assigned_opc_approver_id
            ) {
                $validator->errors()->add('application', 'Assigned applications cannot be sent back to data entry.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'reason.max' => 'The reason must not exceed 5000 characters.',
        ];
    }
}
