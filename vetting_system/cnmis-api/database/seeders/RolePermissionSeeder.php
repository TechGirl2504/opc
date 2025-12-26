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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $opcDataEntryRole = Role::firstOrCreate(['name' => 'opc_data_entry']);
        $opcDataEntryRole->givePermissionTo([
            'create applications',
            'view applications',
            'edit applications',
            'upload documents',
            'view documents',
            'assign applications',
        ]);

        $opcApproverRole = Role::firstOrCreate(['name' => 'opc_approver']);
        $opcApproverRole->givePermissionTo([
            'view applications',
            'approve applications',
            'deny applications',
            'view decisions',
            'view vetting records',
            'view documents',
            'view reports',
        ]);

        $policeOfficerRole = Role::firstOrCreate(['name' => 'police_officer']);
        $policeOfficerRole->givePermissionTo([
            'view applications',
            'conduct police vetting',
            'view vetting records',
            'upload documents',
            'view documents',
        ]);

        $nisOfficerRole = Role::firstOrCreate(['name' => 'nis_officer']);
        $nisOfficerRole->givePermissionTo([
            'view applications',
            'conduct nis vetting',
            'view vetting records',
            'upload documents',
            'view documents',
        ]);
    }
}
