<?php

namespace App\Numz\Services;

use App\Models\Webhook;
use App\Models\WebhookCall;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Trigger webhooks for a specific event
     *
     * @param string $event
     * @param array $payload
     * @return int Number of webhooks triggered
     */
    public function trigger(string $event, array $payload): int
    {
        $webhooks = Webhook::where('active', true)
            ->whereJsonContains('events', $event)
            ->get();

        $count = 0;

        foreach ($webhooks as $webhook) {
            $this->dispatch($webhook, $event, $payload);
            $count++;
        }

        return $count;
    }

    /**
     * Dispatch a webhook call
     *
     * @param Webhook $webhook
     * @param string $event
     * @param array $payload
     * @return WebhookCall
     */
    public function dispatch(Webhook $webhook, string $event, array $payload): WebhookCall
    {
        $call = WebhookCall::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'attempt' => 1,
        ]);

        $this->send($call);

        return $call;
    }

    /**
     * Send a webhook call with retry logic
     *
     * @param WebhookCall $call
     * @return bool
     */
    public function send(WebhookCall $call): bool
    {
        try {
            $webhook = $call->webhook;

            // Build headers
            $headers = [
                'Content-Type' => 'application/json',
                'X-Webhook-Event' => $call->event,
                'X-Webhook-ID' => $call->id,
                'X-Webhook-Attempt' => $call->attempt,
            ];

            // Add signature if secret is set
            if ($webhook->secret) {
                $signature = hash_hmac('sha256', json_encode($call->payload), $webhook->secret);
                $headers['X-Webhook-Signature'] = $signature;
            }

            // Send the webhook
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($webhook->url, $call->payload);

            // Update call record
            $call->update([
                'status_code' => $response->status(),
                'response' => $response->body(),
                'delivered_at' => $response->successful() ? now() : null,
            ]);

            if ($response->successful()) {
                Log::info("Webhook delivered successfully", [
                    'webhook_id' => $webhook->id,
                    'event' => $call->event,
                    'attempt' => $call->attempt,
                ]);

                return true;
            }

            // Handle retry
            if ($call->shouldRetry()) {
                $this->retry($call);
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Webhook delivery failed", [
                'webhook_id' => $call->webhook_id,
                'error' => $e->getMessage(),
            ]);

            $call->update([
                'response' => $e->getMessage(),
            ]);

            // Retry on exception
            if ($call->shouldRetry()) {
                $this->retry($call);
            }

            return false;
        }
    }

    /**
     * Retry a failed webhook call
     *
     * @param WebhookCall $call
     * @return void
     */
    protected function retry(WebhookCall $call): void
    {
        // Exponential backoff: 2^attempt minutes
        $delay = pow(2, $call->attempt);

        $call->update([
            'attempt' => $call->attempt + 1,
        ]);

        // Dispatch retry job after delay
        dispatch(function () use ($call) {
            $this->send($call);
        })->delay(now()->addMinutes($delay));

        Log::info("Webhook retry scheduled", [
            'webhook_id' => $call->webhook_id,
            'attempt' => $call->attempt,
            'delay_minutes' => $delay,
        ]);
    }

    /**
     * Available webhook events
     *
     * @return array
     */
    public static function availableEvents(): array
    {
        return [
            'invoice.created' => 'Invoice Created',
            'invoice.paid' => 'Invoice Paid',
            'invoice.overdue' => 'Invoice Overdue',
            'invoice.cancelled' => 'Invoice Cancelled',
            'service.created' => 'Service Created',
            'service.activated' => 'Service Activated',
            'service.suspended' => 'Service Suspended',
            'service.terminated' => 'Service Terminated',
            'domain.registered' => 'Domain Registered',
            'domain.renewed' => 'Domain Renewed',
            'domain.transferred' => 'Domain Transferred',
            'domain.expired' => 'Domain Expired',
            'ticket.created' => 'Support Ticket Created',
            'ticket.replied' => 'Support Ticket Reply',
            'ticket.closed' => 'Support Ticket Closed',
            'user.created' => 'User Created',
            'user.updated' => 'User Updated',
            'payment.received' => 'Payment Received',
            'payment.refunded' => 'Payment Refunded',
        ];
    }
}
