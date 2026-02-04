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
            ['name' => 'Police Vetting', 'code' => 'police_vetting', 'order' => 2, 'description' => 'Application is being vetted by Police'],
            ['name' => 'Police Completed', 'code' => 'police_completed', 'order' => 3, 'description' => 'Police vetting has been completed'],
            ['name' => 'OPC Review', 'code' => 'opc_review', 'order' => 4, 'description' => 'OPC is reviewing the application'],
            ['name' => 'NIS Vetting', 'code' => 'nis_vetting', 'order' => 5, 'description' => 'Application is being vetted by NIS'],
            ['name' => 'NIS Completed', 'code' => 'nis_completed', 'order' => 6, 'description' => 'NIS vetting has been completed'],
            ['name' => 'Pending Approval', 'code' => 'pending_approval', 'order' => 7, 'description' => 'Application is pending final approval'],
            ['name' => 'Approved', 'code' => 'approved', 'order' => 8, 'description' => 'Application has been approved'],
            ['name' => 'Denied', 'code' => 'denied', 'order' => 9, 'description' => 'Application has been denied'],
            ['name' => 'Archived', 'code' => 'archived', 'order' => 10, 'description' => 'Application has been archived'],
        ];

        foreach ($statuses as $status) {
            ApplicationStatus::firstOrCreate(
                ['code' => $status['code']],
                array_merge($status, ['is_active' => true])
            );
        }
    }
}
