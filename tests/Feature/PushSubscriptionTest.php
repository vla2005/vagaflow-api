<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.webpush.public_key', 'public-test-key');
        config()->set('services.webpush.private_key', 'private-test-key');
    }

    public function test_user_can_manage_a_device_subscription_and_read_notification_status(): void
    {
        $user = User::factory()->create();
        $endpoint = 'https://push.example.test/subscriptions/device-1';

        $this->getJson('/api/push-subscriptions')->assertUnauthorized();

        $this->actingAs($user)->getJson('/api/push-subscriptions')
            ->assertOk()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.devices', 0)
            ->assertJsonPath('data.unread_count', 0);

        $this->postJson('/api/push-subscriptions', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'public-device-key', 'auth' => 'device-auth-token'],
            'content_encoding' => 'aes128gcm',
        ])->assertOk()->assertJsonPath('data.devices', 1);

        $subscription = PushSubscription::query()->firstOrFail();
        $this->assertSame($user->id, $subscription->user_id);
        $this->assertSame($endpoint, $subscription->endpoint);
        $this->assertNotSame($endpoint, DB::table('push_subscriptions')->value('endpoint'));

        $this->deleteJson('/api/push-subscriptions', ['endpoint' => $endpoint])
            ->assertOk()
            ->assertJsonPath('data.devices', 0);
    }

    public function test_opening_an_opportunity_marks_it_as_viewed_and_reduces_the_badge_count(): void
    {
        $user = User::factory()->create();
        $job = Job::query()->create([
            'user_id' => $user->id,
            'fingerprint' => hash('sha256', 'push-job'),
            'level' => 'junior',
            'title' => 'Desenvolvedor Laravel',
            'score' => 92,
            'analysis_state' => 'accepted',
            'payload' => [],
        ]);

        $this->actingAs($user)->getJson('/api/push-subscriptions')
            ->assertJsonPath('data.unread_count', 1);

        $this->getJson("/api/jobs/{$job->id}")
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 0);

        $this->assertNotNull($job->refresh()->viewed_at);
    }
}
