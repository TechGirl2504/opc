<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            [
                'name' => 'Office of the President and Cabinet',
                'code' => 'OPC',
                'description' => 'Office of the President and Cabinet',
                'is_active' => true,
            ],
            [
                'name' => 'Malawi Police Service',
                'code' => 'POLICE',
                'description' => 'Malawi Police Service',
                'is_active' => true,
            ],
            [
                'name' => 'National Intelligence Service',
                'code' => 'NIS',
                'description' => 'National Intelligence Service',
                'is_active' => true,
            ],
        ];

        foreach ($institutions as $institution) {
            Institution::firstOrCreate(
                ['code' => $institution['code']],
                $institution
            );
        }
    }
}
