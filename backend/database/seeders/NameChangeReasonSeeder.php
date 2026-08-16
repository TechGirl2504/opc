<?php

namespace Database\Seeders;

use App\Models\NameChangeReason;
use Illuminate\Database\Seeder;

class NameChangeReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            [
                'name' => 'To match with academic certificates',
                'code' => 'academic_certificates',
                'description' => 'Use when the applicant wants the legal name to match academic records.',
                'order' => 1,
            ],
            [
                'name' => 'To match with clan name',
                'code' => 'clan_name',
                'description' => 'Use when the applicant wants the legal name to reflect a clan name.',
                'order' => 2,
            ],
            [
                'name' => 'To match with bank details',
                'code' => 'bank_details',
                'description' => 'Use when the applicant wants the legal name to match bank records.',
                'order' => 3,
            ],
            [
                'name' => 'To match with employers details',
                'code' => 'employers_details',
                'description' => 'Use when the applicant wants the legal name to match employer records.',
                'order' => 4,
            ],
            [
                'name' => 'To match with religious beliefs',
                'code' => 'religious_beliefs',
                'description' => 'Use when the applicant wants the legal name to reflect religious beliefs.',
                'order' => 5,
            ],
        ];

        foreach ($reasons as $reason) {
            NameChangeReason::updateOrCreate(
                ['code' => $reason['code']],
                $reason + ['is_active' => true]
            );
        }
    }
}
