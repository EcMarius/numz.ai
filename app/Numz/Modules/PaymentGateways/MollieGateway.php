<?php

namespace App\Numz\Modules\PaymentGateways;

class MollieGateway implements PaymentGatewayInterface
{
    private $apiKey;
    private $testMode;

    public function __construct()
    {
        $this->apiKey = config('numz.gateways.mollie.api_key');
        $this->testMode = config('numz.gateways.mollie.test_mode', true);
    }

    public function getName(): string
    {
        return 'Mollie';
    }

    public function processPayment(array $data): array
    {
        try {
            $mollie = new \Mollie\Api\MollieApiClient();
            $mollie->setApiKey($this->apiKey);

            $payment = $mollie->payments->create([
                'amount' => [
                    'currency' => $data['currency'] ?? 'USD',
                    'value' => number_format($data['amount'], 2, '.', ''),
                ],
                'description' => $data['description'] ?? 'Payment',
                'redirectUrl' => $data['redirect_url'],
                'webhookUrl' => $data['webhook_url'] ?? route('webhook.mollie'),
                'metadata' => [
                    'invoice_id' => $data['invoice_id'] ?? null,
                    'user_id' => $data['user_id'] ?? null,
                ],
            ]);

            return [
                'success' => true,
                'transaction_id' => $payment->id,
                'checkout_url' => $payment->getCheckoutUrl(),
                'status' => $payment->status,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(array $payload): array
    {
        try {
            $mollie = new \Mollie\Api\MollieApiClient();
            $mollie->setApiKey($this->apiKey);

            $payment = $mollie->payments->get($payload['id']);

            return [
                'success' => $payment->isPaid(),
                'transaction_id' => $payment->id,
                'amount' => $payment->amount->value,
                'currency' => $payment->amount->currency,
                'status' => $payment->status,
                'metadata' => (array) $payment->metadata,
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
            $mollie = new \Mollie\Api\MollieApiClient();
            $mollie->setApiKey($this->apiKey);

            $payment = $mollie->payments->get($transactionId);
            $refund = $payment->refund([
                'amount' => [
                    'currency' => $payment->amount->currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
