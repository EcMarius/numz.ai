<?php

namespace App\Numz\Modules\PaymentGateways;

use App\Numz\Contracts\PaymentGatewayInterface;
use App\Models\ModuleSetting;
use Illuminate\Support\Facades\Http;

class RazorpayGateway implements PaymentGatewayInterface
{
    protected $keyId;
    protected $keySecret;
    protected $baseUrl = 'https://api.razorpay.com/v1';
    protected $moduleName = 'razorpay';

    public function __construct()
    {
        $this->keyId = ModuleSetting::get('payment_gateway', $this->moduleName, 'key_id')
            ?? config('numz.gateways.razorpay.key_id');

        $this->keySecret = ModuleSetting::get('payment_gateway', $this->moduleName, 'key_secret')
            ?? config('numz.gateways.razorpay.key_secret');
    }

    public function charge(array $params): array
    {
        try {
            // Create Razorpay order
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post($this->baseUrl . '/orders', [
                    'amount' => $params['amount'] * 100, // Convert to paise
                    'currency' => $params['currency'] ?? 'INR',
                    'receipt' => $params['receipt'] ?? 'order_' . time(),
                    'notes' => $params['metadata'] ?? [],
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['id'])) {
                return [
                    'success' => true,
                    'order_id' => $data['id'],
                    'amount' => $data['amount'] / 100,
                    'currency' => $data['currency'],
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $data['error']['description'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function capturePayment(string $paymentId, float $amount): array
    {
        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post($this->baseUrl . '/payments/' . $paymentId . '/capture', [
                    'amount' => $amount * 100, // Convert to paise
                ]);

            $data = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'payment_id' => $data['id'],
                    'status' => $data['status'],
                ];
            }

            return [
                'success' => false,
                'error' => $data['error']['description'] ?? 'Capture failed',
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
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post($this->baseUrl . '/payments/' . $transactionId . '/refund', [
                    'amount' => $amount * 100, // Convert to paise
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['id'])) {
                return [
                    'success' => true,
                    'refund_id' => $data['id'],
                    'status' => $data['status'],
                ];
            }

            return [
                'success' => false,
                'error' => $data['error']['description'] ?? 'Refund failed',
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
            'name' => 'Razorpay',
            'description' => 'Accept payments via Razorpay. Popular in India with support for UPI, cards, netbanking, and wallets.',
            'supports_refunds' => true,
            'supports_recurring' => true,
            'currencies' => ['INR', 'USD', 'EUR', 'GBP'],
            'settings' => [
                [
                    'key' => 'key_id',
                    'label' => 'Key ID',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => true,
                ],
                [
                    'key' => 'key_secret',
                    'label' => 'Key Secret',
                    'type' => 'password',
                    'encrypted' => true,
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
        $webhookSecret = ModuleSetting::get('payment_gateway', $this->moduleName, 'webhook_secret');

        if (!$webhookSecret) {
            return false;
        }

        $signature = request()->header('X-Razorpay-Signature');

        if (!$signature) {
            return false;
        }

        $body = request()->getContent();
        $computedSignature = hash_hmac('sha256', $body, $webhookSecret);

        return hash_equals($computedSignature, $signature);
    }
}
