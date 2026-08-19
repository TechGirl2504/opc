<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('id');
        
        return [
            'username' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name'),
            ],
            'roles' => [
                'sometimes',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name'),
            ],
            'institution_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:institutions,id',
            ],
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $roles = $this->input('roles');
            $institutionId = $this->input('institution_id');
            $userId = $this->route('id');

            if ($roles !== null || $this->filled('role') || $institutionId) {
                $user = User::find($userId);
                if (!$user) {
                    return;
                }

                $finalRoles = $roles ?? $user->roles->pluck('name')->all();
                if ($this->filled('role')) {
                    $finalRoles[] = $this->input('role');
                }
                $finalInstitutionId = $institutionId ?? $user->institution_id;

                if (empty($finalRoles)) {
                    $validator->errors()->add('roles', 'At least one role must be selected.');
                } elseif ($finalInstitutionId) {
                    $institution = \App\Models\Institution::find($finalInstitutionId);
                    
                    if ($institution) {
                        $validRoles = $this->getValidRolesForInstitution($institution->code);
                        foreach (array_unique($finalRoles) as $role) {
                            if (!in_array($role, $validRoles, true)) {
                                $validator->errors()->add('roles', "Role '{$role}' is not valid for institution '{$institution->name}'");
                            }
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
