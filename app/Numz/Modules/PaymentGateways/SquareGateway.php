<?php

namespace App\Numz\Modules\PaymentGateways;

use Square\SquareClient;
use Square\Environment;
use Square\Models\Money;
use Square\Models\CreatePaymentRequest;

class SquareGateway implements PaymentGatewayInterface
{
    private $client;
    private $locationId;

    public function __construct()
    {
        $accessToken = config('numz.gateways.square.access_token');
        $environment = config('numz.gateways.square.sandbox', true)
            ? Environment::SANDBOX
            : Environment::PRODUCTION;

        $this->client = new SquareClient([
            'accessToken' => $accessToken,
            'environment' => $environment,
        ]);

        $this->locationId = config('numz.gateways.square.location_id');
    }

    public function getName(): string
    {
        return 'Square';
    }

    public function processPayment(array $data): array
    {
        try {
            $amountMoney = new Money();
            $amountMoney->setAmount($data['amount'] * 100); // Convert to cents
            $amountMoney->setCurrency($data['currency'] ?? 'USD');

            $createPaymentRequest = new CreatePaymentRequest(
                $data['source_id'], // nonce from Square.js
                uniqid()
            );
            $createPaymentRequest->setAmountMoney($amountMoney);
            $createPaymentRequest->setLocationId($this->locationId);

            $response = $this->client->getPaymentsApi()->createPayment($createPaymentRequest);

            if ($response->isSuccess()) {
                $payment = $response->getResult()->getPayment();
                return [
                    'success' => true,
                    'transaction_id' => $payment->getId(),
                    'status' => $payment->getStatus(),
                ];
            } else {
                $errors = $response->getErrors();
                return [
                    'success' => false,
                    'error' => $errors[0]->getDetail() ?? 'Payment failed',
                ];
            }

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
            $paymentId = $payload['data']['object']['payment']['id'] ?? null;

            if (!$paymentId) {
                return ['success' => false, 'error' => 'Invalid webhook payload'];
            }

            $response = $this->client->getPaymentsApi()->getPayment($paymentId);

            if ($response->isSuccess()) {
                $payment = $response->getResult()->getPayment();
                return [
                    'success' => $payment->getStatus() === 'COMPLETED',
                    'transaction_id' => $payment->getId(),
                    'amount' => $payment->getAmountMoney()->getAmount() / 100,
                    'currency' => $payment->getAmountMoney()->getCurrency(),
                    'status' => $payment->getStatus(),
                ];
            }

            return ['success' => false, 'error' => 'Payment not found'];

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
            $amountMoney = new Money();
            $amountMoney->setAmount($amount * 100);
            $amountMoney->setCurrency('USD');

            $body = new \Square\Models\RefundPaymentRequest(
                uniqid(),
                $amountMoney
            );
            $body->setPaymentId($transactionId);

            $response = $this->client->getRefundsApi()->refundPayment($body);

            if ($response->isSuccess()) {
                $refund = $response->getResult()->getRefund();
                return [
                    'success' => true,
                    'refund_id' => $refund->getId(),
                    'status' => $refund->getStatus(),
                ];
            } else {
                $errors = $response->getErrors();
                return [
                    'success' => false,
                    'error' => $errors[0]->getDetail() ?? 'Refund failed',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
