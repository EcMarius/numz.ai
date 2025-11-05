<?php

namespace App\Numz\Modules\PaymentGateways;

use App\Numz\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

class PayPalGateway implements PaymentGatewayInterface
{
    protected $clientId;
    protected $secret;
    protected $baseUrl;

    public function __construct()
    {
        $this->clientId = config('numz.gateways.paypal.client_id');
        $this->secret = config('numz.gateways.paypal.secret');
        $this->baseUrl = config('numz.gateways.paypal.sandbox') 
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    protected function getAccessToken()
    {
        $response = Http::withBasicAuth($this->clientId, $this->secret)
            ->asForm()
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        return $response->json()['access_token'] ?? null;
    }

    public function charge(array $params): array
    {
        $token = $this->getAccessToken();
        
        try {
            $response = Http::withToken($token)
                ->post($this->baseUrl . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => $params['currency'] ?? 'USD',
                            'value' => $params['amount'],
                        ],
                        'description' => $params['description'] ?? 'NUMZ.AI Payment',
                    ]],
                ]);

            $data = $response->json();

            return [
                'success' => true,
                'transaction_id' => $data['id'],
                'approval_url' => $data['links'][1]['href'] ?? null,
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
        $token = $this->getAccessToken();

        try {
            $response = Http::withToken($token)
                ->post($this->baseUrl . "/v2/payments/captures/{$transactionId}/refund", [
                    'amount' => [
                        'value' => $amount,
                        'currency_code' => 'USD',
                    ],
                ]);

            return [
                'success' => true,
                'refund_id' => $response->json()['id'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getConfig(): array
    {
        return [
            'name' => 'PayPal',
            'supports_refunds' => true,
            'supports_recurring' => true,
            'currencies' => ['USD', 'EUR', 'GBP'],
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        return true;
    }
}
