<?php

namespace Database\Seeders;

use App\Models\VettingStatus;
use Illuminate\Database\Seeder;

class VettingStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Pending', 'code' => 'pending', 'description' => 'Vetting has not started'],
            ['name' => 'In Progress', 'code' => 'in_progress', 'description' => 'Vetting is currently in progress'],
            ['name' => 'Completed', 'code' => 'completed', 'description' => 'Vetting has been completed'],
            ['name' => 'Rejected', 'code' => 'rejected', 'description' => 'Vetting has been rejected'],
            ['name' => 'Sent Back', 'code' => 'sent_back', 'description' => 'Vetting has been sent back for clarifications'],
        ];

        foreach ($statuses as $status) {
            VettingStatus::firstOrCreate(
                ['code' => $status['code']],
                array_merge($status, ['is_active' => true])
            );
        }
    }
}
