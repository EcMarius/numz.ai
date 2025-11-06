<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Handle Stripe webhook
     */
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            // Verify webhook signature
            $gatewayClass = config('numz.payment_gateways.stripe');
            if (!$gatewayClass) {
                Log::error('Stripe gateway not configured');
                return response()->json(['error' => 'Gateway not configured'], 500);
            }

            $gateway = new $gatewayClass();

            // Initialize with settings
            $settings = \App\Models\ModuleSetting::where('module_type', 'payment')
                ->where('module_name', 'stripe')
                ->pluck('setting_value', 'setting_key')
                ->toArray();

            $gateway->initialize($settings);

            // Verify signature
            if (!$gateway->verifyWebhookSignature($signature, $payload)) {
                Log::warning('Invalid Stripe webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Handle webhook
            $event = json_decode($payload, true);
            $result = $gateway->handleWebhook($event);

            if ($result['success'] && isset($result['invoice_id'])) {
                $invoice = Invoice::find($result['invoice_id']);

                if ($invoice) {
                    // Create payment transaction
                    PaymentTransaction::create([
                        'invoice_id' => $invoice->id,
                        'user_id' => $invoice->user_id,
                        'gateway' => 'stripe',
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'amount' => $result['amount'] ?? $invoice->total,
                        'currency' => $invoice->currency ?? 'USD',
                        'status' => 'completed',
                        'raw_response' => $payload,
                    ]);

                    // Mark invoice as paid
                    $this->invoiceService->markInvoiceAsPaid(
                        $invoice,
                        'stripe',
                        $result['transaction_id'] ?? null
                    );

                    Log::info('Stripe webhook processed successfully', [
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $result['transaction_id'] ?? null,
                    ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle PayPal webhook
     */
    public function paypal(Request $request)
    {
        $payload = $request->all();

        try {
            $gatewayClass = config('numz.payment_gateways.paypal');
            if (!$gatewayClass) {
                return response()->json(['error' => 'Gateway not configured'], 500);
            }

            $gateway = new $gatewayClass();

            $settings = \App\Models\ModuleSetting::where('module_type', 'payment')
                ->where('module_name', 'paypal')
                ->pluck('setting_value', 'setting_key')
                ->toArray();

            $gateway->initialize($settings);

            // Verify webhook signature
            $signature = $request->header('PAYPAL-TRANSMISSION-SIG');
            if (!$gateway->verifyWebhookSignature($signature, json_encode($payload))) {
                Log::warning('Invalid PayPal webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $result = $gateway->handleWebhook($payload);

            if ($result['success'] && isset($result['invoice_id'])) {
                $invoice = Invoice::find($result['invoice_id']);

                if ($invoice) {
                    PaymentTransaction::create([
                        'invoice_id' => $invoice->id,
                        'user_id' => $invoice->user_id,
                        'gateway' => 'paypal',
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'amount' => $result['amount'] ?? $invoice->total,
                        'currency' => $invoice->currency ?? 'USD',
                        'status' => 'completed',
                        'raw_response' => json_encode($payload),
                    ]);

                    $this->invoiceService->markInvoiceAsPaid(
                        $invoice,
                        'paypal',
                        $result['transaction_id'] ?? null
                    );

                    Log::info('PayPal webhook processed successfully', [
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $result['transaction_id'] ?? null,
                    ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('PayPal webhook error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Coinbase webhook
     */
    public function coinbase(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-CC-Webhook-Signature');

        try {
            $gatewayClass = config('numz.payment_gateways.coinbase');
            if (!$gatewayClass) {
                return response()->json(['error' => 'Gateway not configured'], 500);
            }

            $gateway = new $gatewayClass();

            $settings = \App\Models\ModuleSetting::where('module_type', 'payment')
                ->where('module_name', 'coinbase')
                ->pluck('setting_value', 'setting_key')
                ->toArray();

            $gateway->initialize($settings);

            if (!$gateway->verifyWebhookSignature($signature, $payload)) {
                Log::warning('Invalid Coinbase webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $event = json_decode($payload, true);
            $result = $gateway->handleWebhook($event);

            if ($result['success'] && isset($result['invoice_id'])) {
                $invoice = Invoice::find($result['invoice_id']);

                if ($invoice) {
                    PaymentTransaction::create([
                        'invoice_id' => $invoice->id,
                        'user_id' => $invoice->user_id,
                        'gateway' => 'coinbase',
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'amount' => $result['amount'] ?? $invoice->total,
                        'currency' => $invoice->currency ?? 'USD',
                        'status' => 'completed',
                        'raw_response' => $payload,
                    ]);

                    $this->invoiceService->markInvoiceAsPaid(
                        $invoice,
                        'coinbase',
                        $result['transaction_id'] ?? null
                    );

                    Log::info('Coinbase webhook processed successfully', [
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $result['transaction_id'] ?? null,
                    ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Coinbase webhook error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Razorpay webhook
     */
    public function razorpay(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        try {
            $gatewayClass = config('numz.payment_gateways.razorpay');
            if (!$gatewayClass) {
                return response()->json(['error' => 'Gateway not configured'], 500);
            }

            $gateway = new $gatewayClass();

            $settings = \App\Models\ModuleSetting::where('module_type', 'payment')
                ->where('module_name', 'razorpay')
                ->pluck('setting_value', 'setting_key')
                ->toArray();

            $gateway->initialize($settings);

            if (!$gateway->verifyWebhookSignature($signature, $payload)) {
                Log::warning('Invalid Razorpay webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $event = json_decode($payload, true);
            $result = $gateway->handleWebhook($event);

            if ($result['success'] && isset($result['invoice_id'])) {
                $invoice = Invoice::find($result['invoice_id']);

                if ($invoice) {
                    PaymentTransaction::create([
                        'invoice_id' => $invoice->id,
                        'user_id' => $invoice->user_id,
                        'gateway' => 'razorpay',
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'amount' => $result['amount'] ?? $invoice->total,
                        'currency' => $invoice->currency ?? 'USD',
                        'status' => 'completed',
                        'raw_response' => $payload,
                    ]);

                    $this->invoiceService->markInvoiceAsPaid(
                        $invoice,
                        'razorpay',
                        $result['transaction_id'] ?? null
                    );

                    Log::info('Razorpay webhook processed successfully', [
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $result['transaction_id'] ?? null,
                    ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Razorpay webhook error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle 2Checkout webhook
     */
    public function twoCheckout(Request $request)
    {
        $payload = $request->all();

        try {
            $gatewayClass = config('numz.payment_gateways.2checkout');
            if (!$gatewayClass) {
                return response()->json(['error' => 'Gateway not configured'], 500);
            }

            $gateway = new $gatewayClass();

            $settings = \App\Models\ModuleSetting::where('module_type', 'payment')
                ->where('module_name', '2checkout')
                ->pluck('setting_value', 'setting_key')
                ->toArray();

            $gateway->initialize($settings);

            // 2Checkout uses hash verification
            $hash = $request->header('X-Avangate-Signature');
            if (!$gateway->verifyWebhookSignature($hash, json_encode($payload))) {
                Log::warning('Invalid 2Checkout webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $result = $gateway->handleWebhook($payload);

            if ($result['success'] && isset($result['invoice_id'])) {
                $invoice = Invoice::find($result['invoice_id']);

                if ($invoice) {
                    PaymentTransaction::create([
                        'invoice_id' => $invoice->id,
                        'user_id' => $invoice->user_id,
                        'gateway' => '2checkout',
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'amount' => $result['amount'] ?? $invoice->total,
                        'currency' => $invoice->currency ?? 'USD',
                        'status' => 'completed',
                        'raw_response' => json_encode($payload),
                    ]);

                    $this->invoiceService->markInvoiceAsPaid(
                        $invoice,
                        '2checkout',
                        $result['transaction_id'] ?? null
                    );

                    Log::info('2Checkout webhook processed successfully', [
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $result['transaction_id'] ?? null,
                    ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('2Checkout webhook error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Paysafecard webhook
     */
    public function paysafecard(Request $request)
    {
        $payload = $request->all();

        try {
            $gatewayClass = config('numz.payment_gateways.paysafecard');
            if (!$gatewayClass) {
                return response()->json(['error' => 'Gateway not configured'], 500);
            }

            $gateway = new $gatewayClass();

            $settings = \App\Models\ModuleSetting::where('module_type', 'payment')
                ->where('module_name', 'paysafecard')
                ->pluck('setting_value', 'setting_key')
                ->toArray();

            $gateway->initialize($settings);

            $result = $gateway->handleWebhook($payload);

            if ($result['success'] && isset($result['invoice_id'])) {
                $invoice = Invoice::find($result['invoice_id']);

                if ($invoice) {
                    PaymentTransaction::create([
                        'invoice_id' => $invoice->id,
                        'user_id' => $invoice->user_id,
                        'gateway' => 'paysafecard',
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'amount' => $result['amount'] ?? $invoice->total,
                        'currency' => $invoice->currency ?? 'USD',
                        'status' => 'completed',
                        'raw_response' => json_encode($payload),
                    ]);

                    $this->invoiceService->markInvoiceAsPaid(
                        $invoice,
                        'paysafecard',
                        $result['transaction_id'] ?? null
                    );

                    Log::info('Paysafecard webhook processed successfully', [
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $result['transaction_id'] ?? null,
                    ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Paysafecard webhook error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}
