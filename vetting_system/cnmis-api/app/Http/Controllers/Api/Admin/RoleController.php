<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Role "name" is treated as an internal immutable code (used by middleware/hasRole()).
     * Use display_name for human-friendly labels.
     */
    private const PROTECTED_ROLE_CODES = [
        'admin',
        'opc_data_entry',
        'opc_approver',
        'police_officer',
        'nis_officer',
    ];

    public function index()
    {
        // Get roles with web guard
        $roles = Role::where('guard_name', 'web')->with('permissions')->get();
        
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // keep as a stable slug/code
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles')->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
            'display_name' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
        ], [
            'permissions.*.exists' => 'The selected permission does not exist.',
        ]);
        
        // Additional validation: ensure all permissions have web guard
        if (isset($validated['permissions']) && !empty($validated['permissions'])) {
            $invalidPermissions = Permission::whereIn('name', $validated['permissions'])
                ->where('guard_name', '!=', 'web')
                ->pluck('name')
                ->toArray();
            
            if (!empty($invalidPermissions)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Some permissions do not exist or are not for the web guard.',
                        'invalid_permissions' => $invalidPermissions
                    ]
                ], 422);
            }
        }

        // Create role with web guard
        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? null,
            'guard_name' => 'web',
        ]);
        
        if (isset($validated['permissions']) && !empty($validated['permissions'])) {
            // Filter out any invalid permissions - ensure we get permissions with web guard
            $validPermissions = Permission::where('guard_name', 'web')
                ->whereIn('name', $validated['permissions'])
                ->get();
            
            if ($validPermissions->isNotEmpty()) {
                // Sync permissions using the Permission models directly
                $role->syncPermissions($validPermissions);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $role->load('permissions'),
            'message' => 'Role created successfully'
        ], 201);
    }

    public function show($id)
    {
        $role = Role::where('guard_name', 'web')->with('permissions')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    public function update(Request $request, $id)
    {
        // Ensure we get the role with web guard
        $role = Role::where('guard_name', 'web')->findOrFail($id);

        $validated = $request->validate([
            // Role code should not be renamed once created (prevents breaking middleware checks)
            'name' => ['sometimes', 'string', 'max:255'],
            'display_name' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
        ], [
            'permissions.*.exists' => 'The selected permission does not exist.',
        ]);
        
        // Additional validation: ensure all permissions have web guard
        if (isset($validated['permissions']) && !empty($validated['permissions'])) {
            $invalidPermissions = Permission::whereIn('name', $validated['permissions'])
                ->where('guard_name', '!=', 'web')
                ->pluck('name')
                ->toArray();
            
            if (!empty($invalidPermissions)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Some permissions do not exist or are not for the web guard.',
                        'invalid_permissions' => $invalidPermissions
                    ]
                ], 422);
            }
        }

        // Prevent renaming role code (especially for system roles)
        if (array_key_exists('name', $validated) && $validated['name'] !== $role->name) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Role code cannot be changed. Use display name for labeling.',
                ],
            ], 422);
        }

        // Allow updating display_name
        if (array_key_exists('display_name', $validated)) {
            $role->update([
                'display_name' => $validated['display_name'],
            ]);
        }
        
        if (isset($validated['permissions'])) {
            // Filter out any invalid permissions - ensure we get permissions with web guard
            if (!empty($validated['permissions'])) {
                $validPermissions = Permission::where('guard_name', 'web')
                    ->whereIn('name', $validated['permissions'])
                    ->get();
                
                // Sync permissions using the Permission models directly
                $role->syncPermissions($validPermissions);
            } else {
                // If empty array, remove all permissions
                $role->syncPermissions([]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $role->load('permissions'),
            'message' => 'Role updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $role = Role::where('guard_name', 'web')->findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLE_CODES, true)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'This system role cannot be deleted.',
                ],
            ], 403);
        }
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }

    public function permissions()
    {
        // Get permissions with web guard
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }
}
