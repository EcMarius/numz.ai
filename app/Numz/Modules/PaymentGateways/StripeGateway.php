<?php

namespace App\Numz\Modules\PaymentGateways;

use App\Numz\Contracts\PaymentGatewayInterface;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Refund;

class StripeGateway implements PaymentGatewayInterface
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('numz.gateways.stripe.secret_key');
        Stripe::setApiKey($this->apiKey);
    }

    public function charge(array $params): array
    {
        try {
            $charge = Charge::create([
                'amount' => $params['amount'] * 100, // Convert to cents
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
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        // Implement webhook signature validation
        return true;
    }
}
