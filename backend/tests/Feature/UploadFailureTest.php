<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\InstitutionSeeder::class,
            \Database\Seeders\ApplicationStatusSeeder::class,
            \Database\Seeders\NameChangeReasonSeeder::class,
            \Database\Seeders\DocumentTypeSeeder::class,
            \Database\Seeders\VettingTypeSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);
    }

    public function test_application_create_rejects_oversized_supporting_documents(): void
    {
        $creator = User::factory()->create();
        $creator->assignRole('opc_data_entry');

        $beforeCount = Application::count();

        $response = $this->actingAs($creator, 'sanctum')->post(
            '/api/v1/applications',
            [
                'full_name' => 'John Doe',
                'national_id' => 'ABC12345',
                'district' => 'Rumphi',
                'traditional_authority' => 'Mwamulowe',
                'village' => 'Luwuchi',
                'requested_name' => 'John Doe',
                'reason' => 'To match with bank details',
                'documents' => [
                    UploadedFile::fake()->create('oversized-supporting.pdf', 52001, 'application/pdf'),
                ],
            ],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame($beforeCount, Application::count());
    }

    public function test_document_upload_rejects_oversized_file_and_keeps_database_clean(): void
    {
        Storage::fake('documents');

        $creator = User::factory()->create();
        $creator->assignRole('opc_data_entry');

        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => ApplicationStatus::where('code', 'handoff_to_admin')->value('id'),
        ]);

        $beforeDocumentCount = \App\Models\Document::count();
        $supportingDocumentTypeId = \App\Models\DocumentType::where('code', 'supporting_document')->value('id');

        $response = $this->actingAs($creator, 'sanctum')->post(
            "/api/v1/applications/{$application->id}/documents",
            [
                'document_type_id' => $supportingDocumentTypeId,
                'description' => 'Oversized supporting document test.',
                'file' => UploadedFile::fake()->create('oversized-supporting.pdf', 52001, 'application/pdf'),
            ],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame($beforeDocumentCount, \App\Models\Document::count());
        $this->assertCount(0, Storage::disk('documents')->allFiles());
    }
}
