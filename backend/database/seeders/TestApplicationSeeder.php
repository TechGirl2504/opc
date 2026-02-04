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
        $pendingStatus = ApplicationStatus::where('code', 'pending')->first();
        $policeVettingStatus = ApplicationStatus::where('code', 'police_vetting')->first();
        $nisVettingStatus = ApplicationStatus::where('code', 'nis_vetting')->first();

        if (!$opcDataEntry || !$pendingStatus) {
            $this->command->warn('Required users or statuses not found. Run TestUserSeeder first.');
            return;
        }

        // Create sample applications with different statuses and assignments
        $applications = [
            [
                'full_name' => 'John Doe',
                'national_id' => '12345678',
                'current_name' => 'John Smith',
                'requested_name' => 'John Doe',
                'reason' => 'Name change due to marriage. I want to use my spouse\'s surname.',
                'status_id' => $pendingStatus->id,
                'created_by' => $opcDataEntry->id,
                'submitted_at' => now()->subDays(5),
            ],
            [
                'full_name' => 'Jane Smith',
                'national_id' => '87654321',
                'current_name' => 'Jane Williams',
                'requested_name' => 'Jane Smith',
                'reason' => 'Legal name change for professional purposes. I need to use my professional name consistently.',
                'status_id' => $policeVettingStatus ? $policeVettingStatus->id : $pendingStatus->id,
                'created_by' => $opcDataEntry->id,
                'assigned_police_officer_id' => $policeOfficer?->id,
                'submitted_at' => now()->subDays(3),
            ],
            [
                'full_name' => 'Michael Johnson',
                'national_id' => '11223344',
                'current_name' => null,
                'requested_name' => 'Michael Johnson',
                'reason' => 'First-time name registration. I need to register my name officially.',
                'status_id' => $nisVettingStatus ? $nisVettingStatus->id : $pendingStatus->id,
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
