<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $requestUser = $request->user();
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'institution' => $this->whenLoaded('institution', function () {
                return [
                    'id' => $this->institution->id,
                    'name' => $this->institution->name,
                    'code' => $this->institution->code,
                ];
            }),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                });
            }),
            'permissions' => $this->when(
                // only include when requester is allowed to manage users/roles, or requesting own user
                ($requestUser?->hasPermissionTo('manage users') ?? false)
                    || ($requestUser?->hasPermissionTo('manage roles') ?? false)
                    || ($requestUser?->id === $this->id),
                function () {
                return $this->getAllPermissions()->pluck('name');
            }),
            'profile_picture' => $this->profile_picture,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
