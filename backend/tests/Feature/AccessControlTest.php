<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\InstitutionSeeder::class,
            \Database\Seeders\ApplicationStatusSeeder::class,
            \Database\Seeders\DocumentTypeSeeder::class,
            \Database\Seeders\VettingTypeSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);
    }

    public function test_user_cannot_upload_document_to_unassigned_application(): void
    {
        $owner = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $owner->id,
        ]);

        $officer = User::factory()->create();
        $officer->assignRole('police_officer');

        $response = $this->actingAs($officer, 'sanctum')->postJson(
            "/api/v1/applications/{$application->id}/documents",
            [
                'document_type_id' => \App\Models\DocumentType::where('code', 'supporting_document')->value('id'),
            ]
        );

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_creator_can_upload_document_to_own_application_and_it_is_stored(): void
    {
        Storage::fake('documents');

        $creator = User::factory()->create();
        $creator->assignRole('opc_data_entry');

        $application = Application::factory()->create([
            'created_by' => $creator->id,
        ]);

        $documentTypeId = \App\Models\DocumentType::where('code', 'supporting_document')->value('id');

        $response = $this->actingAs($creator, 'sanctum')->postJson(
            "/api/v1/applications/{$application->id}/documents",
            [
                'document_type_id' => $documentTypeId,
                'description' => 'Supporting document upload test.',
                'file' => UploadedFile::fake()->image('supporting-evidence.png'),
            ]
        );

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['application_id' => $application->id]);

        $documentPath = $response->json('data.file_path');

        $this->assertNotNull($documentPath);
        Storage::disk('documents')->assertExists($documentPath);
        $this->assertDatabaseHas('documents', [
            'application_id' => $application->id,
            'uploaded_by' => $creator->id,
            'description' => 'Supporting document upload test.',
        ]);
    }

    public function test_user_cannot_view_vetting_for_unassigned_application(): void
    {
        $owner = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $owner->id,
        ]);

        $officer = User::factory()->create();
        $officer->assignRole('police_officer');

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}/vetting/police");

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_creator_and_admin_can_edit_pending_application(): void
    {
        $pendingStatus = ApplicationStatus::where('code', 'pending')->firstOrFail();

        $creator = User::factory()->create();
        $creator->assignRole('opc_data_entry');

        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $pendingStatus->id,
        ]);

        $creatorResponse = $this->actingAs($creator, 'sanctum')
            ->putJson("/api/v1/applications/{$application->id}", [
                'full_name' => 'Updated Name',
            ]);

        $creatorResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin->assignRole($adminRole);

        $adminResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/applications/{$application->id}", [
                'full_name' => 'Admin Update',
            ]);

        $adminResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_data_entry_role_does_not_have_broad_application_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('opc_data_entry');

        $this->assertFalse($user->hasPermissionTo('view all applications'));
        $this->assertFalse($user->hasPermissionTo('assign applications'));
    }

    public function test_admin_cannot_see_approver_actions_in_pending_approval(): void
    {
        $pendingApprovalStatus = ApplicationStatus::where('code', 'pending_approval')->firstOrFail();

        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin->assignRole($adminRole);

        $application = Application::factory()->create([
            'created_by' => User::factory()->create()->id,
            'status_id' => $pendingApprovalStatus->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}");

        $response->assertStatus(200)
            ->assertJsonMissing(['deny_application'])
            ->assertJsonMissing(['approve_application'])
            ->assertJsonMissing(['send_back_to_admin'])
            ->assertJsonMissing(['edit_application']);
    }

    public function test_opc_approver_can_see_final_approval_actions(): void
    {
        $pendingApprovalStatus = ApplicationStatus::where('code', 'pending_approval')->firstOrFail();

        $approver = User::factory()->create();
        $approver->assignRole('opc_approver');

        $application = Application::factory()->create([
            'created_by' => User::factory()->create()->id,
            'status_id' => $pendingApprovalStatus->id,
        ]);

        $response = $this->actingAs($approver, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['approve_application'])
            ->assertJsonFragment(['deny_application'])
            ->assertJsonFragment(['send_back_to_admin']);
    }

    public function test_admin_can_send_pending_application_back_to_data_entry(): void
    {
        $pendingStatus = ApplicationStatus::where('code', 'pending')->firstOrFail();
        $returnedStatus = ApplicationStatus::where('code', 'returned_to_data_entry')->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $creator = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $pendingStatus->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/send-back-to-data-entry", [
                'reason' => 'Please correct the district spelling.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['code' => $returnedStatus->code]);
    }

    public function test_admin_can_edit_pending_application_directly(): void
    {
        $pendingStatus = ApplicationStatus::where('code', 'pending')->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $creator = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $pendingStatus->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/applications/{$application->id}", [
                'full_name' => 'John Updated',
                'district' => 'Lilongwe',
                'traditional_authority' => 'T/A Sample',
                'village' => 'Sample Village',
                'requested_name' => 'John Updated',
                'reason' => 'Minor correction to spelling and location details.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['full_name' => 'John Updated']);
    }

    public function test_admin_can_edit_pending_application_even_without_explicit_permission_sync(): void
    {
        $pendingStatus = ApplicationStatus::where('code', 'pending')->firstOrFail();

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $adminRole->revokePermissionTo('edit applications');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $creator = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $pendingStatus->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/applications/{$application->id}", [
                'full_name' => 'Admin Updated Again',
                'district' => 'Lilongwe',
                'traditional_authority' => 'T/A Sample',
                'village' => 'Sample Village',
                'requested_name' => 'Admin Updated Again',
                'reason' => 'Minor correction to spelling and location details.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['full_name' => 'Admin Updated Again']);
    }

    public function test_admin_can_edit_application_after_approver_send_back(): void
    {
        $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $policeOfficer = User::factory()->create();
        $policeOfficer->assignRole('police_officer');

        $nisOfficer = User::factory()->create();
        $nisOfficer->assignRole('nis_officer');

        $creator = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $opcReviewStatus->id,
            'assigned_police_officer_id' => $policeOfficer->id,
            'assigned_nis_officer_id' => $nisOfficer->id,
            'approver_send_back_reason' => 'Please make a small correction before approval.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['edit_application'])
            ->assertJsonFragment(['handle_approver_send_back']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/applications/{$application->id}", [
                'district' => 'Lilongwe',
                'traditional_authority' => 'T/A Sample',
                'village' => 'Sample Village',
                'reason' => 'Minor correction after approver review.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['district' => 'Lilongwe']);
    }

    public function test_admin_can_handle_approver_return_to_police_from_ui_state(): void
    {
        $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();
        $policeStatus = ApplicationStatus::where('code', 'police_vetting')->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $policeOfficer = User::factory()->create();
        $policeOfficer->assignRole('police_officer');

        $creator = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $opcReviewStatus->id,
            'assigned_police_officer_id' => $policeOfficer->id,
            'approver_send_back_reason' => 'Please re-check the police findings.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/handle-approver-send-back", [
                'action' => 'send_to_police',
                'reason' => 'Re-check the district and residence details.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['code' => $policeStatus->code]);
    }

    public function test_admin_can_handle_approver_return_to_nis_from_ui_state(): void
    {
        $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();
        $nisStatus = ApplicationStatus::where('code', 'nis_vetting')->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $nisOfficer = User::factory()->create();
        $nisOfficer->assignRole('nis_officer');

        $creator = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $opcReviewStatus->id,
            'assigned_nis_officer_id' => $nisOfficer->id,
            'approver_send_back_reason' => 'Please re-check the NIS findings.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/handle-approver-send-back", [
                'action' => 'send_to_nis',
                'reason' => 'Re-check the supporting documents.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['code' => $nisStatus->code]);
    }

    public function test_creator_can_resubmit_returned_application(): void
    {
        $pendingStatus = ApplicationStatus::where('code', 'pending')->firstOrFail();
        $returnedStatus = ApplicationStatus::where('code', 'returned_to_data_entry')->firstOrFail();

        $creator = User::factory()->create();
        $creator->assignRole('opc_data_entry');

        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $returnedStatus->id,
            'data_entry_return_reason' => 'Please correct the district spelling.',
        ]);

        $response = $this->actingAs($creator, 'sanctum')
            ->putJson("/api/v1/applications/{$application->id}", [
                'full_name' => 'John Doe',
                'district' => 'Rumphi',
                'traditional_authority' => 'Mwamulowe',
                'village' => 'Luwuchi',
                'requested_name' => 'John Doe',
                'reason' => 'Name change due to marriage.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['code' => $pendingStatus->code]);
    }
}
