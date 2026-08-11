<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Notification;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\VettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $dataEntry;
    protected User $approver;
    protected User $policeOfficer;
    protected User $nisOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\InstitutionSeeder::class,
            \Database\Seeders\ApplicationStatusSeeder::class,
            \Database\Seeders\VettingTypeSeeder::class,
            \Database\Seeders\VettingStatusSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);

        $this->admin = User::factory()->create(['username' => 'admin_user']);
        $this->admin->assignRole('admin');

        $this->dataEntry = User::factory()->create(['username' => 'data_entry_user']);
        $this->dataEntry->assignRole('opc_data_entry');

        $this->approver = User::factory()->create(['username' => 'approver_user']);
        $this->approver->assignRole('opc_approver');

        $this->policeOfficer = User::factory()->create(['username' => 'police_user']);
        $this->policeOfficer->assignRole('police_officer');

        $this->nisOfficer = User::factory()->create(['username' => 'nis_user']);
        $this->nisOfficer->assignRole('nis_officer');
    }

    public function test_notifications_follow_the_exact_workflow_handoffs(): void
    {
        /** @var ApplicationService $applicationService */
        $applicationService = app(ApplicationService::class);
        /** @var VettingService $vettingService */
        $vettingService = app(VettingService::class);

        $application = $applicationService->createApplication([
            'full_name' => 'John Doe',
            'national_id' => 'ABC12345',
            'district' => 'Rumphi',
            'traditional_authority' => 'Mwamulowe',
            'village' => 'Luwuchi',
            'current_name' => 'John Smith',
            'requested_name' => 'John Doe',
            'reason' => 'Name change due to marriage.',
        ], $this->dataEntry);

        $this->assertNotification($this->admin, 'application_received', 'Application Received', $application);
        $this->assertSame(1, Notification::count());

        $application = $applicationService->assignToPolice($application, $this->policeOfficer->id, $this->admin);
        $this->assertNotification($this->policeOfficer, 'application_assigned', 'Application Received for Vetting', $application);
        $this->assertSame(2, Notification::count());

        $vettingService->submitPoliceVetting($application, [], $this->policeOfficer);
        $application = $application->fresh(['status', 'policeVetting', 'nisVetting']);
        $this->assertNotification($this->admin, 'vetting_completed', 'Police Vetting Completed', $application);
        $this->assertSame(3, Notification::count());

        $application = $applicationService->assignToNis($application, $this->nisOfficer->id, $this->admin);
        $this->assertNotification($this->nisOfficer, 'application_assigned', 'Application Received for Vetting', $application);
        $this->assertSame(4, Notification::count());

        $vettingService->submitNisVetting($application, [], $this->nisOfficer);
        $application = $application->fresh(['status', 'policeVetting', 'nisVetting']);
        $this->assertNotification($this->admin, 'vetting_completed', 'Nis Vetting Completed', $application);
        $this->assertSame(5, Notification::count());

        $application = $applicationService->forwardToApproval($application, $this->admin);
        $this->assertNotification($this->approver, 'approval_required', 'Application Ready for Approval', $application);
        $this->assertSame(6, Notification::count());

        $application = $applicationService->sendBackToAdmin($application, 'Needs additional supporting details', $this->approver);
        $this->assertNotification($this->admin, 'application_sent_back_to_admin', 'Application Sent Back for Review', $application);
        $this->assertSame(7, Notification::count());

        $returnedApplication = $applicationService->createApplication([
            'full_name' => 'Mary Jane',
            'national_id' => 'DEF67890',
            'district' => 'Mzimba',
            'traditional_authority' => 'Khosolo',
            'village' => 'Boma',
            'current_name' => 'Mary Jones',
            'requested_name' => 'Mary Jane',
            'reason' => 'Correction needed.',
        ], $this->dataEntry);

        $returnedApplication = $applicationService->sendBackToDataEntry($returnedApplication, 'Missing district details', $this->admin);
        $this->assertNotification($this->dataEntry, 'application_sent_back_to_data_entry', 'Application Returned for Correction', $returnedApplication);
        $this->assertSame(9, Notification::count());

        $returnedApplication = $applicationService->updateApplication($returnedApplication, [
            'district' => 'Mzimba',
            'traditional_authority' => 'Khosolo',
            'village' => 'Boma',
            'reason' => 'Correction completed.',
        ], $this->dataEntry);

        $this->assertNotification($this->admin, 'application_received', 'Application Received', $returnedApplication);
        $this->assertSame(10, Notification::count());
    }

    public function test_notifications_api_exposes_application_link_data(): void
    {
        /** @var ApplicationService $applicationService */
        $applicationService = app(ApplicationService::class);

        $application = $applicationService->createApplication([
            'full_name' => 'John Doe',
            'national_id' => 'XYZ12345',
            'district' => 'Rumphi',
            'traditional_authority' => 'Mwamulowe',
            'village' => 'Luwuchi',
            'current_name' => 'John Smith',
            'requested_name' => 'John Doe',
            'reason' => 'Workflow link test.',
        ], $this->dataEntry);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonPath('data.0.related_model_id', $application->id)
            ->assertJsonPath('data.0.data.application_id', $application->id);
    }

    private function assertNotification(User $user, string $type, string $title, Application $application): void
    {
        $notification = Notification::latest('id')->first();

        $this->assertNotNull($notification);
        $this->assertSame($user->id, $notification->user_id);
        $this->assertSame($type, $notification->type);
        $this->assertSame($title, $notification->title);
        $this->assertSame($application->id, $notification->related_model_id);
    }
}
