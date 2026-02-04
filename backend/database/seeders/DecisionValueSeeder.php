<?php

namespace Database\Seeders;

use App\Models\DecisionValue;
use Illuminate\Database\Seeder;

class DecisionValueSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            ['name' => 'Approved', 'code' => 'approved', 'description' => 'Application or vetting has been approved'],
            ['name' => 'Denied', 'code' => 'denied', 'description' => 'Application or vetting has been denied'],
            ['name' => 'Conditional', 'code' => 'conditional', 'description' => 'Approval with conditions'],
            ['name' => 'Approve', 'code' => 'approve', 'description' => 'Recommendation to approve'],
            ['name' => 'Reject', 'code' => 'reject', 'description' => 'Recommendation to reject'],
        ];

        foreach ($values as $value) {
            DecisionValue::firstOrCreate(
                ['code' => $value['code']],
                array_merge($value, ['is_active' => true])
            );
        }
    }
}
