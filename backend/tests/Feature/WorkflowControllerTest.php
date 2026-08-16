<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\InstitutionSeeder::class,
            \Database\Seeders\ApplicationStatusSeeder::class,
            \Database\Seeders\NameChangeReasonSeeder::class,
            \Database\Seeders\VettingTypeSeeder::class,
            \Database\Seeders\VettingStatusSeeder::class,
            \Database\Seeders\DecisionValueSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);
    }

    public function test_application_detail_exposes_backend_allowed_actions(): void
    {
        $handoffStatus = ApplicationStatus::where('code', 'handoff_to_admin')->firstOrFail();

        $creator = User::factory()->create();
        $creator->assignRole('opc_data_entry');

        $application = Application::factory()->create([
            'created_by' => $creator->id,
            'status_id' => $handoffStatus->id,
        ]);

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('data.allowed_actions.0', 'view_application');
    }

    public function test_main_workflow_happy_path_is_enforced_by_http_endpoints(): void
    {
        $handoffStatus = ApplicationStatus::where('code', 'handoff_to_admin')->firstOrFail();
        $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();
        $pendingApprovalStatus = ApplicationStatus::where('code', 'pending_approval')->firstOrFail();
        $policeVettingStatus = ApplicationStatus::where('code', 'police_vetting')->firstOrFail();
        $nisVettingStatus = ApplicationStatus::where('code', 'nis_vetting')->firstOrFail();

        $dataEntry = User::factory()->create();
        $dataEntry->assignRole('opc_data_entry');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $policeOfficer = User::factory()->create();
        $policeOfficer->assignRole('police_officer');

        $nisOfficer = User::factory()->create();
        $nisOfficer->assignRole('nis_officer');

        $approver = User::factory()->create();
        $approver->assignRole('opc_approver');

        $createResponse = $this->actingAs($dataEntry, 'sanctum')
            ->postJson('/api/v1/applications', [
                'full_name' => 'Workflow Test',
                'national_id' => 'ABC12345',
                'district' => 'Rumphi',
                'traditional_authority' => 'Mwamulowe',
                'village' => 'Luwuchi',
                'requested_name' => 'Workflow Preferred',
                'reason' => 'To match with bank details',
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.status.code', $handoffStatus->code);

        $applicationId = $createResponse->json('data.id');

        $assignPoliceResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/applications/{$applicationId}/assign-police", [
                'police_officer_id' => $policeOfficer->id,
            ]);

        $assignPoliceResponse->assertOk()
            ->assertJsonPath('data.status.code', $policeVettingStatus->code)
            ->assertJsonPath('data.assigned_police_officer.id', $policeOfficer->id);

        $policeDetail = $this->actingAs($policeOfficer, 'sanctum')
            ->getJson("/api/v1/applications/{$applicationId}");

        $policeDetail->assertOk()
            ->assertJsonFragment(['conduct_police_vetting']);

        $submitPoliceResponse = $this->actingAs($policeOfficer, 'sanctum')
            ->postJson("/api/v1/applications/{$applicationId}/vetting/police", []);

        $submitPoliceResponse->assertCreated();

        $assignNisResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/applications/{$applicationId}/assign-nis", [
                'nis_officer_id' => $nisOfficer->id,
            ]);

        $assignNisResponse->assertOk()
            ->assertJsonPath('data.status.code', $nisVettingStatus->code)
            ->assertJsonPath('data.assigned_nis_officer.id', $nisOfficer->id);

        $submitNisResponse = $this->actingAs($nisOfficer, 'sanctum')
            ->postJson("/api/v1/applications/{$applicationId}/vetting/nis", []);

        $submitNisResponse->assertCreated();

        $forwardResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/applications/{$applicationId}/forward-to-approval");

        $forwardResponse->assertOk()
            ->assertJsonPath('data.status.code', $pendingApprovalStatus->code);

        $approverResponse = $this->actingAs($approver, 'sanctum')
            ->postJson("/api/v1/applications/{$applicationId}/send-back-to-admin", [
                'reason' => 'To match with clan name',
            ]);

        $approverResponse->assertOk()
            ->assertJsonPath('data.status.code', $opcReviewStatus->code)
            ->assertJsonPath('data.approver_send_back_reason', 'To match with clan name');
    }

    public function test_police_completion_still_succeeds_when_push_storage_is_unavailable(): void
    {
        $policeVettingStatus = ApplicationStatus::where('code', 'police_vetting')->firstOrFail();
        $opcReviewStatus = ApplicationStatus::where('code', 'opc_review')->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $policeOfficer = User::factory()->create();
        $policeOfficer->assignRole('police_officer');

        $application = Application::factory()->create([
            'status_id' => $policeVettingStatus->id,
            'assigned_police_officer_id' => $policeOfficer->id,
        ]);

        Schema::dropIfExists('push_subscriptions');

        $response = $this->actingAs($policeOfficer, 'sanctum')
            ->postJson("/api/v1/applications/{$application->id}/vetting/police/complete", [
                'vetting_date' => now()->toDateString(),
                'recommendation_id' => null,
                'findings' => 'Identity details were verified.',
                'remarks' => 'No adverse information was found.',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status_id' => $opcReviewStatus->id,
        ]);
    }

    public function test_vetting_list_visibility_comes_from_backend_allowed_actions(): void
    {
        $policeOfficer = User::factory()->create();
        $policeOfficer->assignRole('police_officer');

        $application = Application::factory()->create([
            'assigned_police_officer_id' => $policeOfficer->id,
            'status_id' => ApplicationStatus::where('code', 'police_vetting')->value('id'),
        ]);

        $response = $this->actingAs($policeOfficer, 'sanctum')
            ->getJson('/api/v1/applications?assigned_police_officer_id=' . $policeOfficer->id);

        $response->assertOk()
            ->assertJsonFragment(['id' => $application->id])
            ->assertJsonFragment(['conduct_police_vetting']);
    }
}
