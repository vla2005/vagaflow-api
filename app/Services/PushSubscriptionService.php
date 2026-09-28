<?php

namespace App\Services;

use App\Models\Job;
use App\Models\PushSubscription;
use App\Models\User;

class PushSubscriptionService
{
    /** @param array<string, mixed> $data */
    public function store(User $user, array $data, ?string $userAgent): PushSubscription
    {
        return PushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id' => $user->id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['content_encoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $userAgent, 0, 500),
                'last_used_at' => now(),
            ],
        );
    }

    public function remove(User $user, string $endpoint): void
    {
        PushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint_hash', hash('sha256', $endpoint))
            ->delete();
    }

    public function unreadCount(User $user): int
    {
        return Job::query()
            ->where('user_id', $user->id)
            ->where('analysis_state', 'accepted')
            ->whereNull('viewed_at')
            ->where('status', '!=', 'ignored')
            ->count();
    }

    /** @return array<string, mixed> */
    public function status(User $user): array
    {
        $publicKey = (string) config('services.webpush.public_key');

        return [
            'available' => $publicKey !== '' && filled(config('services.webpush.private_key')),
            'public_key' => $publicKey !== '' ? $publicKey : null,
            'devices' => $user->pushSubscriptions()->count(),
            'unread_count' => $this->unreadCount($user),
        ];
    }
}
