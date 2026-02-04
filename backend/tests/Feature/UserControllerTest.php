<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            \Database\Seeders\InstitutionSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);
    }

    public function test_users_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_users_index_requires_view_users_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    public function test_user_with_admin_role_can_list_users(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin->assignRole($adminRole);

        User::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to',
                    'timestamp',
                ],
            ]);
    }
}
