<?php

namespace App\Services;

use Wave\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected $secretKey;
    protected $publishableKey;
    protected $webhookSecret;
    protected $baseUrl = 'https://api.stripe.com/v1';
    protected $mode;

    public function __construct()
    {
        // Get active mode (test or live) from config
        $this->mode = config('wave.stripe.mode', 'test');

        // Get credentials from Wave config
        if ($this->mode === 'live') {
            $this->secretKey = config('wave.stripe.live.secret_key') ?? config('wave.stripe.secret_key');
            $this->publishableKey = config('wave.stripe.live.publishable_key') ?? config('wave.stripe.publishable_key');
            $this->webhookSecret = config('wave.stripe.live.webhook_secret') ?? config('wave.stripe.webhook_secret');
        } else {
            $this->secretKey = config('wave.stripe.test.secret_key') ?? config('wave.stripe.secret_key');
            $this->publishableKey = config('wave.stripe.test.publishable_key') ?? config('wave.stripe.publishable_key');
            $this->webhookSecret = config('wave.stripe.test.webhook_secret') ?? config('wave.stripe.webhook_secret');
        }
    }

    /**
     * Get current mode
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Get publishable key
     */
    public function getPublishableKey(): ?string
    {
        return $this->publishableKey;
    }

    /**
     * Get webhook secret
     */
    public function getWebhookSecret(): ?string
    {
        return $this->webhookSecret;
    }

    /**
     * Get secret key
     */
    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    /**
     * Check if Stripe is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Create or update Stripe product and prices for a plan
     */
    public function syncPlanToStripe(Plan $plan, bool $refreshPrices = false): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Stripe API credentials not configured');
        }

        $result = [
            'success' => true,
            'product_id' => null,
            'monthly_price_id' => null,
            'yearly_price_id' => null,
            'errors' => []
        ];

        try {
            // Create or update product
            $product = $this->createOrUpdateProduct($plan);
            $result['product_id'] = $product['id'];

            // Get plan currency (default to USD if not set)
            $currency = $plan->currency ?? 'usd';

            // Handle monthly price
            if (!empty($plan->monthly_price)) {
                $needsNewPrice = $refreshPrices || empty($plan->monthly_price_id);

                // If price ID exists but refresh not forced, verify it exists in Stripe
                if (!$needsNewPrice && !empty($plan->monthly_price_id)) {
                    if (!$this->verifyPriceExists($plan->monthly_price_id)) {
                        Log::warning("Monthly price ID doesn't exist in Stripe, will recreate", [
                            'plan' => $plan->name,
                            'price_id' => $plan->monthly_price_id
                        ]);
                        $needsNewPrice = true;
                    }
                }

                if ($needsNewPrice) {
                    $monthlyPrice = $this->createPrice($product['id'], $plan->monthly_price, 'month', $plan->name . ' - Monthly', $currency);
                    $result['monthly_price_id'] = $monthlyPrice['id'];
                } else {
                    $result['monthly_price_id'] = $plan->monthly_price_id;
                }
            }

            // Handle yearly price
            if (!empty($plan->yearly_price)) {
                $needsNewPrice = $refreshPrices || empty($plan->yearly_price_id);

                // If price ID exists but refresh not forced, verify it exists in Stripe
                if (!$needsNewPrice && !empty($plan->yearly_price_id)) {
                    if (!$this->verifyPriceExists($plan->yearly_price_id)) {
                        Log::warning("Yearly price ID doesn't exist in Stripe, will recreate", [
                            'plan' => $plan->name,
                            'price_id' => $plan->yearly_price_id
                        ]);
                        $needsNewPrice = true;
                    }
                }

                if ($needsNewPrice) {
                    $yearlyPrice = $this->createPrice($product['id'], $plan->yearly_price, 'year', $plan->name . ' - Yearly', $currency);
                    $result['yearly_price_id'] = $yearlyPrice['id'];
                } else {
                    $result['yearly_price_id'] = $plan->yearly_price_id;
                }
            }

            // Update plan with Stripe IDs
            $plan->update([
                'stripe_id' => $result['product_id'],
                'monthly_price_id' => $result['monthly_price_id'],
                'yearly_price_id' => $result['yearly_price_id'],
            ]);

        } catch (\Exception $e) {
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
            Log::error('Stripe sync error for plan ' . $plan->id, [
                'error' => $e->getMessage(),
                'plan' => $plan->name
            ]);
        }

        return $result;
    }

    /**
     * Create or update a Stripe product
     */
    protected function createOrUpdateProduct(Plan $plan): array
    {
        $productData = [
            'name' => $plan->name,
            'description' => $plan->description ?? '',
            'metadata' => [
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
            ]
        ];

        // If plan already has a stripe_id, update it
        if (!empty($plan->stripe_id)) {
            try {
                return $this->updateProduct($plan->stripe_id, $productData);
            } catch (\Exception $e) {
                // If update fails, create new product
                Log::warning('Failed to update Stripe product, creating new one', [
                    'stripe_id' => $plan->stripe_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Create new product
        return $this->createProduct($productData);
    }

    /**
     * Create a Stripe product
     */
    protected function createProduct(array $data): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->asForm()
            ->post($this->baseUrl . '/products', $data);

        if ($response->failed()) {
            throw new \Exception('Failed to create Stripe product: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Update a Stripe product
     */
    protected function updateProduct(string $productId, array $data): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->asForm()
            ->post($this->baseUrl . '/products/' . $productId, $data);

        if ($response->failed()) {
            throw new \Exception('Failed to update Stripe product: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Create a Stripe price
     */
    protected function createPrice(string $productId, float $amount, string $interval, string $nickname, string $currency = 'usd'): array
    {
        $priceData = [
            'product' => $productId,
            'unit_amount' => (int)($amount * 100), // Convert to cents
            'currency' => strtolower($currency), // Use plan's currency
            'recurring' => [
                'interval' => $interval,
            ],
            'nickname' => $nickname,
        ];

        $response = Http::withBasicAuth($this->secretKey, '')
            ->asForm()
            ->post($this->baseUrl . '/prices', $priceData);

        if ($response->failed()) {
            throw new \Exception('Failed to create Stripe price: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Create webhook endpoint
     */
    public function createWebhook(string $url): array
    {
        $webhookData = [
            'url' => $url,
            'enabled_events' => [
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted',
                'customer.updated',
                'customer.deleted',
                'invoice.payment_succeeded',
                'invoice.payment_failed',
            ],
        ];

        $response = Http::withBasicAuth($this->secretKey, '')
            ->asForm()
            ->post($this->baseUrl . '/webhook_endpoints', $webhookData);

        if ($response->failed()) {
            throw new \Exception('Failed to create webhook: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * List all webhooks
     */
    public function listWebhooks(): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->get($this->baseUrl . '/webhook_endpoints');

        if ($response->failed()) {
            throw new \Exception('Failed to list webhooks: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Delete a webhook
     */
    public function deleteWebhook(string $webhookId): bool
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->delete($this->baseUrl . '/webhook_endpoints/' . $webhookId);

        return $response->successful();
    }

    /**
     * Verify that a price ID exists in Stripe
     */
    protected function verifyPriceExists(string $priceId): bool
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/prices/' . $priceId);

            return $response->successful();
        } catch (\Exception $e) {
            Log::debug("Price verification failed for {$priceId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test Stripe API connection
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/products', ['limit' => 1]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Successfully connected to Stripe API (' . strtoupper($this->mode) . ' mode)'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to connect: ' . $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate prorated amount for seat change
     *
     * @param int $oldQuantity Current seat quantity
     * @param int $newQuantity New seat quantity
     * @param float $pricePerSeat Price per seat per billing period
     * @param string $subscriptionId Stripe subscription ID to get accurate proration
     * @return array ['amount' => float, 'currency' => string, 'days_remaining' => int]
     */
    public function calculateSeatProration(int $oldQuantity, int $newQuantity, float $pricePerSeat, string $subscriptionId): array
    {
        try {
            // Get subscription to determine billing cycle
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/subscriptions/' . $subscriptionId);

            if ($response->failed()) {
                throw new \Exception('Failed to retrieve subscription: ' . $response->body());
            }

            $subscription = $response->json();

            // Get current period end
            $currentPeriodEnd = $subscription['current_period_end'];
            $now = now()->timestamp;
            $daysRemaining = ceil(($currentPeriodEnd - $now) / 86400); // 86400 seconds in a day
            $totalDaysInPeriod = ceil(($currentPeriodEnd - $subscription['current_period_start']) / 86400);

            // Calculate prorated amount
            $seatDifference = $newQuantity - $oldQuantity;
            $proratedAmount = ($seatDifference * $pricePerSeat * $daysRemaining) / $totalDaysInPeriod;

            return [
                'amount' => round($proratedAmount, 2),
                'currency' => $subscription['currency'] ?? 'eur',
                'days_remaining' => $daysRemaining,
                'total_days' => $totalDaysInPeriod,
                'seat_difference' => $seatDifference,
                'price_per_seat' => $pricePerSeat,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to calculate seat proration', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId
            ]);
            throw $e;
        }
    }

    /**
     * Get upcoming invoice preview for a subscription
     *
     * @param string $customerId Stripe customer ID
     * @param string $subscriptionId Stripe subscription ID
     * @return array|null Invoice data or null if none
     */
    public function getUpcomingInvoice(string $customerId, string $subscriptionId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/invoices/upcoming', [
                    'customer' => $customerId,
                    'subscription' => $subscriptionId,
                ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::debug('Failed to get upcoming invoice', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId
            ]);
            return null;
        }
    }

    /**
     * Check if upcoming invoice has pending prorations
     *
     * @param string $customerId Stripe customer ID
     * @param string $subscriptionId Stripe subscription ID
     * @return array ['has_prorations' => bool, 'amount' => float, 'currency' => string]
     */
    public function checkPendingProrations(string $customerId, string $subscriptionId): array
    {
        $result = [
            'has_prorations' => false,
            'amount' => 0.0,
            'currency' => 'eur',
        ];

        $invoice = $this->getUpcomingInvoice($customerId, $subscriptionId);

        if (!$invoice) {
            return $result;
        }

        $totalProrations = 0;

        foreach ($invoice['lines']['data'] ?? [] as $line) {
            if ($line['proration'] ?? false) {
                $totalProrations += $line['amount'];
            }
        }

        if ($totalProrations > 0) {
            $result['has_prorations'] = true;
            $result['amount'] = $totalProrations / 100; // Convert from cents
            $result['currency'] = $invoice['currency'];
        }

        return $result;
    }

    /**
     * Create and finalize an invoice immediately (for seat increases)
     *
     * @param string $customerId Stripe customer ID
     * @param string $subscriptionId Stripe subscription ID
     * @param string $description Description for the invoice
     * @return array Invoice data with payment status
     * @throws \Exception If invoice creation or payment fails
     */
    public function createImmediateInvoice(string $customerId, string $subscriptionId, string $description = 'Prorated charge for subscription update'): array
    {
        try {
            // Create the invoice
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post($this->baseUrl . '/invoices', [
                    'customer' => $customerId,
                    'subscription' => $subscriptionId,
                    'description' => $description,
                    'auto_advance' => false, // We'll finalize it manually
                    'collection_method' => 'charge_automatically',
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to create invoice: ' . $response->body());
            }

            $invoice = $response->json();

            // If there's nothing to charge, return success
            if ($invoice['amount_due'] <= 0) {
                return [
                    'success' => true,
                    'invoice_id' => $invoice['id'],
                    'amount' => 0,
                    'status' => 'paid',
                    'message' => 'No charge required'
                ];
            }

            // Finalize the invoice to charge the customer
            $finalizeResponse = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post($this->baseUrl . '/invoices/' . $invoice['id'] . '/finalize', [
                    'auto_advance' => true,
                ]);

            if ($finalizeResponse->failed()) {
                throw new \Exception('Failed to finalize invoice: ' . $finalizeResponse->body());
            }

            $finalizedInvoice = $finalizeResponse->json();

            // Wait a moment for payment to process
            sleep(2);

            // Retrieve the invoice to check payment status
            $checkResponse = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/invoices/' . $invoice['id']);

            if ($checkResponse->successful()) {
                $finalizedInvoice = $checkResponse->json();
            }

            // Check if payment succeeded
            if ($finalizedInvoice['status'] !== 'paid') {
                throw new \Exception('Payment failed or requires action. Status: ' . $finalizedInvoice['status']);
            }

            return [
                'success' => true,
                'invoice_id' => $finalizedInvoice['id'],
                'amount' => $finalizedInvoice['amount_paid'] / 100,
                'currency' => $finalizedInvoice['currency'],
                'status' => $finalizedInvoice['status'],
                'payment_intent' => $finalizedInvoice['payment_intent'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create immediate invoice', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'subscription_id' => $subscriptionId,
            ]);
            throw $e;
        }
    }

    /**
     * Validate payment method for a customer
     *
     * @param string $customerId Stripe customer ID
     * @return array ['valid' => bool, 'message' => string, 'payment_method' => array|null]
     */
    public function validatePaymentMethod(string $customerId): array
    {
        try {
            // Get customer details
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/customers/' . $customerId);

            if ($response->failed()) {
                return [
                    'valid' => false,
                    'message' => 'Failed to retrieve customer information',
                    'payment_method' => null
                ];
            }

            $customer = $response->json();

            // Check if customer has a default payment method
            $defaultPaymentMethod = $customer['invoice_settings']['default_payment_method'] ?? null;

            if (!$defaultPaymentMethod) {
                return [
                    'valid' => false,
                    'message' => 'No payment method on file. Please add a payment method.',
                    'payment_method' => null
                ];
            }

            // Retrieve payment method details
            $pmResponse = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/payment_methods/' . $defaultPaymentMethod);

            if ($pmResponse->failed()) {
                return [
                    'valid' => false,
                    'message' => 'Payment method is invalid',
                    'payment_method' => null
                ];
            }

            $paymentMethod = $pmResponse->json();

            // Check if card is expired (if it's a card)
            if ($paymentMethod['type'] === 'card') {
                $card = $paymentMethod['card'];
                $expMonth = $card['exp_month'];
                $expYear = $card['exp_year'];
                $now = now();

                if ($expYear < $now->year || ($expYear == $now->year && $expMonth < $now->month)) {
                    return [
                        'valid' => false,
                        'message' => 'Payment card has expired. Please update your payment method.',
                        'payment_method' => $paymentMethod
                    ];
                }
            }

            return [
                'valid' => true,
                'message' => 'Payment method is valid',
                'payment_method' => $paymentMethod
            ];

        } catch (\Exception $e) {
            Log::error('Failed to validate payment method', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId
            ]);

            return [
                'valid' => false,
                'message' => 'Error validating payment method: ' . $e->getMessage(),
                'payment_method' => null
            ];
        }
    }

    /**
     * Create a Checkout Session for seat increase payment
     *
     * @param string $customerId Stripe customer ID
     * @param float $amount Amount to charge (prorated)
     * @param string $currency Currency code (e.g., 'eur', 'usd')
     * @param int $newSeats New total seats
     * @param int $oldSeats Current seats
     * @param string $successUrl URL to redirect after successful payment
     * @param string $cancelUrl URL to redirect if payment cancelled
     * @return array ['success' => bool, 'url' => string|null, 'session_id' => string|null, 'message' => string]
     */
    public function createPaymentSessionForSeats(
        string $customerId,
        float $amount,
        string $currency,
        int $newSeats,
        int $oldSeats,
        string $successUrl,
        string $cancelUrl
    ): array {
        try {
            \Stripe\Stripe::setApiKey($this->secretKey);

            $session = \Stripe\Checkout\Session::create([
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => (int) ($amount * 100), // Convert to cents
                        'product_data' => [
                            'name' => 'Additional Seats - Prorated Charge',
                            'description' => "Increase from {$oldSeats} to {$newSeats} seats (prorated for remaining billing period)",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'type' => 'seat_increase',
                    'old_seats' => $oldSeats,
                    'new_seats' => $newSeats,
                    'prorated_amount' => $amount,
                ],
            ]);

            Log::info('Checkout session created for seat increase', [
                'customer_id' => $customerId,
                'session_id' => $session->id,
                'amount' => $amount,
                'old_seats' => $oldSeats,
                'new_seats' => $newSeats
            ]);

            return [
                'success' => true,
                'url' => $session->url,
                'session_id' => $session->id,
                'message' => 'Checkout session created successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create checkout session for seats', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'amount' => $amount
            ]);

            return [
                'success' => false,
                'url' => null,
                'session_id' => null,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a customer portal session for payment method management
     *
     * @param string $customerId Stripe customer ID
     * @param string $returnUrl URL to return to after portal session
     * @return array ['success' => bool, 'url' => string|null, 'message' => string]
     */
    public function createCustomerPortalSession(string $customerId, string $returnUrl): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post($this->baseUrl . '/billing_portal/sessions', [
                    'customer' => $customerId,
                    'return_url' => $returnUrl,
                ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error';

                Log::error('Failed to create customer portal session', [
                    'http_status' => $response->status(),
                    'error' => $errorData,
                    'error_message' => $errorMessage,
                    'customer_id' => $customerId,
                    'return_url' => $returnUrl
                ]);

                return [
                    'success' => false,
                    'url' => null,
                    'message' => 'Failed to create portal session: ' . $errorMessage
                ];
            }

            $session = $response->json();

            Log::info('Customer portal session created successfully', [
                'customer_id' => $customerId,
                'session_url' => $session['url']
            ]);

            return [
                'success' => true,
                'url' => $session['url'],
                'message' => 'Portal session created successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error creating customer portal session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'customer_id' => $customerId
            ]);

            return [
                'success' => false,
                'url' => null,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve an invoice by ID
     *
     * @param string $invoiceId Stripe invoice ID
     * @return array|null Invoice data or null if not found
     */
    public function getInvoice(string $invoiceId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/invoices/' . $invoiceId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::debug('Failed to retrieve invoice', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoiceId
            ]);
            return null;
        }
    }
}
