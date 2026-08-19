<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;
use App\Models\DecisionValue;
use Illuminate\Validation\Rule;

class StoreVettingRequest extends FormRequest
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

        // Police vetting permission
        if ($this->user()?->hasActivePermission('conduct police vetting')) {
            return $application->assigned_police_officer_id === $this->user()->id;
        }

        // NIS vetting permission
        if ($this->user()?->hasActivePermission('conduct nis vetting')) {
            return $application->assigned_nis_officer_id === $this->user()->id;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rejectRecommendationId = DecisionValue::where('code', 'reject')->value('id');

        return [
            'return_reason' => [
                Rule::requiredIf(fn () => $rejectRecommendationId && (int) $this->input('recommendation_id') === (int) $rejectRecommendationId),
                'nullable',
                'string',
                'max:5000',
            ],
            'recommendation_id' => 'required|integer|exists:decision_values,id',
            'vetting_date' => 'nullable|date',
            'document' => 'nullable|file|max:51200|mimes:pdf,jpg,jpeg,png',
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
