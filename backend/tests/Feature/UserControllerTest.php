<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

    public function test_admin_can_create_user_with_strong_password_and_single_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $institution = Institution::firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/users', [
            'username' => 'new_admin_user',
            'email' => 'new.admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'institution_id' => $institution->id,
            'role' => 'admin',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $createdUser = User::where('username', 'new_admin_user')->firstOrFail();
        $this->assertSame('new.admin@example.com', $createdUser->email);
        $this->assertSame($institution->id, $createdUser->institution_id);
        $this->assertTrue(Hash::check('password123', $createdUser->password));
        $this->assertTrue($createdUser->hasRole('admin'));
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $institution = Institution::firstOrFail();
        $user = User::factory()->create([
            'institution_id' => $institution->id,
        ]);
        $user->assignRole('opc_data_entry');

        $originalPassword = $user->password;

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/users/{$user->id}", [
            'username' => 'updated_user',
            'email' => 'updated.user@example.com',
            'institution_id' => $institution->id,
            'role' => 'opc_data_entry',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertSame($originalPassword, $user->password);
        $this->assertSame('updated_user', $user->username);
        $this->assertTrue($user->hasRole('opc_data_entry'));
    }
}
