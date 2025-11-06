<?php

namespace App\Numz\Modules\PaymentGateways;

use App\Numz\Contracts\PaymentGatewayInterface;
use App\Models\ModuleSetting;
use Illuminate\Support\Facades\Http;

class TwoCheckoutGateway implements PaymentGatewayInterface
{
    protected $merchantCode;
    protected $secretKey;
    protected $baseUrl;
    protected $moduleName = 'twocheckout';

    public function __construct()
    {
        $this->merchantCode = ModuleSetting::get('payment_gateway', $this->moduleName, 'merchant_code')
            ?? config('numz.gateways.twocheckout.merchant_code');

        $this->secretKey = ModuleSetting::get('payment_gateway', $this->moduleName, 'secret_key')
            ?? config('numz.gateways.twocheckout.secret_key');

        $sandbox = ModuleSetting::get('payment_gateway', $this->moduleName, 'sandbox', 'false') === 'true';

        $this->baseUrl = $sandbox
            ? 'https://sandbox.2checkout.com'
            : 'https://www.2checkout.com';
    }

    public function charge(array $params): array
    {
        try {
            // 2Checkout uses hosted checkout page
            $formData = [
                'sid' => $this->merchantCode,
                'mode' => '2CO',
                'li_0_name' => $params['description'] ?? 'NUMZ.AI Service',
                'li_0_price' => $params['amount'],
                'li_0_quantity' => 1,
                'currency_code' => $params['currency'] ?? 'USD',
                'merchant_order_id' => $params['order_id'] ?? uniqid('order_'),
                'return_url' => $params['success_url'] ?? url('/'),
                'cancel_url' => $params['cancel_url'] ?? url('/'),
            ];

            // Generate checkout URL
            $checkoutUrl = $this->baseUrl . '/checkout/purchase?' . http_build_query($formData);

            return [
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'transaction_id' => $formData['merchant_order_id'],
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
            // 2Checkout API for refunds
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->withBasicAuth($this->merchantCode, $this->secretKey)
                ->post('https://api.2checkout.com/rest/6.0/orders/' . $transactionId . '/refund/', [
                    'amount' => $amount,
                    'comment' => 'Refund processed via NUMZ.AI',
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['refund_id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Refund failed',
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
            'name' => '2Checkout (Verifone)',
            'description' => 'Accept credit cards and PayPal via 2Checkout. Supports worldwide payments.',
            'supports_refunds' => true,
            'supports_recurring' => true,
            'currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'CAD'],
            'settings' => [
                [
                    'key' => 'merchant_code',
                    'label' => 'Merchant Code (Seller ID)',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => true,
                ],
                [
                    'key' => 'secret_key',
                    'label' => 'Secret Key',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => true,
                ],
                [
                    'key' => 'sandbox',
                    'label' => 'Sandbox Mode',
                    'type' => 'boolean',
                    'encrypted' => false,
                    'required' => false,
                    'default' => 'false',
                ],
            ],
        ];
    }

    public function validateWebhook(array $payload): bool
    {
        // 2Checkout uses MD5 hash for webhook validation
        $signature = request()->input('md5_hash');

        if (!$signature) {
            return false;
        }

        $params = request()->except('md5_hash');
        ksort($params);

        $hashString = '';
        foreach ($params as $key => $value) {
            $hashString .= strlen($value) . $value;
        }

        $computedHash = md5($hashString . $this->secretKey);

        return hash_equals($computedHash, $signature);
    }
}
