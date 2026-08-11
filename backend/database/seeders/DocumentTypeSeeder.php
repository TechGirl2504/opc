<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Supporting Document',
                'code' => 'supporting_document',
                'description' => 'Supporting documents uploaded with application',
                'max_file_size' => 52428800, // 50MB
                'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'],
                'is_active' => true,
            ],
            [
                'name' => 'Police Vetting Report',
                'code' => 'police_vetting_report',
                'description' => 'Scanned police vetting report',
                'max_file_size' => 52428800, // 50MB
                'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'],
                'is_active' => true,
            ],
            [
                'name' => 'NIS Vetting Report',
                'code' => 'nis_vetting_report',
                'description' => 'Scanned NIS vetting report',
                'max_file_size' => 52428800, // 50MB
                'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'],
                'is_active' => true,
            ],
            [
                'name' => 'Other Document',
                'code' => 'other',
                'description' => 'Other supporting documents',
                'max_file_size' => 52428800, // 50MB
                'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'],
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            DocumentType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
