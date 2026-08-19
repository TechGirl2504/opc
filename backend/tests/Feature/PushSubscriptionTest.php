<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
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

    public function test_user_can_store_and_remove_push_subscription(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $payload = [
            'endpoint' => 'https://push.example.test/subscription/1',
            'keys' => [
                'p256dh' => 'test-p256dh',
                'auth' => 'test-auth',
            ],
            'content_encoding' => 'aes128gcm',
            'user_agent' => 'CNMIS Test Browser',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/push-subscriptions', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => $payload['endpoint'],
            'is_active' => true,
        ]);

        $deleteResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/push-subscriptions/delete', [
                'endpoint' => $payload['endpoint'],
            ]);

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, PushSubscription::where('endpoint', $payload['endpoint'])->count());
    }

    public function test_creating_notification_dispatches_web_push_after_commit(): void
    {
        Http::fake();

        if (!config('services.web_push.public_key') || !config('services.web_push.private_key') || !config('services.web_push.subject')) {
            $this->markTestSkipped('Web push keys are not configured for this environment.');
        }

        $user = User::factory()->create();
        $user->assignRole('admin');

        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/subscription/2',
            'p256dh' => 'test-p256dh',
            'auth_key' => 'test-auth',
            'content_encoding' => 'aes128gcm',
            'user_agent' => 'CNMIS Test Browser',
            'last_seen_at' => now(),
            'is_active' => true,
        ]);

        DB::transaction(function () use ($user): void {
            app(NotificationService::class)->createNotification(
                $user,
                'workflow_update',
                'Workflow Update',
                'The application has moved to the next stage.',
                \App\Models\Application::class,
                123
            );
        });

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization')
                && $request->hasHeader('TTL');
        });

        $this->assertSame(1, Notification::count());
    }
}
