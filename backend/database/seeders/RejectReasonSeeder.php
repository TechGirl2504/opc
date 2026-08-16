<?php

namespace Database\Seeders;

use App\Models\RejectReason;
use Illuminate\Database\Seeder;

class RejectReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['name' => 'Parents objecting', 'code' => 'parents_objecting', 'description' => 'The applicant family is objecting to the change request', 'order' => 1],
            ['name' => 'Village not known', 'code' => 'village_not_known', 'description' => 'The applicant details cannot be confirmed locally', 'order' => 2],
            ['name' => 'Crime suspect', 'code' => 'crime_suspect', 'description' => 'The applicant is linked to an active criminal matter', 'order' => 3],
            ['name' => 'Documentation incomplete', 'code' => 'documentation_incomplete', 'description' => 'Mandatory supporting documents are missing', 'order' => 4],
            ['name' => 'Identity mismatch', 'code' => 'identity_mismatch', 'description' => 'The submitted identity details do not align with records', 'order' => 5],
        ];

        foreach ($reasons as $reason) {
            RejectReason::updateOrCreate(
                ['code' => $reason['code']],
                $reason + ['is_active' => true]
            );
        }
    }
}
