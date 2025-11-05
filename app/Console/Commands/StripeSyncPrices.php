<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plan;
use Wave\Plugins\EvenLeads\Models\Setting;
use Illuminate\Support\Facades\Http;

class StripeSyncPrices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stripe:sync-prices
                          {--currency=EUR : Currency to sync (EUR or USD)}
                          {--test : Use test mode}
                          {--force : Force recreate all prices}';

    /**
     * The console command description.
     */
    protected $description = 'Sync plans with Stripe and create/update price IDs (auto-detects test/live mode from settings)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currency = strtoupper($this->option('currency'));
        $force = $this->option('force');

        // Auto-detect mode from settings unless --test flag is used
        if ($this->option('test')) {
            $mode = 'test';
            $this->info("🔧 Using TEST mode (forced via --test flag)");
        } else {
            // Auto-detect from database settings
            $configuredMode = Setting::getValue('stripe.mode');

            if (empty($configuredMode)) {
                $configuredMode = setting('stripe.mode', 'test');
            }

            $mode = strtolower($configuredMode);
            $this->info("🔧 Auto-detected mode from settings: " . strtoupper($mode));
        }

        // Get Stripe credentials for the detected/selected mode
        $secretKey = Setting::getValue("stripe.{$mode}.secret_key");

        // If not found, try wave settings helper
        if (empty($secretKey)) {
            $secretKey = setting("stripe.{$mode}.secret_key");
        }

        if (empty($secretKey)) {
            $this->newLine();
            $this->error("❌ Stripe {$mode} secret key not configured!");
            $this->newLine();
            $this->warn("Current mode setting: " . strtoupper($mode));
            $this->info("Please configure Stripe credentials in Admin → EvenLeads Settings");
            $this->newLine();
            $this->comment("Tip: Change mode in settings or use --test flag to force test mode");
            return 1;
        }

        $this->info("✅ Using {$mode} mode with key: " . substr($secretKey, 0, 15) . "...");
        $this->newLine();

        $this->info("🔄 Syncing plans with Stripe ({$mode} mode, {$currency})...");
        $this->newLine();

        // Get all active non-custom plans
        $plans = Plan::where('active', true)
            ->where('custom_plan', false)
            ->get();

        if ($plans->isEmpty()) {
            $this->warn('No active plans found to sync.');
            return 0;
        }

        $bar = $this->output->createProgressBar($plans->count() * 2); // Monthly + Yearly

        foreach ($plans as $plan) {
            $this->newLine();
            $this->info("📦 Processing: {$plan->name}");

            // Create/Update Monthly Price
            $monthlyResult = $this->createOrUpdatePrice(
                $secretKey,
                $plan,
                'monthly',
                $currency,
                $force
            );

            if ($monthlyResult['success']) {
                $plan->monthly_price_id = $monthlyResult['price_id'];
                $this->line("  ✅ Monthly: {$monthlyResult['price_id']}");
            } else {
                $this->newLine();
                $this->error("  ❌ Monthly Error: {$monthlyResult['error']}");
            }
            $bar->advance();

            // Create/Update Yearly Price
            $yearlyResult = $this->createOrUpdatePrice(
                $secretKey,
                $plan,
                'yearly',
                $currency,
                $force
            );

            if ($yearlyResult['success']) {
                $plan->yearly_price_id = $yearlyResult['price_id'];
                $this->line("  ✅ Yearly: {$yearlyResult['price_id']}");
            } else {
                $this->error("  ❌ Yearly: {$yearlyResult['error']}");
            }
            $bar->advance();

            $plan->save();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('🎉 Stripe price sync completed!');

        return 0;
    }

    /**
     * Create or update a Stripe price
     */
    protected function createOrUpdatePrice($secretKey, $plan, $interval, $currency, $force)
    {
        try {
            $priceField = "{$interval}_price";
            $priceIdField = "{$interval}_price_id";

            // Get price amount
            $amount = $plan->$priceField;
            if (empty($amount) || $amount == '0.00') {
                return [
                    'success' => false,
                    'error' => 'Price is 0 or not set'
                ];
            }

            // Convert to cents
            $amountCents = (int) ($amount * 100);

            // Check if price ID already exists
            if (!$force && !empty($plan->$priceIdField)) {
                // Verify the price exists in Stripe
                $checkResponse = Http::withBasicAuth($secretKey, '')
                    ->get("https://api.stripe.com/v1/prices/{$plan->$priceIdField}");

                if ($checkResponse->successful()) {
                    return [
                        'success' => true,
                        'price_id' => $plan->$priceIdField,
                        'action' => 'existing'
                    ];
                }
            }

            // Create product first (or get existing)
            $productId = $this->getOrCreateProduct($secretKey, $plan);

            if (!$productId) {
                return [
                    'success' => false,
                    'error' => 'Failed to create product'
                ];
            }

            // Create new price
            $response = Http::withBasicAuth($secretKey, '')
                ->asForm()
                ->post('https://api.stripe.com/v1/prices', [
                    'product' => $productId,
                    'unit_amount' => $amountCents,
                    'currency' => strtolower($currency),
                    'recurring' => [
                        'interval' => $interval === 'yearly' ? 'year' : 'month',
                    ],
                    'nickname' => "{$plan->name} - " . ucfirst($interval),
                ]);

            if ($response->successful()) {
                $price = $response->json();
                return [
                    'success' => true,
                    'price_id' => $price['id'],
                    'action' => 'created'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $this->parseStripeError($response->body())
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get or create Stripe product
     */
    protected function getOrCreateProduct($secretKey, $plan)
    {
        try {
            // Try to find existing product by name
            $searchResponse = Http::withBasicAuth($secretKey, '')
                ->get('https://api.stripe.com/v1/products', [
                    'active' => 'true',
                    'limit' => 100,
                ]);

            if ($searchResponse->successful()) {
                $products = $searchResponse->json()['data'] ?? [];

                foreach ($products as $product) {
                    if ($product['name'] === $plan->name) {
                        $this->line("    Found existing product: {$product['id']}");
                        return $product['id'];
                    }
                }
            } else {
                $this->error("    Search failed: " . $this->parseStripeError($searchResponse->body()));
            }

            // Create new product
            $this->line("    Creating new product: {$plan->name}");
            $response = Http::withBasicAuth($secretKey, '')
                ->asForm()
                ->post('https://api.stripe.com/v1/products', [
                    'name' => $plan->name,
                    'description' => $plan->description,
                ]);

            if ($response->successful()) {
                $product = $response->json();
                $this->line("    Product created: {$product['id']}");
                return $product['id'];
            } else {
                $this->error("    Create failed: " . $this->parseStripeError($response->body()));
            }

            return null;

        } catch (\Exception $e) {
            $this->error("    Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse Stripe error response
     */
    protected function parseStripeError($errorResponse)
    {
        $decoded = json_decode($errorResponse, true);

        if (isset($decoded['error']['message'])) {
            return $decoded['error']['message'];
        }

        if (preg_match('/"message":\s*"([^"]+)"/', $errorResponse, $matches)) {
            return $matches[1];
        }

        return 'Unknown error';
    }
}
