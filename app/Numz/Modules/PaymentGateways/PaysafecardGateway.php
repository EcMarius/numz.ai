<?php

namespace App\Numz\Modules\PaymentGateways;

use App\Numz\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

class PaysafecardGateway implements PaymentGatewayInterface
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('numz.gateways.paysafecard.api_key');
        $this->baseUrl = config('numz.gateways.paysafecard.test_mode')
            ? 'https://apitest.paysafecard.com/v1'
            : 'https://api.paysafecard.com/v1';
    }

    public function charge(array $params): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payments', [
                'type' => 'PAYSAFECARD',
                'amount' => $params['amount'],
                'currency' => $params['currency'] ?? 'EUR',
                'redirect' => [
                    'success_url' => $params['success_url'],
                    'failure_url' => $params['failure_url'],
                ],
                'notification_url' => $params['webhook_url'],
                'customer' => [
                    'id' => $params['user_id'],
                ],
            ]);

            $data = $response->json();

            return [
                'success' => true,
                'transaction_id' => $data['id'],
                'redirect_url' => $data['redirect']['auth_url'],
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function refund(string $transactionId, float $amount): array
    {
        // Paysafecard doesn't support refunds via API
        return [
            'success' => false,
            'error' => 'Paysafecard does not support automatic refunds',
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Paysafecard',
            'supports_refunds' => false,
            'supports_recurring' => false,
            'currencies' => ['EUR', 'USD'],
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        return true;
    }
}
