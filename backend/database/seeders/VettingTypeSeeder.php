<?php

namespace Database\Seeders;

use App\Models\VettingType;
use Illuminate\Database\Seeder;

class VettingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Police Vetting',
                'code' => 'police',
                'description' => 'Vetting conducted by Malawi Police Service',
                'is_active' => true,
            ],
            [
                'name' => 'NIS Vetting',
                'code' => 'nis',
                'description' => 'Vetting conducted by National Intelligence Service',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            VettingType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
