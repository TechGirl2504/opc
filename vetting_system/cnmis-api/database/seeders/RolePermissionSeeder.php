<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Application permissions
            'create applications',
            'view applications',
            'view all applications',
            'edit applications',
            'delete applications',
            'assign applications',

            // Vetting permissions
            'conduct police vetting',
            'conduct nis vetting',
            'view vetting records',

            // Document permissions
            'upload documents',
            'view documents',
            'delete documents',
            'download documents',

            // Decision permissions
            'approve applications',
            'deny applications',
            'view decisions',

            // User management permissions
            'manage users',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'activate users',
            'deactivate users',

            // Role & Permission management
            'manage roles',
            'manage permissions',

            // Configuration management
            'manage institutions',
            'manage application statuses',
            'manage vetting types',
            'manage document types',

            // Reports
            'view reports',
            'export reports',
            'view audit logs',

            // Workflow / admin actions
            'send back vetting',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create roles and assign permissions (authoritative)
        // NOTE: We use syncPermissions so old/stale permissions are REMOVED.
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        $opcDataEntryRole = Role::firstOrCreate([
            'name' => 'opc_data_entry',
            'guard_name' => 'web',
        ]);
        $opcDataEntryPerms = [
            'create applications',
            'view applications',
            'view all applications',
            'edit applications',
            'upload documents',
            'view documents',
            'assign applications',
        ];
        $opcDataEntryRole->syncPermissions(
            Permission::where('guard_name', 'web')->whereIn('name', $opcDataEntryPerms)->get()
        );

        $opcApproverRole = Role::firstOrCreate([
            'name' => 'opc_approver',
            'guard_name' => 'web',
        ]);
        $opcApproverPerms = [
            'view applications',
            'view all applications',
            'approve applications',
            'deny applications',
            'view decisions',
            'view vetting records',
            'view documents',
            'view reports',
            'send back vetting',
        ];
        $opcApproverRole->syncPermissions(
            Permission::where('guard_name', 'web')->whereIn('name', $opcApproverPerms)->get()
        );

        $policeOfficerRole = Role::firstOrCreate([
            'name' => 'police_officer',
            'guard_name' => 'web',
        ]);
        $policePerms = [
            'view applications',
            'conduct police vetting',
            'view vetting records',
            'upload documents',
            'view documents',
        ];
        $policeOfficerRole->syncPermissions(
            Permission::where('guard_name', 'web')->whereIn('name', $policePerms)->get()
        );

        $nisOfficerRole = Role::firstOrCreate([
            'name' => 'nis_officer',
            'guard_name' => 'web',
        ]);
        $nisPerms = [
            'view applications',
            'conduct nis vetting',
            'view vetting records',
            'upload documents',
            'view documents',
        ];
        $nisOfficerRole->syncPermissions(
            Permission::where('guard_name', 'web')->whereIn('name', $nisPerms)->get()
        );
    }
}
