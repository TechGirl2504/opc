<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Institution;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $opcInstitution = Institution::where('code', 'OPC')->first();
        $policeInstitution = Institution::where('code', 'POLICE')->first();
        $nisInstitution = Institution::where('code', 'NIS')->first();

        // Test Admin User
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@cnmis.test',
                'password' => Hash::make('Admin@123'),
                'institution_id' => $opcInstitution->id,
                'is_active' => true,
            ]
        );
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        // Test OPC Data Entry User
        $opcDataEntry = User::firstOrCreate(
            ['username' => 'opc_data_entry'],
            [
                'email' => 'opc_data_entry@cnmis.test',
                'password' => Hash::make('DataEntry@123'),
                'institution_id' => $opcInstitution->id,
                'is_active' => true,
            ]
        );
        $opcDataEntryRole = Role::where('name', 'opc_data_entry')->first();
        if ($opcDataEntryRole && !$opcDataEntry->hasRole('opc_data_entry')) {
            $opcDataEntry->assignRole($opcDataEntryRole);
        }

        // Test OPC Approver User
        $opcApprover = User::firstOrCreate(
            ['username' => 'opc_approver'],
            [
                'email' => 'opc_approver@cnmis.test',
                'password' => Hash::make('Approver@123'),
                'institution_id' => $opcInstitution->id,
                'is_active' => true,
            ]
        );
        $opcApproverRole = Role::where('name', 'opc_approver')->first();
        if ($opcApproverRole && !$opcApprover->hasRole('opc_approver')) {
            $opcApprover->assignRole($opcApproverRole);
        }

        // Test Police Officer
        $policeOfficer = User::firstOrCreate(
            ['username' => 'police_officer'],
            [
                'email' => 'police@cnmis.test',
                'password' => Hash::make('Police@123'),
                'institution_id' => $policeInstitution->id,
                'is_active' => true,
            ]
        );
        $policeRole = Role::where('name', 'police_officer')->first();
        if ($policeRole && !$policeOfficer->hasRole('police_officer')) {
            $policeOfficer->assignRole($policeRole);
        }

        // Test NIS Officer
        $nisOfficer = User::firstOrCreate(
            ['username' => 'nis_officer'],
            [
                'email' => 'nis@cnmis.test',
                'password' => Hash::make('Nis@123'),
                'institution_id' => $nisInstitution->id,
                'is_active' => true,
            ]
        );
        $nisRole = Role::where('name', 'nis_officer')->first();
        if ($nisRole && !$nisOfficer->hasRole('nis_officer')) {
            $nisOfficer->assignRole($nisRole);
        }

        $this->command->info('Test users created successfully!');
        $this->command->info('Admin: admin / Admin@123');
        $this->command->info('OPC Data Entry: opc_data_entry / DataEntry@123');
        $this->command->info('OPC Approver: opc_approver / Approver@123');
        $this->command->info('Police Officer: police_officer / Police@123');
        $this->command->info('NIS Officer: nis_officer / Nis@123');
    }
}
