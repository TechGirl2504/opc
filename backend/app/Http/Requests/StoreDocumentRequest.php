<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;
use App\Models\DocumentType;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $applicationId = $this->route('id');
        $application = Application::find($applicationId);
        
        // User must have access to the application
        if (!$application) {
            return false;
        }

        // Permission check is enforced on the route; keep request authorization permission-based too.
        return $this->user()?->hasPermissionTo('upload documents') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $documentTypeId = $this->input('document_type_id');
        $documentType = $documentTypeId ? DocumentType::find($documentTypeId) : null;

        $maxSize = $documentType ? $documentType->max_file_size / 1024 : 10240; // Convert bytes to KB
        $allowedMimes = $documentType && $documentType->allowed_mime_types 
            ? $documentType->allowed_mime_types 
            : ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

        return [
            'document_type_id' => 'required|integer|exists:document_types,id',
            'file' => [
                'required',
                'file',
                'max:' . $maxSize,
                function ($attribute, $value, $fail) use ($allowedMimes) {
                    if ($value && !in_array($value->getMimeType(), $allowedMimes)) {
                        $fail('The file type is not allowed. Allowed types: ' . implode(', ', $allowedMimes));
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document_type_id.required' => 'Document type is required.',
            'document_type_id.exists' => 'Selected document type is invalid.',
            'file.required' => 'File is required.',
            'file.max' => 'File size exceeds the maximum allowed size.',
        ];
    }
}
