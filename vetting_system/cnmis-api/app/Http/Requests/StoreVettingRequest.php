<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;

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

        // Police officers can only submit police vetting
        if ($this->user()->hasRole('police_officer')) {
            return $application->assigned_police_officer_id === $this->user()->id;
        }

        // NIS officers can only submit NIS vetting
        if ($this->user()->hasRole('nis_officer')) {
            return $application->assigned_nis_officer_id === $this->user()->id;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'remarks' => 'nullable|string|max:5000',
            'findings' => 'nullable|string|max:5000',
            'recommendation_id' => 'nullable|integer|exists:decision_values,id',
            'vetting_date' => 'nullable|date',
            'document' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document.max' => 'Vetting report must not exceed 10MB.',
            'document.mimes' => 'Vetting report must be PDF, JPG, JPEG, or PNG file.',
            'recommendation_id.exists' => 'Selected recommendation is invalid.',
        ];
    }
}
