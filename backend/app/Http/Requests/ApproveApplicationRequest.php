<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;

class ApproveApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasActiveRole('opc_approver') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'conditions' => 'nullable|string|max:5000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $applicationId = $this->route('id');
            $application = Application::with(['policeVetting', 'nisVetting'])->find($applicationId);

            if (!$application) {
                $validator->errors()->add('application', 'Application not found.');
                return;
            }

            // Check if both vetting are completed
            $policeCompleted = $application->policeVetting && 
                $application->policeVetting->status->code === 'completed';
            $nisCompleted = $application->nisVetting && 
                $application->nisVetting->status->code === 'completed';

            if (!$policeCompleted) {
                $validator->errors()->add('application', 'Police vetting must be completed before approval.');
            }

            if (!$nisCompleted) {
                $validator->errors()->add('application', 'NIS vetting must be completed before approval.');
            }

            // Check if application is in pending_approval status
            $pendingApprovalStatus = \App\Models\ApplicationStatus::where('code', 'pending_approval')->first();
            if ($application->status_id !== $pendingApprovalStatus?->id) {
                $validator->errors()->add('application', 'Application must be in pending approval status.');
            }
        });
    }
}
