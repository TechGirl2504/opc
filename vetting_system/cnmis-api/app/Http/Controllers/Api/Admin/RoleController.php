<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
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
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
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
        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        
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
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
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

        $role->update(['name' => $validated['name']]);
        
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
        $role = Role::findOrFail($id);
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
