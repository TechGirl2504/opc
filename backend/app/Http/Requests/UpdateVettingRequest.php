<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;
use App\Models\DecisionValue;
use App\Models\VettingRecord;
use Illuminate\Validation\Rule;

class UpdateVettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $applicationId = $this->route('id');
        $application = Application::find($applicationId);
        
        if (!$application) {
            return false;
        }

        // Get the vetting record
        $vettingRecord = null;
        if ($this->user()?->hasPermissionTo('conduct police vetting')) {
            if ($application->assigned_police_officer_id !== $this->user()->id) {
                return false;
            }
            $vettingRecord = $application->policeVetting;
        } elseif ($this->user()?->hasPermissionTo('conduct nis vetting')) {
            if ($application->assigned_nis_officer_id !== $this->user()->id) {
                return false;
            }
            $vettingRecord = $application->nisVetting;
        } else {
            return false;
        }

        // If no vetting record exists, allow creation (will be handled by submit)
        if (!$vettingRecord) {
            return true;
        }

        // Allow update only if:
        // 1. Vetting is not completed (pending, in_progress), OR
        // 2. Vetting is sent_back (for clarifications)
        $statusCode = $vettingRecord->status->code ?? null;
        if ($statusCode === 'completed') {
            return false; // Cannot edit completed vetting
        }
        
        // Allow editing if pending, in_progress, or sent_back
        return in_array($statusCode, ['pending', 'in_progress', 'sent_back']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rejectRecommendationId = DecisionValue::where('code', 'reject')->value('id');

        return [
            'return_reason' => [
                'sometimes',
                Rule::requiredIf(fn () => $rejectRecommendationId && (int) $this->input('recommendation_id') === (int) $rejectRecommendationId),
                'nullable',
                'string',
                'max:5000',
            ],
            'recommendation_id' => 'sometimes|required|integer|exists:decision_values,id',
            'vetting_date' => 'sometimes|nullable|date',
            'document' => 'sometimes|nullable|file|max:51200|mimes:pdf,jpg,jpeg,png',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document.max' => 'Vetting report must not exceed 50MB.',
            'document.mimes' => 'Vetting report must be PDF, JPG, JPEG, or PNG file.',
            'recommendation_id.exists' => 'Selected recommendation is invalid.',
            'recommendation_id.required' => 'Please select a recommendation.',
            'return_reason.required' => 'Please provide a rejection reason.',
            'return_reason.max' => 'Rejection reason must not exceed 5000 characters.',
        ];
    }
}
