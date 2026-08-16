<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $opcDataEntry = User::where('username', 'opc_data_entry')->first();
        $policeOfficer = User::where('username', 'police_officer')->first();
        $nisOfficer = User::where('username', 'nis_officer')->first();
        $handoffStatus = ApplicationStatus::where('code', 'handoff_to_admin')->first();
        $policeVettingStatus = ApplicationStatus::where('code', 'police_vetting')->first();
        $nisVettingStatus = ApplicationStatus::where('code', 'nis_vetting')->first();

        if (!$opcDataEntry || !$handoffStatus) {
            $this->command->warn('Required users or statuses not found. Run TestUserSeeder first.');
            return;
        }

        // Create sample applications with different statuses and assignments
        $applications = [
            [
                'full_name' => 'John Doe',
                'national_id' => '12345678',
                'date_of_birth' => '1990-01-15',
                'phone_number' => '0999000001',
                'requested_name' => 'John Doe',
                'reason' => 'To match with bank details',
                'status_id' => $handoffStatus->id,
                'created_by' => $opcDataEntry->id,
                'submitted_at' => now()->subDays(5),
            ],
            [
                'full_name' => 'Jane Smith',
                'national_id' => '87654321',
                'date_of_birth' => '1988-06-22',
                'phone_number' => '0999000002',
                'requested_name' => 'Jane Smith',
                'reason' => 'To match with academic certificates',
                'status_id' => $policeVettingStatus ? $policeVettingStatus->id : $handoffStatus->id,
                'created_by' => $opcDataEntry->id,
                'assigned_police_officer_id' => $policeOfficer?->id,
                'submitted_at' => now()->subDays(3),
            ],
            [
                'full_name' => 'Michael Johnson',
                'national_id' => '11223344',
                'date_of_birth' => '1995-11-03',
                'phone_number' => '0999000003',
                'requested_name' => 'Michael Johnson',
                'reason' => 'To match with clan name',
                'status_id' => $nisVettingStatus ? $nisVettingStatus->id : $handoffStatus->id,
                'created_by' => $opcDataEntry->id,
                'assigned_nis_officer_id' => $nisOfficer?->id,
                'submitted_at' => now()->subDays(1),
            ],
        ];

        foreach ($applications as $applicationData) {
            // Create application instance
            $application = new Application($applicationData);
            // Explicitly generate and set application number
            $application->application_number = $application->generateApplicationNumber();
            // Save the application
            $application->save();
        }

        $this->command->info('Test applications created successfully!');
        $this->command->info('Some applications have been assigned to police and NIS officers for testing.');
    }
}
