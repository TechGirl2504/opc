<?php

namespace App\Http\Requests;

use App\Models\NameChangeReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasActivePermission('create applications') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/',
            ],
            'national_id' => [
                'required',
                'string',
                'regex:/^[A-Z0-9]{8}$/',
                'unique:applications,national_id',
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
                'required',
                'string',
                'max:255',
            ],
            'traditional_authority' => [
                'required',
                'string',
                'max:255',
            ],
            'village' => [
                'required',
                'string',
                'max:255',
            ],
            'requested_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'different:full_name',
            ],
            'reason_id' => [
                'nullable',
                'integer',
                Rule::exists('name_change_reasons', 'id'),
            ],
            'reason' => [
                'required_without:reason_id',
                'nullable',
                'string',
                'max:255',
                Rule::in($this->activeReasonNames()),
            ],
            'documents' => [
                'nullable',
                'array',
            ],
            'documents.*' => [
                'file',
                'max:51200', // 50MB in kilobytes
                'mimes:pdf,jpg,jpeg,png',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Full name is required.',
            'full_name.min' => 'Full name must be at least 2 characters.',
            'full_name.max' => 'Full name must not exceed 255 characters.',
            'full_name.regex' => 'Full name must contain only alphabetic characters and spaces.',
            'national_id.regex' => 'National ID must be exactly 8 characters with uppercase letters and numbers only (e.g., ABC12345).',
            'national_id.required' => 'National ID is required.',
            'national_id.unique' => 'This National ID already exists. Use a different National ID.',
            'date_of_birth.before' => 'Date of birth must be a past date.',
            'phone_number.regex' => 'Phone number may contain only digits, spaces, plus signs, hyphens, and parentheses.',
            'email.email' => 'Email address must be a valid email.',
            'email.max' => 'Email address must not exceed 255 characters.',
            'district.required' => 'District is required.',
            'district.max' => 'District must not exceed 255 characters.',
            'traditional_authority.required' => 'T/A is required.',
            'traditional_authority.max' => 'T/A must not exceed 255 characters.',
            'village.required' => 'Village is required.',
            'village.max' => 'Village must not exceed 255 characters.',
            'requested_name.required' => 'Requested name is required.',
            'requested_name.min' => 'Requested name must be at least 2 characters.',
            'requested_name.max' => 'Requested name must not exceed 255 characters.',
            'requested_name.different' => 'Requested name must be different from current full name.',
            'reason_id.integer' => 'Please select a valid reason for change.',
            'reason_id.exists' => 'Please select a valid reason for change.',
            'reason.required' => 'Reason for change is required.',
            'reason.in' => 'Select a valid reason for change.',
            'reason.max' => 'Reason must not exceed 255 characters.',
            'documents.*.max' => 'Each document must not exceed 50MB.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, JPEG, or PNG files.',
        ];
    }

    /**
     * Get the active managed reason labels.
     */
    protected function activeReasonNames(): array
    {
        return NameChangeReason::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
