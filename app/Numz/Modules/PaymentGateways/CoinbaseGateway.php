<?php

namespace App\Numz\Modules\PaymentGateways;

use App\Numz\Contracts\PaymentGatewayInterface;
use App\Models\ModuleSetting;
use Illuminate\Support\Facades\Http;

class CoinbaseGateway implements PaymentGatewayInterface
{
    protected $apiKey;
    protected $baseUrl = 'https://api.commerce.coinbase.com';
    protected $moduleName = 'coinbase';

    public function __construct()
    {
        $this->apiKey = ModuleSetting::get('payment_gateway', $this->moduleName, 'api_key')
            ?? config('numz.gateways.coinbase.api_key');
    }

    public function charge(array $params): array
    {
        try {
            $response = Http::withHeaders([
                'X-CC-Api-Key' => $this->apiKey,
                'X-CC-Version' => '2018-03-22',
            ])->post($this->baseUrl . '/charges', [
                'name' => $params['description'] ?? 'NUMZ.AI Payment',
                'description' => $params['description'] ?? 'Hosting service payment',
                'pricing_type' => 'fixed_price',
                'local_price' => [
                    'amount' => $params['amount'],
                    'currency' => $params['currency'] ?? 'USD',
                ],
                'metadata' => $params['metadata'] ?? [],
                'redirect_url' => $params['success_url'] ?? url('/'),
                'cancel_url' => $params['cancel_url'] ?? url('/'),
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['data'])) {
                return [
                    'success' => true,
                    'transaction_id' => $data['data']['id'],
                    'hosted_url' => $data['data']['hosted_url'],
                    'data' => $data['data'],
                ];
            }

            return [
                'success' => false,
                'error' => $data['error']['message'] ?? 'Unknown error',
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
        // Coinbase Commerce doesn't support refunds via API
        return [
            'success' => false,
            'error' => 'Coinbase Commerce does not support automatic refunds. Please process refunds manually.',
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => 'Coinbase Commerce',
            'description' => 'Accept cryptocurrency payments (Bitcoin, Ethereum, Litecoin, etc.) via Coinbase Commerce.',
            'supports_refunds' => false,
            'supports_recurring' => false,
            'currencies' => ['USD', 'EUR', 'GBP'],
            'settings' => [
                [
                    'key' => 'api_key',
                    'label' => 'API Key',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => true,
                ],
                [
                    'key' => 'webhook_secret',
                    'label' => 'Webhook Shared Secret',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => false,
                ],
            ],
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        $webhookSecret = ModuleSetting::get('payment_gateway', $this->moduleName, 'webhook_secret');

        if (!$webhookSecret) {
            return false;
        }

        $signature = request()->header('X-CC-Webhook-Signature');

        if (!$signature) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', request()->getContent(), $webhookSecret);

        return hash_equals($computedSignature, $signature);
    }
}
