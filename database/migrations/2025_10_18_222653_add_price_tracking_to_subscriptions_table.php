<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL FOR BILLING COMPLIANCE:
     * Store the exact Stripe price_id used when subscription was created.
     * This enables grandfathering - existing customers keep their price
     * even when plan prices change for new customers.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Store the Stripe price ID used at subscription time
            // This is CRITICAL for grandfathering and billing compliance
            $table->string('vendor_price_id')->nullable()->after('vendor_subscription_id');

            // Store the actual amount charged at subscription time
            // Helps with auditing and dispute resolution
            $table->decimal('subscribed_price', 10, 2)->nullable()->after('vendor_price_id');
            $table->string('subscribed_currency', 3)->nullable()->after('subscribed_price');
        });

        // Backfill existing subscriptions with their current Stripe price_ids
        $this->backfillExistingSubscriptions();
    }

    /**
     * Backfill existing subscriptions with Stripe data
     */
    protected function backfillExistingSubscriptions(): void
    {
        if (!config('wave.stripe.secret_key')) {
            return; // Skip if Stripe not configured
        }

        $stripe = new \Stripe\StripeClient(config('wave.stripe.secret_key'));
        $subscriptions = \Wave\Subscription::whereNotNull('vendor_subscription_id')
            ->where('status', 'active')
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $stripeSub = $stripe->subscriptions->retrieve($subscription->vendor_subscription_id);
                $priceData = $stripeSub->items->data[0]->price;

                $subscription->update([
                    'vendor_price_id' => $priceData->id,
                    'subscribed_price' => $priceData->unit_amount / 100,
                    'subscribed_currency' => strtoupper($priceData->currency),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to backfill subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['vendor_price_id', 'subscribed_price', 'subscribed_currency']);
        });
    }
};
