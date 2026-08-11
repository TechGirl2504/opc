<?php

namespace Database\Seeders;

use App\Models\ApplicationStatus;
use Illuminate\Database\Seeder;

class ApplicationStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Pending', 'code' => 'pending', 'order' => 1, 'description' => 'Application is pending initial review'],
            ['name' => 'Returned to Data Entry', 'code' => 'returned_to_data_entry', 'order' => 2, 'description' => 'Application has been returned to data entry for correction'],
            ['name' => 'Police Vetting', 'code' => 'police_vetting', 'order' => 3, 'description' => 'Application is being vetted by Police'],
            ['name' => 'Police Completed', 'code' => 'police_completed', 'order' => 4, 'description' => 'Police vetting has been completed'],
            ['name' => 'OPC Review', 'code' => 'opc_review', 'order' => 5, 'description' => 'OPC is reviewing the application'],
            ['name' => 'NIS Vetting', 'code' => 'nis_vetting', 'order' => 6, 'description' => 'Application is being vetted by NIS'],
            ['name' => 'NIS Completed', 'code' => 'nis_completed', 'order' => 7, 'description' => 'NIS vetting has been completed'],
            ['name' => 'Pending Approval', 'code' => 'pending_approval', 'order' => 8, 'description' => 'Application is pending final approval'],
            ['name' => 'Approved', 'code' => 'approved', 'order' => 9, 'description' => 'Application has been approved'],
            ['name' => 'Denied', 'code' => 'denied', 'order' => 10, 'description' => 'Application has been denied'],
            ['name' => 'Archived', 'code' => 'archived', 'order' => 11, 'description' => 'Application has been archived'],
        ];

        foreach ($statuses as $status) {
            ApplicationStatus::updateOrCreate(
                ['code' => $status['code']],
                array_merge($status, ['is_active' => true])
            );
        }
    }
}
