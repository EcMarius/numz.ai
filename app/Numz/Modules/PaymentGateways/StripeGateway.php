<?php

namespace App\Numz\Modules\PaymentGateways;

use App\Numz\Contracts\PaymentGatewayInterface;
use App\Models\ModuleSetting;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Refund;

class StripeGateway implements PaymentGatewayInterface
{
    protected $apiKey;
    protected $moduleName = 'stripe';

    public function __construct()
    {
        $this->apiKey = ModuleSetting::get('payment_gateway', $this->moduleName, 'secret_key')
            ?? config('numz.gateways.stripe.secret_key');
        
        if ($this->apiKey) {
            Stripe::setApiKey($this->apiKey);
        }
    }

    public function charge(array $params): array
    {
        try {
            $charge = Charge::create([
                'amount' => $params['amount'] * 100,
                'currency' => $params['currency'] ?? 'usd',
                'source' => $params['token'],
                'description' => $params['description'] ?? 'NUMZ.AI Payment',
                'metadata' => $params['metadata'] ?? [],
            ]);

            return [
                'success' => true,
                'transaction_id' => $charge->id,
                'status' => $charge->status,
                'data' => $charge,
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
        try {
            $refund = Refund::create([
                'charge' => $transactionId,
                'amount' => $amount * 100,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
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
            'name' => 'Stripe',
            'supports_refunds' => true,
            'supports_recurring' => true,
            'currencies' => ['USD', 'EUR', 'GBP'],
            'settings' => [
                [
                    'key' => 'secret_key',
                    'label' => 'Secret Key',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => true,
                ],
                [
                    'key' => 'publishable_key',
                    'label' => 'Publishable Key',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => true,
                ],
                [
                    'key' => 'webhook_secret',
                    'label' => 'Webhook Secret',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => false,
                ],
            ],
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        return true;
    }
}
