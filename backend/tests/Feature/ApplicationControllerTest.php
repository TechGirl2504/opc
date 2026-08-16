<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed required configuration data
        $this->seed([
            \Database\Seeders\InstitutionSeeder::class,
            \Database\Seeders\ApplicationStatusSeeder::class,
            \Database\Seeders\NameChangeReasonSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_applications(): void
    {
        Application::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_user_can_view_single_application(): void
    {
        $application = Application::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_user_cannot_view_another_users_application(): void
    {
        $owner = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}");

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_user_cannot_delete_another_users_application(): void
    {
        $owner = User::factory()->create();
        $application = Application::factory()->create([
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/applications/{$application->id}");

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_user_can_search_applications(): void
    {
        Application::factory()->create(['full_name' => 'John Doe']);
        Application::factory()->create(['full_name' => 'Jane Smith']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/applications?search=John');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
