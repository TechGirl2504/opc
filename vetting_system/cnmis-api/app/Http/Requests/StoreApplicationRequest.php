<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'opc_data_entry']);
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
                'required',
                'string',
                'min:2',
                'max:255',
                'different:current_name',
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'documents' => [
                'nullable',
                'array',
                'max:5',
            ],
            'documents.*' => [
                'file',
                'max:10240', // 10MB in kilobytes
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
            'requested_name.required' => 'Requested name is required.',
            'requested_name.min' => 'Requested name must be at least 2 characters.',
            'requested_name.max' => 'Requested name must not exceed 255 characters.',
            'requested_name.different' => 'Requested name must be different from current name.',
            'reason.required' => 'Reason for change is required.',
            'reason.min' => 'Reason must be at least 10 characters.',
            'reason.max' => 'Reason must not exceed 5000 characters.',
            'documents.max' => 'Maximum 5 documents allowed per application.',
            'documents.*.max' => 'Each document must not exceed 10MB.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, JPEG, or PNG files.',
        ];
    }
}
