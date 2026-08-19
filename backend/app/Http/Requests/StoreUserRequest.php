<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasActivePermission('manage users') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:users,username',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name'),
            ],
            'roles' => [
                'nullable',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name'),
            ],
            'institution_id' => [
                'required',
                'integer',
                'exists:institutions,id',
            ],
            'is_active' => 'boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $roles = $this->input('roles', []);
            if ($this->filled('role')) {
                $roles[] = $this->input('role');
            }
            $institutionId = $this->input('institution_id');

            if (empty($roles) || !$institutionId) {
                if (empty($roles)) {
                    $validator->errors()->add('roles', 'At least one role must be selected.');
                }
                return;
            }

            if ($institutionId) {
                $institution = \App\Models\Institution::find($institutionId);
                
                // Validate role-institution compatibility
                if ($institution) {
                    $validRoles = $this->getValidRolesForInstitution($institution->code);
                    foreach (array_unique($roles) as $role) {
                        if (!in_array($role, $validRoles, true)) {
                            $validator->errors()->add('roles', "Role '{$role}' is not valid for institution '{$institution->name}'");
                        }
                    }
                }
            }
        });
    }

    /**
     * Get valid roles for an institution
     */
    private function getValidRolesForInstitution(string $institutionCode): array
    {
        return match($institutionCode) {
            'OPC' => ['admin', 'opc_data_entry', 'opc_approver'],
            'POLICE' => ['police_officer'],
            'NIS' => ['nis_officer'],
            default => [],
        };
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'Username must contain only alphanumeric characters and underscores.',
            'password.min' => 'Password must be at least 8 characters.',
            'role.exists' => 'Selected role does not exist.',
            'roles.*.exists' => 'One or more selected roles do not exist.',
            'institution_id.exists' => 'Selected institution does not exist.',
        ];
    }
}
