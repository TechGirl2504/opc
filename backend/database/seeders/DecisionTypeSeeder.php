<?php

namespace Database\Seeders;

use App\Models\DecisionType;
use Illuminate\Database\Seeder;

class DecisionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Police Vetting Decision',
                'code' => 'police_vetting',
                'description' => 'Decision made during police vetting',
                'is_active' => true,
            ],
            [
                'name' => 'NIS Vetting Decision',
                'code' => 'nis_vetting',
                'description' => 'Decision made during NIS vetting',
                'is_active' => true,
            ],
            [
                'name' => 'Final Approval Decision',
                'code' => 'final_approval',
                'description' => 'Final approval or rejection decision by OPC',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            DecisionType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
