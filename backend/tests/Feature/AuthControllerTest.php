<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed required configuration data
        $this->seed([
            \Database\Seeders\InstitutionSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class,
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->withSession([])
            ->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_login_when_account_is_inactive(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_authenticated_user_can_get_their_info(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession([])
            ->actingAs($user, 'web')
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_multi_role_user_must_select_an_active_role_and_can_switch_roles(): void
    {
        $user = User::factory()->create([
            'username' => 'multi_role_user',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $user->assignRole(['admin', 'opc_data_entry']);

        $login = $this->withSession([])->postJson('/api/v1/auth/login', [
            'username' => 'multi_role_user',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.user.active_role', null)
            ->assertJsonPath('data.user.roles.0', 'admin')
            ->assertJsonPath('data.user.roles.1', 'opc_data_entry');

        $this->withSession([])->getJson('/api/v1/users')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'ROLE_SELECTION_REQUIRED');

        $this->withSession([])->postJson('/api/v1/auth/active-role', ['role' => 'opc_data_entry'])
            ->assertOk()
            ->assertJsonPath('data.active_role', 'opc_data_entry');

        $this->withSession(['active_role' => 'opc_data_entry'])->getJson('/api/v1/users')->assertForbidden();

        $this->withSession(['active_role' => 'opc_data_entry'])->postJson('/api/v1/auth/active-role', ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.active_role', 'admin');

        $this->withSession(['active_role' => 'admin'])->getJson('/api/v1/users')->assertOk();
    }

    public function test_user_cannot_select_an_unassigned_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('opc_data_entry');

        $this->withSession([])->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/active-role', ['role' => 'admin'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'ROLE_NOT_ASSIGNED');
    }
}
