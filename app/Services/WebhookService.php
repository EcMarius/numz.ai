<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Available webhook events
     */
    public const EVENTS = [
        'invoice.created',
        'invoice.paid',
        'invoice.overdue',
        'invoice.cancelled',
        'service.created',
        'service.activated',
        'service.suspended',
        'service.terminated',
        'service.upgraded',
        'domain.registered',
        'domain.renewed',
        'domain.transferred',
        'ticket.created',
        'ticket.replied',
        'ticket.closed',
        'client.created',
        'client.updated',
        'payment.received',
        'payment.failed',
    ];

    /**
     * Trigger webhook for an event
     */
    public function trigger(string $event, array $payload, ?int $userId = null): void
    {
        // Get all webhooks subscribed to this event
        $webhooksQuery = Webhook::where('is_active', true)
            ->whereJsonContains('events', $event);

        if ($userId) {
            $webhooksQuery->where('user_id', $userId);
        }

        $webhooks = $webhooksQuery->get();

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $event, $payload);
        }
    }

    /**
     * Deliver webhook to endpoint
     */
    public function deliverWebhook(Webhook $webhook, string $event, array $payload): void
    {
        $fullPayload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $jsonPayload = json_encode($fullPayload);
        $signature = $webhook->generateSignature($jsonPayload);

        // Create delivery record
        $delivery = $webhook->deliveries()->create([
            'event' => $event,
            'payload' => $fullPayload,
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $this->sendWebhook($webhook, $delivery, $jsonPayload, $signature);
    }

    /**
     * Send webhook HTTP request
     */
    public function sendWebhook(Webhook $webhook, WebhookDelivery $delivery, string $jsonPayload, string $signature): void
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $delivery->event,
                    'User-Agent' => 'Numz-Webhook/1.0',
                ])
                ->post($webhook->url, $delivery->payload);

            $statusCode = $response->status();

            if ($response->successful()) {
                $delivery->markAsDelivered($statusCode, $response->body());
                $webhook->resetFailureCount();
                $webhook->update(['last_triggered_at' => now()]);
            } else {
                $delivery->markAsFailed(
                    "HTTP {$statusCode}: " . $response->body(),
                    $statusCode
                );
                $webhook->incrementFailureCount();

                // Schedule retry if applicable
                if ($delivery->attempts < ($webhook->max_retries ?? 3)) {
                    $delivery->scheduleRetry();
                }
            }
        } catch (\Exception $e) {
            $delivery->markAsFailed($e->getMessage());
            $webhook->incrementFailureCount();

            // Schedule retry if applicable
            if ($delivery->attempts < ($webhook->max_retries ?? 3)) {
                $delivery->scheduleRetry();
            }

            Log::error('Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retry failed webhook deliveries
     */
    public function retryFailedDeliveries(): void
    {
        $deliveries = WebhookDelivery::where('status', 'pending')
            ->where('next_retry_at', '<=', now())
            ->with('webhook')
            ->get();

        foreach ($deliveries as $delivery) {
            if (!$delivery->webhook->is_active) {
                continue;
            }

            $jsonPayload = json_encode($delivery->payload);
            $signature = $delivery->webhook->generateSignature($jsonPayload);

            $this->sendWebhook($delivery->webhook, $delivery, $jsonPayload, $signature);
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
