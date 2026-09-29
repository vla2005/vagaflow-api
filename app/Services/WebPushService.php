<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;
use Throwable;

class WebPushService
{
    public function __construct(private PushSubscriptionService $subscriptions) {}

    public function sendNewOpportunity(Job $job): void
    {
        $job->loadMissing('user.pushSubscriptions');
        $devices = $job->user->pushSubscriptions;

        if ($devices->isEmpty()) {
            return;
        }

        $publicKey = (string) config('services.webpush.public_key');
        $privateKey = (string) config('services.webpush.private_key');
        $subject = (string) config('services.webpush.subject');

        if ($publicKey === '' || $privateKey === '' || $subject === '') {
            Log::warning('Web Push não configurado; notificação não enviada.', ['job_id' => $job->id]);
            throw new RuntimeException('Web Push não configurado.');
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => $subject,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ]]);
        $payload = json_encode([
            'title' => 'Nova vaga adequada',
            'body' => sprintf('%s%s · %d%% de compatibilidade', $job->title, $job->company ? ' - '.$job->company : '', $job->score ?? 0),
            'url' => '/vagas/'.$job->id,
            'jobId' => $job->id,
            'unreadCount' => $this->subscriptions->unreadCount($job->user),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $delivered = false;

        foreach ($devices as $device) {
            try {
                $subscription = new Subscription(
                    $device->endpoint,
                    $device->public_key,
                    $device->auth_token,
                    $device->content_encoding,
                );
                $report = $webPush->sendOneNotification($subscription, $payload, [
                    'TTL' => 86400,
                    'urgency' => 'high',
                    'topic' => 'job-'.$job->id,
                ]);

                if ($report->isSubscriptionExpired()) {
                    $device->delete();
                } elseif ($report->isSuccess()) {
                    $delivered = true;
                    $device->update(['last_used_at' => now()]);
                } else {
                    $response = $report->getResponse();

                    Log::warning('Falha ao enviar Web Push.', [
                        'job_id' => $job->id,
                        'subscription_id' => $device->id,
                        'reason' => $report->getReason(),
                        'status' => $response?->getStatusCode(),
                        'response' => $report->getResponseContent(),
                        'apns_id' => $response?->getHeaderLine('apns-id'),
                        'endpoint_host' => parse_url($device->endpoint, PHP_URL_HOST),
                    ]);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if (! $delivered) {
            throw new RuntimeException('Nenhum dispositivo recebeu a notificação Web Push.');
        }

        $job->update(['push_notified_at' => now()]);
    }
}
